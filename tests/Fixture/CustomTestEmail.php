<?php

namespace Zenstruck\Mailer\Test\Tests\Fixture;

use PHPUnit\Framework\Assert;
use Zenstruck\Mailer\Test\TestEmail;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class CustomTestEmail extends TestEmail
{
    public function assertHasPostmarkTag(string $expected): self
    {
        Assert::assertTrue($this->getHeaders()->has('X-PM-Tag'));
        Assert::assertSame($expected, $this->getHeaders()->get('X-PM-Tag')->getBodyAsString());

        return $this;
    }
}
