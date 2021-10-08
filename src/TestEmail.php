<?php

namespace Zenstruck\Mailer\Test;

use Symfony\Component\Mailer\Header\MetadataHeader;
use Symfony\Component\Mailer\Header\TagHeader;
use Symfony\Component\Mime\Email;
use Zenstruck\Assert;
use Zenstruck\Callback;
use Zenstruck\Callback\Parameter;

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

    /**
     * @template T
     *
     * @param class-string $class
     *
     * @return T
     */
    final public function as(string $class): self
    {
        if (self::class === $class) {
            return $this;
        }

        if (!\is_a($class, self::class, true)) {
            throw new \InvalidArgumentException(\sprintf('$class must be a class that\'s an instance of "%s".', self::class));
        }

        return new $class($this->inner());
    }

    /**
     * @param callable(TestEmail|Email):mixed $callback
     *
     * @return mixed
     */
    final public function call(callable $callback)
    {
        return Callback::createFor($callback)->invoke(Parameter::union(
            Parameter::untyped($this),
            Parameter::typed(Email::class, $this->inner()),
            Parameter::typed(self::class, Parameter::factory(fn(string $class) => $this->as($class)))
        ));
    }

    final public function inner(): Email
    {
        return $this->email;
    }

    /**
     * @return string|null The first {@see TagHeader} value found or null if none
     */
    final public function tag(): ?string
    {
        return $this->tags()[0] ?? null;
    }

    /**
     * @return string[] The {@see TagHeader} values
     */
    final public function tags(): array
    {
        if (!\class_exists(TagHeader::class)) {
            throw new \BadMethodCallException('Tags can only be used in symfony/mailer 5.1+.');
        }

        $tags = [];

        foreach ($this->getHeaders()->all() as $header) {
            if ($header instanceof TagHeader) {
                $tags[] = $header->getValue();
            }
        }

        return $tags;
    }

    /**
     * @return array<string, string> The {@see MetadataHeader} keys/values
     */
    final public function metadata(): array
    {
        if (!\class_exists(MetadataHeader::class)) {
            throw new \BadMethodCallException('Metadata can only be used in symfony/mailer 5.1+.');
        }

        $metadata = [];

        foreach ($this->getHeaders()->all() as $header) {
            if ($header instanceof MetadataHeader) {
                $metadata[$header->getKey()] = $header->getValue();
            }
        }

        return $metadata;
    }

    final public function assertSubject(string $expected): self
    {
        Assert::that($this->email->getSubject())->is($expected);

        return $this;
    }

    final public function assertSubjectContains(string $needle): self
    {
        Assert::that($this->email->getSubject())->contains($needle);

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

    final public function assertHasTag(string $expected): self
    {
        Assert::that($this->tags())
            ->isNotEmpty('No tags found.')
            ->contains($expected, 'Expected to have tag "{needle}".')
        ;

        return $this;
    }

    final public function assertHasMetadata(string $expectedKey, ?string $expectedValue = null): self
    {
        Assert::that($metadata = $this->metadata())->isNotEmpty('No metadata found.');
        Assert::that(\array_keys($metadata))->contains($expectedKey, 'Expected to have metadata key "{needle}".');

        if (null !== $expectedValue) {
            Assert::that($metadata[$expectedKey])->is($expectedValue, 'Expected metadata "{key}" to be "{expected}".', [
                'key' => $expectedKey,
            ]);
        }

        return $this;
    }
}
