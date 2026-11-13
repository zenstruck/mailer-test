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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Mailer\DataCollector\MessageDataCollector;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\BusNameStamp;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Notifier\Recipient\Recipient;
use Zenstruck\Mailer\Test\InteractsWithMailer;
use Zenstruck\Mailer\Test\SentEmails;
use Zenstruck\Mailer\Test\Tests\Fixture\Email1;
use Zenstruck\Mailer\Test\Tests\Fixture\EmailNotification;

final class NotifierTest extends KernelTestCase
{
    use InteractsWithMailer;

    /**
     * @test
     * @dataProvider environmentProvider
     */
    public function can_collect_notification_emails(string $environment): void
    {
        self::bootKernel(['environment' => $environment]);

        self::getContainer()->get(NotifierInterface::class)->send(new EmailNotification(new Email1()), new Recipient('kevin@example.com'));

        $this->mailer()
            ->assertSentEmailCount(1)
            ->assertEmailSentTo('kevin@example.com', 'email subject')
        ;
    }

    /**
     * @test
     */
    public function queued_notifications_are_collected_before_processing_and_not_duplicated_afterward(): void
    {
        self::bootKernel(['environment' => 'notifier_bus_async']);

        self::getContainer()->get(NotifierInterface::class)->send(new EmailNotification(new Email1()), new Recipient('kevin@example.com'));
        self::getContainer()->get('mailer')->send(new Email1());

        $transport = self::getContainer()->get('messenger.transport.async');
        $this->assertCount(2, $transport->getSent());
        $this->mailer()->assertSentEmailCount(2);

        foreach ($transport->get(\PHP_INT_MAX) as $envelope) {
            self::getContainer()->get(MessageBusInterface::class)->dispatch($envelope->with(new ReceivedStamp('async')));
            $transport->ack($envelope);
        }

        $this->assertCount(0, $transport->get());
        $this->mailer()->assertSentEmailCount(2);
    }

    public static function environmentProvider(): iterable
    {
        yield ['notifier'];
        yield ['notifier_bus_sync'];
        yield ['notifier_bus_async'];
    }

    /**
     * @test
     */
    public function notifications_keep_using_the_configured_notifier_bus(): void
    {
        if (Kernel::VERSION_ID < 60300) {
            $this->markTestSkipped('Notifier does not support configuring a custom message bus.');
        }

        self::bootKernel(['environment' => 'notifier_custom_bus']);

        self::getContainer()->get(NotifierInterface::class)->send(new EmailNotification(new Email1()), new Recipient('kevin@example.com'));
        self::getContainer()->get('mailer')->send(new Email1());

        $sent = self::getContainer()->get('messenger.transport.async')->getSent();
        $this->assertSame('notifier.bus', $sent[0]->last(BusNameStamp::class)->getBusName());
        $this->assertSame('mailer.bus', $sent[1]->last(BusNameStamp::class)->getBusName());
        $this->mailer()->assertSentEmailCount(2);
    }

    /**
     * @test
     */
    public function queued_notifications_are_visible_to_the_mailer_profiler(): void
    {
        self::bootKernel(['environment' => 'notifier_bus_async']);

        self::getContainer()->get(NotifierInterface::class)->send(new EmailNotification(new Email1()), new Recipient('kevin@example.com'));

        $collector = new MessageDataCollector(self::getContainer()->get('mailer.message_logger_listener'));
        $collector->collect(new Request(), new Response());
        SentEmails::fromEvents($collector->getEvents())->assertCount(1);
    }
}
