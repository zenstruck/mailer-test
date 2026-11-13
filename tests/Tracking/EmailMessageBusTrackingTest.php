<?php

/*
 * This file is part of the zenstruck/mailer-test package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Mailer\Test\Tests\Tracking;

use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Mailer\Envelope as MailEnvelope;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mailer\Event\MessageEvents;
use Symfony\Component\Mailer\Messenger\SendEmailMessage;
use Symfony\Component\Mailer\Transport\NullTransport;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use Zenstruck\Mailer\Test\Bridge\Symfony\Notifier\EmailMessageBus;
use Zenstruck\Mailer\Test\SentEmails;
use Zenstruck\Mailer\Test\Tests\Fixture\Email1;
use Zenstruck\Mailer\Test\Tracking\EmailEventTracker;

final class EmailMessageBusTrackingTest extends TestCase
{
    /**
     * @test
     */
    public function preexisting_queued_events_keep_their_send_identity_when_forwarded(): void
    {
        if (!\method_exists(MessageEvent::class, 'addStamp')) {
            $this->markTestSkipped('Mailer does not support event stamps.');
        }

        $dispatcher = new EventDispatcher();
        $tracker = new EmailEventTracker();
        $dispatcher->addListener(MessageEvent::class, [$tracker, 'prepare'], \PHP_INT_MAX);
        $dispatcher->addListener(MessageEvent::class, [$tracker, 'finish'], \PHP_INT_MIN);
        $inner = $this->createMock(MessageBusInterface::class);
        $inner->expects($this->once())->method('dispatch')->willReturnArgument(0);
        $bus = new EmailMessageBus($inner, $dispatcher, new NullTransport());
        $dispatcher->addListener(MessageEvent::class, [$bus, 'markCollected']);
        $events = new MessageEvents();
        $dispatcher->addListener(MessageEvent::class, [$events, 'add']);
        $email = new Email1();
        $event = new MessageEvent(clone $email, MailEnvelope::create($email), 'null://', true);
        $dispatcher->dispatch($event);

        $queued = $bus->dispatch(new SendEmailMessage($email), $event->getStamps());
        $serializer = new PhpSerializer();
        $received = $serializer->decode($serializer->encode($queued));
        $message = $received->getMessage();
        (new NullTransport($dispatcher))->send($message->getMessage(), $message->getEnvelope());

        $this->assertCount(2, $events->getEvents());
        SentEmails::fromEvents($events)->assertCount(1);
    }

    /**
     * @test
     */
    public function non_email_messages_are_forwarded_with_their_stamps(): void
    {
        $message = new \stdClass();
        $stamp = new DelayStamp(10);
        $inner = $this->createMock(MessageBusInterface::class);
        $inner->expects($this->once())->method('dispatch')->with($this->callback(function(Envelope $envelope) use ($message, $stamp): bool {
            $this->assertSame($message, $envelope->getMessage());
            $this->assertSame($stamp, $envelope->last($stamp::class));

            return true;
        }))->willReturnArgument(0);

        (new EmailMessageBus($inner, new EventDispatcher(), new NullTransport()))->dispatch($message, [$stamp]);
    }
}
