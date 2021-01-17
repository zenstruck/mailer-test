<?php

namespace Zenstruck\Mailer\Test\Tests;

use PHPUnit\Framework\AssertionFailedError;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Mailer\Test\InteractsWithMailer;
use Zenstruck\Mailer\Test\TestEmail;
use Zenstruck\Mailer\Test\Tests\Fixture\Email1;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class InteractsWithMailerTest extends KernelTestCase
{
    use InteractsWithMailer;

    /**
     * @test
     * @dataProvider environmentProvider
     */
    public function can_assert_no_email_sent(string $environment): void
    {
        self::bootKernel(['environment' => $environment]);

        $this->mailer()->assertNoEmailSent();
    }

    /**
     * @test
     * @dataProvider environmentProvider
     */
    public function cannot_assert_email_if_none_sent(string $environment): void
    {
        self::bootKernel(['environment' => $environment]);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('No emails have been sent');

        $this->mailer()->assertEmailSentTo('kevin@example.com', 'email subject');
    }

    /**
     * @test
     * @dataProvider environmentProvider
     */
    public function can_assert_email_sent(string $environment): void
    {
        self::bootKernel(['environment' => $environment]);

        self::$container->get('mailer')->send(new Email1());

        $this->mailer()
            ->assertSentEmailCount(1)
            ->assertEmailSentTo('kevin@example.com', 'email subject')
            ->assertEmailSentTo('kevin@example.com', function(TestEmail $email) {
                $email
                    ->assertTo('kevin@example.com', 'Kevin')
                    ->assertFrom('webmaster@example.com')
                    ->assertCc('cc@example.com')
                    ->assertBcc('bcc@example.com')
                    ->assertReplyTo('reply@example.com')
                    ->assertHtmlContains('html body')
                    ->assertTextContains('text body')
                    ->assertContains('body')
                    ->assertHasFile('attachment.txt', 'text/plain', "attachment contents\n")
                ;

                // TestEmail can call underlying Symfony\Component\Mime\Email methods
                $this->assertSame('Kevin', $email->getTo()[0]->getName());
            })
        ;
    }

    /**
     * @test
     * @dataProvider environmentProvider
     */
    public function assert_email_sent_to_fail(string $environment): void
    {
        self::bootKernel(['environment' => $environment]);

        self::$container->get('mailer')->send(new Email1());

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Email sent, but "jim@example.com" is not among to-addresses: kevin@example.com');

        $this->mailer()->assertEmailSentTo('jim@example.com', 'subject');
    }

    public static function environmentProvider(): iterable
    {
        yield ['test'];
        yield ['bus_sync'];
        yield ['bus_async'];
    }

    /**
     * @test
     */
    public function kernel_must_be_booted(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('kernel must be booted');

        $this->mailer();
    }

    /**
     * @test
     */
    public function mailer_must_be_enabled(): void
    {
        self::bootKernel(['environment' => 'no_mailer']);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Mailer and/or profiling not enabled');

        $this->mailer();
    }

    /**
     * @test
     */
    public function profiler_must_be_enabled(): void
    {
        self::bootKernel(['environment' => 'no_profiler']);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Mailer and/or profiling not enabled');

        $this->mailer();
    }
}
