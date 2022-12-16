<?php

/*
 * This file is part of the zenstruck/mailer-test package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
