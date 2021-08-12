<?php

namespace Zenstruck\Mailer\Test;

use Symfony\Component\Mime\Email;
use Zenstruck\Assert;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @mixin Email
 */
class TestEmail
{
    private Email $email;

    final public function __construct(Email $email)
    {
        $this->email = $email;
    }

    final public function __call($name, $arguments)
    {
        return $this->email->{$name}(...$arguments);
    }

    final public function assertSubject(string $expected): self
    {
        Assert::that($this->email->getSubject())->is($expected);

        return $this;
    }

    final public function assertFrom(string $expectedEmail, string $expectedName = ''): self
    {
        foreach ($this->email->getFrom() as $address) {
            if ($expectedEmail !== $address->getAddress()) {
                continue;
            }

            Assert::that($address->getAddress())->is($expectedEmail);
            Assert::that($address->getName())->is($expectedName);

            return $this;
        }

        Assert::fail("Message does not have from [{$expectedEmail}]");
    }

    final public function assertTo(string $expectedEmail, string $expectedName = ''): self
    {
        foreach ($this->email->getTo() as $address) {
            if ($expectedEmail !== $address->getAddress()) {
                continue;
            }

            Assert::that($address->getAddress())->is($expectedEmail);
            Assert::that($address->getName())->is($expectedName);

            return $this;
        }

        Assert::fail("Message does not have to [{$expectedEmail}]");
    }

    final public function assertCc(string $expectedEmail, string $expectedName = ''): self
    {
        foreach ($this->email->getCc() as $address) {
            if ($expectedEmail !== $address->getAddress()) {
                continue;
            }

            Assert::that($address->getAddress())->is($expectedEmail);
            Assert::that($address->getName())->is($expectedName);

            return $this;
        }

        Assert::fail("Message does not have cc [{$expectedEmail}]");
    }

    final public function assertBcc(string $expectedEmail, string $expectedName = ''): self
    {
        foreach ($this->email->getBcc() as $address) {
            if ($expectedEmail !== $address->getAddress()) {
                continue;
            }

            Assert::that($address->getAddress())->is($expectedEmail);
            Assert::that($address->getName())->is($expectedName);

            return $this;
        }

        Assert::fail("Message does not have bcc [{$expectedEmail}]");
    }

    final public function assertReplyTo(string $expectedEmail, string $expectedName = ''): self
    {
        foreach ($this->email->getReplyTo() as $address) {
            if ($expectedEmail !== $address->getAddress()) {
                continue;
            }

            Assert::that($address->getAddress())->is($expectedEmail);
            Assert::that($address->getName())->is($expectedName);

            return $this;
        }

        Assert::fail("Message does not have reply-to [{$expectedEmail}]");
    }

    /**
     * Ensure both html and text contents contain the expected string.
     */
    final public function assertContains(string $expected): self
    {
        return $this
            ->assertHtmlContains($expected)
            ->assertTextContains($expected)
        ;
    }

    final public function assertHtmlContains(string $expected): self
    {
        Assert::that($this->email->getHtmlBody())
            ->contains($expected, 'The [text/html] part does not contain "{expected}".')
        ;

        return $this;
    }

    final public function assertTextContains(string $expected): self
    {
        Assert::that($this->email->getTextBody())
            ->contains($expected, 'The [text/plain] part does not contain "{expected}".')
        ;

        return $this;
    }

    final public function assertHasFile(string $expectedFilename, string $expectedContentType, string $expectedContents): self
    {
        foreach ($this->email->getAttachments() as $attachment) {
            if ($expectedFilename !== $attachment->getPreparedHeaders()->get('content-disposition')->getParameter('filename')) {
                continue;
            }

            Assert::that($attachment->getBody())->is($expectedContents);
            Assert::that($attachment->getPreparedHeaders()->get('content-type')->getBodyAsString())
                ->is($expectedContentType.'; name='.$expectedFilename)
            ;

            return $this;
        }

        Assert::fail("Message does not include file with filename [{$expectedFilename}]");
    }
}
