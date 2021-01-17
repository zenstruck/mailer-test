<?php

namespace Zenstruck\Mailer\Test\Tests;

use PHPUnit\Framework\TestCase;
use Zenstruck\Mailer\Test\InteractsWithMailer;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class NonKernelTestCaseTest extends TestCase
{
    use InteractsWithMailer;

    /**
     * @test
     */
    public function must_extend_kernel_test_case(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('trait can only be used with');

        $this->mailer();
    }
}
