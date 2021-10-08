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
    /** @var TestEmail[] */
    private array $emails;

    public function __construct(TestEmail ...$emails)
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

        // convert to TestEmails
        $messages = \array_map(static fn(Email $email) => new TestEmail($email), $messages);

        return new self(...$messages);
    }

    /**
     * Get the first email in the collection - fail if none.
     *
     * @template T
     *
     * @param class-string $class
     *
     * @return T
     */
    public function first(string $class = TestEmail::class): self
    {
        return $this->ensureSome()->all($class)[\array_key_first($this->emails)];
    }

    /**
     * Get the last email in the collection - fail if none.
     *
     * @template T
     *
     * @param class-string $class
     *
     * @return T
     */
    public function last(string $class = TestEmail::class): self
    {
        return $this->ensureSome()->all($class)[\array_key_last($this->emails)];
    }

    /**
     * Perform callback on each email.
     *
     * @param callable(TestEmail|Email):void $callback {@see TestEmail::call()}
     */
    public function each(callable $callback): self
    {
        foreach ($this as $email) {
            $email->call($callback);
        }

        return $this;
    }

    /**
     * Filter collection.
     *
     * @param callable(TestEmail|Email):bool $filter {@see TestEmail::call()}
     */
    public function where(callable $filter): self
    {
        return new self(...\array_filter($this->emails, static fn(TestEmail $email) => $email->call($filter)));
    }

    public function whereSubject(string $subject): self
    {
        return $this->where(fn(Email $email) => $email->getSubject() === $subject);
    }

    public function whereSubjectContains(string $needle): self
    {
        return $this->where(fn(Email $email) => str_contains($email->getSubject(), $needle));
    }

    public function whereTag(string $tag): self
    {
        return $this->where(fn(TestEmail $email) => $email->tag() === $tag);
    }

    public function dump(): self
    {
        \call_user_func(\function_exists('dump') ? 'dump' : 'var_dump', $this->raw());

        return $this;
    }

    /**
     * @return never-return
     */
    public function dd(): void
    {
        $this->dump();
        exit(1);
    }

    /**
     * @template T
     *
     * @param class-string $class
     *
     * @return TestEmail[]|T[]
     */
    public function all(string $class = TestEmail::class): array
    {
        return \array_map(static fn(TestEmail $email) => $email->as($class), $this->emails);
    }

    /**
     * @return Email[]
     */
    public function raw(): array
    {
        return \array_map(static fn(TestEmail $email) => $email->inner(), $this->emails);
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

    public function ensureSome(): self
    {
        if (0 === \count($this->emails)) {
            Assert::fail('No emails have been sent.');
        }

        return $this;
    }

    /**
     * @return \Traversable|TestEmail[]
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
