<?php

namespace Zenstruck\Mailer\Test;

use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mailer\Event\MessageEvents;
use Symfony\Component\Mime\Email;

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
        if (!self::$events) {
            throw new \LogicException('Cannot access sent emails as email collection has not yet been started.');
        }

        return SentEmails::fromEvents(self::$events);
    }

    /**
     * @return TestEmail[]
     */
    public function sentTestEmails(string $testEmailClass = TestEmail::class): array
    {
        // todo deprecate/remove

        if (!\is_a($testEmailClass, TestEmail::class, true)) {
            throw new \InvalidArgumentException(\sprintf('$testEmailClass must be a class that\'s an instance of "%s".', TestEmail::class));
        }

        return \array_map(static fn(Email $email) => new $testEmailClass($email), $this->sentEmails()->all());
    }
}
