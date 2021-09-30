<?php

namespace Zenstruck\Mailer\Test;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mailer\Event\MessageEvents;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class MessageEventCollector implements EventSubscriberInterface
{
    private static ?MessageEvents $events = null;

    public function onMessage(MessageEvent $event): void
    {
        if (self::$events) {
            self::$events->add($event);
        }
    }

    public function mailer(): TestMailer
    {
        if (!self::$events) {
            throw new \LogicException('Cannot access mailer as email collection has not yet been started.');
        }

        return new TestMailer(self::$events);
    }

    public static function start(): void
    {
        self::$events = new MessageEvents();
    }

    public static function reset(): void
    {
        self::$events = null;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            MessageEvent::class => ['onMessage', -255],
        ];
    }
}
