<?php

namespace Zenstruck\Mailer\Test;

use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mailer\Event\MessageEvents;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class TestMailer
{
    use SentEmailMixin;

    private static ?MessageEvents $events = null;

    /**
     * @internal
     */
    public static function start(): void
    {
        self::$events = new MessageEvents();
    }

    /**
     * @internal
     */
    public static function stop(): void
    {
        self::$events = null;
    }

    /**
     * @internal
     */
    public static function add(MessageEvent $event): void
    {
        if (self::$events) {
            self::$events->add($event);
        }
    }

    public function sentEmails(): SentEmails
    {
        self::ensureStarted();

        return SentEmails::fromEvents(self::$events);
    }

    /**
     * Reset the collected emails.
     */
    public function reset(): self
    {
        self::ensureStarted();
        self::start();

        return $this;
    }

    private static function ensureStarted(): void
    {
        if (!self::$events) {
            throw new \LogicException('Cannot access sent emails as email collection has not yet been started.');
        }
    }
}
