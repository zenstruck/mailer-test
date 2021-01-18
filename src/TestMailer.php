<?php

namespace Zenstruck\Mailer\Test;

use PHPUnit\Framework\Assert;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mailer\Event\MessageEvents;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;
use Zenstruck\Callback;
use Zenstruck\Callback\Parameter;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class TestMailer
{
    private MessageEvents $events;

    public function __construct(MessageEvents $events)
    {
        $this->events = $events;
    }

    /**
     * @return Email[]
     */
    public function sentEmails(): array
    {
        $usingQueue = false;
        $events = $this->events->getEvents();

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

        return \array_filter(
            \array_map(static fn(MessageEvent $event) => $event->getMessage(), $events),
            static fn(RawMessage $message) => $message instanceof Email
        );
    }

    /**
     * @return TestEmail[]
     */
    public function sentTestEmails(string $testEmailClass = TestEmail::class): array
    {
        if (!\is_a($testEmailClass, TestEmail::class, true)) {
            throw new \InvalidArgumentException(\sprintf('$testEmailClass must be a class that\'s an instance of "%s".', TestEmail::class));
        }

        return \array_map(static fn(Email $email) => new $testEmailClass($email), $this->sentEmails());
    }

    public function assertNoEmailSent(): self
    {
        return $this->assertSentEmailCount(0);
    }

    public function assertSentEmailCount(int $count): self
    {
        Assert::assertCount($count, $this->sentEmails(), \sprintf('Expected %d emails to be sent, but %d emails were sent.', $count, \count($this->sentEmails())));

        return $this;
    }

    /**
     * @param callable|string $callback Takes an instance of the found Email as TestEmail - if string, assume subject
     */
    public function assertEmailSentTo(string $expectedTo, $callback): self
    {
        $emails = $this->sentEmails();

        if (0 === \count($emails)) {
            Assert::fail('No emails have been sent.');
        }

        if (!\is_callable($callback)) {
            $callback = static fn(TestEmail $message) => $message->assertSubject($callback);
        }

        $foundToAddresses = [];

        foreach ($emails as $email) {
            $toAddresses = \array_map(static fn(Address $address) => $address->getAddress(), $email->getTo());
            $foundToAddresses[] = $toAddresses;

            if (\in_array($expectedTo, $toAddresses, true)) {
                // address matches
                Callback::createFor($callback)->invoke(
                    Parameter::typed(TestEmail::class, Parameter::factory(fn(string $class) => new $class($email)))
                );

                return $this;
            }
        }

        Assert::fail(\sprintf('Email sent, but "%s" is not among to-addresses: %s', $expectedTo, \implode(', ', \array_merge(...$foundToAddresses))));
    }
}
