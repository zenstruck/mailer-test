<?php

/*
 * This file is part of the zenstruck/mailer-test package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Mailer\Test;

use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mailer\Event\MessageEvents;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Message;
use Symfony\Component\Mime\Part\AbstractMultipartPart;
use Symfony\Component\Mime\Part\AbstractPart;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\TextPart;
use Symfony\Component\Mime\RawMessage;
use Zenstruck\Assert;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @implements \IteratorAggregate<TestEmail>
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
        $messages = \array_filter($messages, static fn(RawMessage $message) => $message instanceof Message);

        // convert to TestEmails
        $messages = \array_map([self::class, 'convertMessageToEmail'], $messages);

        return new self(...$messages);
    }

    /**
     * Get the first email in the collection - fail if none.
     *
     * @template T of TestEmail
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    public function first(string $class = TestEmail::class): TestEmail
    {
        return $this->ensureSome()->all($class)[\array_key_first($this->emails)];
    }

    /**
     * Get the last email in the collection - fail if none.
     *
     * @template T of TestEmail
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    public function last(string $class = TestEmail::class): TestEmail
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
        return $this->where(fn(Email $email) => \str_contains((string) $email->getSubject(), $needle));
    }

    public function whereTag(string $tag): self
    {
        return $this->where(fn(TestEmail $email) => $email->tag() === $tag);
    }

    public function whereFrom(string $email): self
    {
        return $this->where(fn(Email $message) => self::emailsContain($message->getFrom(), $email));
    }

    public function whereTo(string $email): self
    {
        return $this->where(fn(Email $message) => self::emailsContain($message->getTo(), $email));
    }

    public function whereCc(string $email): self
    {
        return $this->where(fn(Email $message) => self::emailsContain($message->getCc(), $email));
    }

    public function whereBcc(string $email): self
    {
        return $this->where(fn(Email $message) => self::emailsContain($message->getBcc(), $email));
    }

    public function whereReplyTo(string $email): self
    {
        return $this->where(fn(Email $message) => self::emailsContain($message->getReplyTo(), $email));
    }

    public function dump(): self
    {
        \call_user_func(\function_exists('dump') ? 'dump' : 'var_dump', $this->raw());

        return $this;
    }

    /**
     * @return never
     */
    public function dd(): void
    {
        $this->dump();
        exit(1);
    }

    /**
     * @template T of TestEmail
     *
     * @param class-string<T> $class
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

    /**
     * @param array<string|int,mixed> $context
     */
    public function ensureSome(string $message = 'No emails.', array $context = []): self
    {
        if (0 === \count($this->emails)) {
            Assert::fail($message, $context);
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

    /**
     * @param Address[] $addresses
     */
    private static function emailsContain(array $addresses, string $needle): bool
    {
        foreach ($addresses as $address) {
            if ($address->getAddress() === $needle) {
                return true;
            }
        }

        return false;
    }

    private static function convertMessageToEmail(Message $message): TestEmail
    {
        if ($message instanceof Email) {
            return new TestEmail($message);
        }

        $email = new Email($message->getHeaders());

        if (!$body = $message->getBody()) {
            return new TestEmail($email);
        }

        foreach (self::textParts($body) as $part) {
            match (true) {
                $part instanceof DataPart => \method_exists($email, 'addPart') ? $email->addPart($part) : $email->attachPart($part),
                'plain' === $part->getMediaSubtype() => $email->text($part->getBody()),
                'html' === $part->getMediaSubtype() => $email->html($part->getBody()),
                default => null,
            };
        }

        return new TestEmail($email);
    }

    /**
     * @return TextPart[]
     */
    private static function textParts(AbstractPart $part): iterable
    {
        if ($part instanceof TextPart) {
            yield $part;
        }

        if (!$part instanceof AbstractMultipartPart) {
            return;
        }

        foreach ($part->getParts() as $subPart) {
            yield from self::textParts($subPart);
        }
    }
}
