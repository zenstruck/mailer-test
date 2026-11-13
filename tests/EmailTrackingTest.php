<?php

/*
 * This file is part of the zenstruck/mailer-test package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Mailer\Test\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;
use Symfony\Component\Mime\Address;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Notifier\Recipient\Recipient;
use Zenstruck\Mailer\Test\InteractsWithMailer;
use Zenstruck\Mailer\Test\SentEmails;
use Zenstruck\Mailer\Test\Tests\Fixture\Email1;
use Zenstruck\Mailer\Test\Tests\Fixture\EmailNotification;

final class EmailTrackingTest extends KernelTestCase
{
    use InteractsWithMailer;

    /**
     * @test
     * @dataProvider environmentProvider
     */
    public function queued_and_direct_emails_with_identical_contents_are_both_collected(string $environment): void
    {
        self::bootKernel(['environment' => $environment]);
        $queued = new Email1();
        $direct = new Email1();
        $headers = $queued->getHeaders()->toString();

        self::getContainer()->get('mailer')->send($queued);
        $sent = self::getContainer()->get('mailer.transports')->send($direct);

        $this->mailer()->assertSentEmailCount(2);
        $this->assertSame($headers, $queued->getHeaders()->toString());
        $this->assertSame($headers, $sent->getOriginalMessage()->getHeaders()->toString());
        $this->assertSame('html body', $queued->getHtmlBody());
        $this->assertSame('html body', $sent->getOriginalMessage()->getHtmlBody());

        if ('bus_async_serialized' === $environment) {
            $messages = self::getContainer()->get('messenger.transport.async')->getSent();
            $this->assertNotSame($queued, $messages[0]->getMessage()->getMessage());
        }

        $this->processQueue();

        $this->mailer()->assertSentEmailCount(2);
        $this->assertProfilerCount(2);
    }

    /**
     * @test
     * @dataProvider environmentProvider
     */
    public function reusing_the_same_email_and_message_id_still_creates_distinct_sends(string $environment): void
    {
        self::bootKernel(['environment' => $environment]);
        $email = new Email1();
        $email->getHeaders()->addIdHeader('Message-ID', 'same@example.com');
        $mailer = self::getContainer()->get('mailer');
        $transport = self::getContainer()->get('mailer.transports');

        $transport->send($email);
        $mailer->send($email);
        $transport->send($email);
        $mailer->send($email);

        $this->mailer()->assertSentEmailCount(4);

        $this->processQueue();

        $this->mailer()->assertSentEmailCount(4);
        $this->assertProfilerCount(4);
    }

    /**
     * @test
     */
    public function delivery_is_not_counted_again_when_listeners_render_different_content(): void
    {
        self::bootKernel(['environment' => 'bus_async_serialized']);
        $renders = 0;
        self::getContainer()->get('event_dispatcher')->addListener(MessageEvent::class, static function(MessageEvent $event) use (&$renders): void {
            $event->getMessage()->html('render '.++$renders);
        });

        self::getContainer()->get('mailer')->send(new Email1());
        $this->mailer()->assertEmailSentTo('kevin@example.com', static fn($email) => $email->assertHtmlContains('render 1'));

        $this->processQueue();

        $this->assertSame(2, $renders);
        $this->mailer()->assertSentEmailCount(1);
        $this->assertProfilerCount(1);
    }

    /**
     * @test
     * @dataProvider envelopeListenerPriorityProvider
     */
    public function replacing_the_envelope_in_a_listener_preserves_the_send_identity(int $priority): void
    {
        self::bootKernel(['environment' => 'bus_async_serialized']);
        self::getContainer()->get('event_dispatcher')->addListener(MessageEvent::class, static function(MessageEvent $event): void {
            $event->setEnvelope(new Envelope(new Address('bounce@example.com'), [new Address('override@example.com')]));
        }, $priority);

        self::getContainer()->get('mailer')->send(new Email1());
        $this->mailer()->assertSentEmailCount(1);

        $this->processQueue();

        $this->mailer()->assertSentEmailCount(1);
        $this->assertProfilerCount(1);
    }

    /**
     * @test
     */
    public function resetting_collection_allows_a_later_delivery_to_be_collected(): void
    {
        self::bootKernel(['environment' => 'bus_async_serialized']);

        self::getContainer()->get('mailer')->send(new Email1());
        $this->mailer()->reset()->assertNoEmailSent();

        $this->processQueue();

        $this->mailer()->assertSentEmailCount(1);
    }

    public static function environmentProvider(): iterable
    {
        yield ['bus_async'];
        yield ['bus_async_serialized'];
    }

    public static function envelopeListenerPriorityProvider(): iterable
    {
        yield [0];
        yield [-512];
    }

    /**
     * @test
     */
    public function notification_sends_are_tracked_through_queue_serialization(): void
    {
        self::bootKernel(['environment' => 'notifier_bus_async_serialized']);

        self::getContainer()->get(NotifierInterface::class)->send(new EmailNotification(new Email1()), new Recipient('kevin@example.com'));
        self::getContainer()->get('mailer.transports')->send(new Email1());
        $this->mailer()->assertSentEmailCount(2);

        $this->processQueue();

        $this->mailer()->assertSentEmailCount(2);
        $this->assertProfilerCount(2);
    }

    private function processQueue(): void
    {
        $transport = self::getContainer()->get('messenger.transport.async');

        foreach ($transport->get(\PHP_INT_MAX) as $envelope) {
            self::getContainer()->get(MessageBusInterface::class)->dispatch($envelope->with(new ReceivedStamp('async')));
            $transport->ack($envelope);
        }

        $this->assertCount(0, $transport->get());
    }

    private function assertProfilerCount(int $expected): void
    {
        $logger = self::getContainer()->get('mailer.message_logger_listener');
        $events = \unserialize(\serialize($logger->getEvents()));

        SentEmails::fromEvents($events)->assertCount($expected);
    }
}
