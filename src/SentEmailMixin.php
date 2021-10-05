<?php

namespace Zenstruck\Mailer\Test;

use Symfony\Component\Mime\Address;
use Zenstruck\Assert;
use Zenstruck\Callback;
use Zenstruck\Callback\Parameter;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait SentEmailMixin
{
    /**
     * @return static
     */
    public function assertNoEmailSent(): self
    {
        $this->sentEmails()->assertNone();

        return $this;
    }

    /**
     * @return static
     */
    public function assertSentEmailCount(int $expected): self
    {
        $this->sentEmails()->assertCount($expected);

        return $this;
    }

    /**
     * @param callable|string $callback Takes an instance of the found Email as TestEmail - if string, assume subject
     *
     * @return static
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

    abstract public function sentEmails(): SentEmails;
}
