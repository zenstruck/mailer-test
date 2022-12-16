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
     * @param callable|string $callback callable: {@see TestEmail::call()}
     *                                  string: subject
     *
     * @return static
     */
    public function assertEmailSentTo(string $expectedTo, $callback): self
    {
        if (!\is_callable($callback)) {
            $callback = static fn(TestEmail $message) => $message->assertSubject($callback);
        }

        $this->sentEmails()
            ->ensureSome('No emails have been sent.')
            ->whereTo($expectedTo)
            ->ensureSome('No email was sent to "{expected}".', ['expected' => $expectedTo])
            ->first()
            ->call($callback)
        ;

        return $this;
    }

    abstract public function sentEmails(): SentEmails;
}
