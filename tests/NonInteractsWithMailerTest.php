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
        self::getContainer()->get('mailer')->send(new Email1());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot access sent emails as email collection has not yet been started.');

        self::getContainer()->get('zenstruck_mailer_test.mailer')->sentEmails();
    }

    /**
     * @test
     */
    public function cannot_reset_if_collection_not_yet_started(): void
    {
        self::getContainer()->get('mailer')->send(new Email1());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot access sent emails as email collection has not yet been started.');

        self::getContainer()->get('zenstruck_mailer_test.mailer')->reset();
    }
}
