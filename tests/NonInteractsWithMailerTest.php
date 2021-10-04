<?php

namespace Zenstruck\Mailer\Test\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Mailer\Test\Tests\Fixture\Email1;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class NonInteractsWithMailerTest extends KernelTestCase
{
    /**
     * @test
     */
    public function ensure_emails_are_not_collected(): void
    {
        self::bootKernel();

        self::$container->get('mailer')->send(new Email1());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot access mailer as email collection has not yet been started.');

        self::$container->get('zenstruck_mailer_test.event_collector')->mailer();
    }
}
