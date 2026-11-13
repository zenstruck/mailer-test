<?php

/*
 * This file is part of the zenstruck/mailer-test package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Mailer\Test\Bridge\Symfony\Notifier;

use Symfony\Component\Mailer\Envelope as MailEnvelope;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mailer\Messenger\SendEmailMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final class EmailMessageBus implements MessageBusInterface
{
    public function __construct(
        private MessageBusInterface $bus,
        private EventDispatcherInterface $dispatcher,
        private TransportInterface $transport,
    ) {
    }

    public function dispatch(object $message, array $stamps = []): Envelope
    {
        $envelope = Envelope::wrap($message, $stamps);
        $email = $envelope->getMessage();

        if (!$email instanceof SendEmailMessage) {
            return $this->bus->dispatch($envelope);
        }

        if ($envelope->last(CollectedEmailStamp::class)) {
            return $this->bus->dispatch($envelope);
        }

        $event = $this->dispatchEvent($email);

        // @phpstan-ignore function.alreadyNarrowedType (Older Mailer versions do not support rejecting events.)
        if (\method_exists($event, 'isRejected') && $event->isRejected()) {
            return $envelope;
        }

        // @phpstan-ignore function.alreadyNarrowedType (Older Mailer versions do not support event stamps.)
        return $this->bus->dispatch($envelope, \method_exists($event, 'getStamps') ? $event->getStamps() : []);
    }

    public function markCollected(MessageEvent $event): void
    {
        // @phpstan-ignore function.alreadyNarrowedType (Older Mailer versions do not support event stamps.)
        if ($event->isQueued() && \method_exists($event, 'addStamp')) {
            $event->addStamp(new CollectedEmailStamp());
        }
    }

    private function dispatchEvent(SendEmailMessage $message): MessageEvent
    {
        // Listeners run again when the original email is consumed.
        $email = clone $message->getMessage();
        $envelope = $message->getEnvelope();
        $envelope = null !== $envelope ? clone $envelope : MailEnvelope::create($email);
        $event = new MessageEvent($email, $envelope, (string) $this->transport, true);
        $this->dispatcher->dispatch($event);

        return $event;
    }
}
