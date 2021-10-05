<?php

namespace Zenstruck\Mailer\Test;

use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mailer\Event\MessageEvents;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;
use Zenstruck\Assert;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class SentEmails implements \IteratorAggregate, \Countable
{
    /** @var Email[] */
    private array $emails;

    public function __construct(Email ...$emails)
    {
        $this->emails = $emails;
    }

    public static function fromEvents(MessageEvents $events): self
    {
        $usingQueue = false;
        $events = $events->getEvents();

        foreach ($events as $event) {
            if ($event->isQueued()) {
                $usingQueue = true;

                break;
            }
        }

        if ($usingQueue) {
            // if using queue, remove non queued messages to avoid duplicates
            $events = \array_filter($events, static fn(MessageEvent $event) => $event->isQueued());
        }

        // convert events to messages
        $messages = \array_map(static fn(MessageEvent $event) => $event->getMessage(), $events);

        // remove non Email messages
        $messages = \array_filter($messages, static fn(RawMessage $message) => $message instanceof Email);

        return new self(...$messages);
    }

    /**
     * @return Email[]
     */
    public function all(): array
    {
        return $this->emails;
    }

    public function assertCount(int $expected): self
    {
        Assert::that($this->emails)
            ->hasCount($expected, 'Expected {expected} emails to be sent, but {actual} emails were sent.')
        ;

        return $this;
    }

    public function assertNone(): self
    {
        return $this->assertCount(0);
    }

    /**
     * @return \Traversable|Email[]
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->emails);
    }

    public function count(): int
    {
        return \count($this->emails);
    }
}
