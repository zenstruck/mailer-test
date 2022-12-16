<?php

/*
 * This file is part of the zenstruck/mailer-test package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Mailer\Test\Tests;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Header\TagHeader;
use Symfony\Component\Mime\Email;
use Zenstruck\Assert;
use Zenstruck\Mailer\Test\TestEmail;
use Zenstruck\Mailer\Test\Tests\Fixture\Email1;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class TestEmailTest extends TestCase
{
    /**
     * @test
     */
    public function can_access_tags(): void
    {
        $this->requiresMailer51();

        $noTags = new TestEmail(new Email());

        $this->assertNull($noTags->tag());
        $this->assertSame([], $noTags->tags());

        $withTags = new TestEmail(new Email1());

        $this->assertSame('foo', $withTags->tag());
        $this->assertSame(['foo', 'bar'], $withTags->tags());
    }

    /**
     * @test
     */
    public function can_access_metadata(): void
    {
        $this->requiresMailer51();

        $noMetadata = new TestEmail(new Email());

        $this->assertSame([], $noMetadata->metadata());

        $withMetadata = new TestEmail(new Email1());

        $this->assertSame(['color' => 'blue', 'id' => '5'], $withMetadata->metadata());
    }

    /**
     * @test
     */
    public function can_assert_has_tag(): void
    {
        $this->requiresMailer51();

        $withTags = new TestEmail(new Email1());

        $withTags->assertHasTag('foo');
        $withTags->assertHasTag('bar');

        Assert::that(fn() => $withTags->assertHasTag('baz'))->throws(
            AssertionFailedError::class, 'Expected to have tag "baz".'
        );
        Assert::that(fn() => (new TestEmail(new Email()))->assertHasTag('foo'))->throws(
            AssertionFailedError::class, 'No tags found.'
        );
    }

    /**
     * @test
     */
    public function can_assert_has_metadata(): void
    {
        $this->requiresMailer51();

        $withMetadata = new TestEmail(new Email1());

        $withMetadata->assertHasMetadata('color');
        $withMetadata->assertHasMetadata('color', 'blue');

        Assert::that(fn() => $withMetadata->assertHasMetadata('color', 'red'))->throws(
            AssertionFailedError::class, 'Expected metadata "color" to be "red".'
        );
        Assert::that(fn() => $withMetadata->assertHasMetadata('foo'))->throws(
            AssertionFailedError::class, 'Expected to have metadata key "foo"'
        );
        Assert::that(fn() => (new TestEmail(new Email()))->assertHasMetadata('foo'))->throws(
            AssertionFailedError::class, 'No metadata found.'
        );
    }

    private function requiresMailer51(): void
    {
        if (!\class_exists(TagHeader::class)) {
            $this->markTestSkipped('Requires symfony/mailer 5.1+');
        }
    }
}
