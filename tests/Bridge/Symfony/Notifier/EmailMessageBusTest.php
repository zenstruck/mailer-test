<?php

/*
 * This file is part of the zenstruck/mailer-test package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Mailer\Test\Tests\Bridge\Symfony\Notifier;

use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Mailer\Envelope as MailEnvelope;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mailer\Messenger\SendEmailMessage;
use Symfony\Component\Mailer\Transport\NullTransport;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use Zenstruck\Mailer\Test\Bridge\Symfony\Notifier\EmailMessageBus;
use Zenstruck\Mailer\Test\Tests\Fixture\Email1;

final class EmailMessageBusTest extends TestCase
{
    /**
     * @test
     */
    public function listeners_receive_clones_and_the_original_message_and_stamps_reach_the_bus(): void
    {
        $email = new Email1();
        $mailEnvelope = MailEnvelope::create($email);
        $message = new SendEmailMessage($email, $mailEnvelope);
        $stamp = new DelayStamp(10);
        $envelope = new Envelope($message, [$stamp]);
        $result = new Envelope(new \stdClass());
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(MessageEvent::class, function(MessageEvent $event) use ($email, $mailEnvelope): void {
            $this->assertTrue($event->isQueued());
            $this->assertNotSame($email, $event->getMessage());
            $this->assertNotSame($mailEnvelope, $event->getEnvelope());
            $this->assertSame('null://', $event->getTransport());
            $event->getMessage()->html('rendered notification');
        });
        $inner = $this->createMock(MessageBusInterface::class);
        $inner->expects($this->once())->method('dispatch')->with($this->callback(function(Envelope $forwarded) use ($email, $stamp): bool {
            $this->assertSame($email, $forwarded->getMessage()->getMessage());
            $this->assertSame($stamp, $forwarded->last(DelayStamp::class));

            return true;
        }), [])->willReturn($result);
        $bus = new EmailMessageBus($inner, $dispatcher, new NullTransport());

        $this->assertSame($result, $bus->dispatch($envelope));
        $this->assertSame('html body', $email->getHtmlBody());
        $this->assertSame($stamp, $envelope->last(DelayStamp::class));
    }

    /**
     * @test
     */
    public function an_existing_queued_event_is_not_dispatched_again(): void
    {
        if (!\method_exists(MessageEvent::class, 'addStamp')) {
            $this->markTestSkipped('Mailer does not support event stamps.');
        }

        $dispatcher = new EventDispatcher();
        $events = [];
        $dispatcher->addListener(MessageEvent::class, static function(MessageEvent $event) use (&$events): void {
            $events[] = $event;
        });
        $inner = $this->createMock(MessageBusInterface::class);
        $inner->expects($this->once())->method('dispatch')->willReturnArgument(0);
        $bus = new EmailMessageBus($inner, $dispatcher, new NullTransport());
        $dispatcher->addListener(MessageEvent::class, [$bus, 'markCollected']);
        $email = new Email1();
        $event = new MessageEvent(clone $email, MailEnvelope::create($email), 'null://', true);
        $dispatcher->dispatch($event);

        $bus->dispatch(new SendEmailMessage($email), $event->getStamps());

        $this->assertCount(1, $events);
    }

    /**
     * @test
     */
    public function stamps_added_by_event_listeners_reach_the_bus(): void
    {
        if (!\method_exists(MessageEvent::class, 'addStamp')) {
            $this->markTestSkipped('Mailer does not support event stamps.');
        }

        $dispatcher = new EventDispatcher();
        $stamp = new DelayStamp(20);
        $dispatcher->addListener(MessageEvent::class, static fn(MessageEvent $event) => $event->addStamp($stamp));
        $inner = $this->createMock(MessageBusInterface::class);
        $inner->expects($this->once())->method('dispatch')->with($this->isInstanceOf(Envelope::class), [$stamp])->willReturnArgument(0);
        $bus = new EmailMessageBus($inner, $dispatcher, new NullTransport());

        $bus->dispatch(new SendEmailMessage(new Email1()));
    }

    /**
     * @test
     */
    public function rejected_emails_are_not_queued(): void
    {
        if (!\method_exists(MessageEvent::class, 'reject')) {
            $this->markTestSkipped('Mailer does not support rejecting events.');
        }

        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(MessageEvent::class, static fn(MessageEvent $event) => $event->reject());
        $inner = $this->createMock(MessageBusInterface::class);
        $inner->expects($this->never())->method('dispatch');
        $bus = new EmailMessageBus($inner, $dispatcher, new NullTransport());
        $message = new SendEmailMessage(new Email1());

        $this->assertSame($message->getMessage(), $bus->dispatch($message)->getMessage()->getMessage());
    }

    /**
     * @test
     */
    public function collection_stamps_are_not_serialized_for_transport(): void
    {
        if (!\method_exists(MessageEvent::class, 'addStamp')) {
            $this->markTestSkipped('Mailer does not support event stamps.');
        }

        $dispatcher = new EventDispatcher();
        $inner = $this->createMock(MessageBusInterface::class);
        $inner->method('dispatch')->willReturnArgument(0);
        $bus = new EmailMessageBus($inner, $dispatcher, new NullTransport());
        $dispatcher->addListener(MessageEvent::class, [$bus, 'markCollected']);
        $email = new Email1();
        $event = new MessageEvent($email, MailEnvelope::create($email), 'null://', true);
        $dispatcher->dispatch($event);
        $serializer = new PhpSerializer();
        $envelope = new Envelope(new SendEmailMessage(new Email1()), $event->getStamps());

        $this->assertNotEmpty($envelope->all());
        $this->assertSame([], $serializer->decode($serializer->encode($envelope))->all());
    }

    /**
     * @test
     */
    public function non_email_messages_pass_through_without_dispatching_mailer_events(): void
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(MessageEvent::class, fn() => $this->fail('Unexpected mailer event.'));
        $envelope = new Envelope(new \stdClass());
        $inner = $this->createMock(MessageBusInterface::class);
        $inner->expects($this->once())->method('dispatch')->with($envelope)->willReturnArgument(0);
        $bus = new EmailMessageBus($inner, $dispatcher, new NullTransport());

        $this->assertSame($envelope->getMessage(), $bus->dispatch($envelope)->getMessage());
    }
}
