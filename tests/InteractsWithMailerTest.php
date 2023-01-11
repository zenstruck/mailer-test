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
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Mime\Email;
use Zenstruck\Mailer\Test\InteractsWithMailer;
use Zenstruck\Mailer\Test\TestEmail;
use Zenstruck\Mailer\Test\Tests\Fixture\CustomTestEmail;
use Zenstruck\Mailer\Test\Tests\Fixture\Email1;
use Zenstruck\Mailer\Test\ZenstruckMailerTestBundle;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class InteractsWithMailerTest extends KernelTestCase
{
    use EnvironmentProvider, InteractsWithMailer;

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

        self::getContainer()->get('mailer')->send(new Email1());

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
                    ->assertSubjectContains('sub')
                    ->assertHtmlContains('html body')
                    ->assertTextContains('text body')
                    ->assertContains('body')
                    ->assertHasFile('attachment.txt', 'text/plain', "attachment contents\n")
                    ->assertHasFile('name with space.txt', 'text/plain', "attachment contents\n")
                ;

                // TestEmail can call underlying Symfony\Component\Mime\Email methods
                $this->assertSame('Kevin', $email->getTo()[0]->getName());
            })
            ->assertEmailSentTo('kevin@example.com', function(Email $email) {
                // can type-hint raw email
                $this->assertSame('Kevin', $email->getTo()[0]->getName());
            })
            ->assertEmailSentTo('kevin@example.com', function($email) {
                // no typehint uses TestEmail
                $this->assertInstanceOf(TestEmail::class, $email);
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

        self::getContainer()->get('mailer')->send(new Email1());

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('No email was sent to "jim@example.com".');

        $this->mailer()->assertEmailSentTo('jim@example.com', 'subject');
    }

    /**
     * @test
     * @dataProvider environmentProvider
     */
    public function can_use_custom_test_email_class(string $environment): void
    {
        self::bootKernel(['environment' => $environment]);

        $email = new Email1();
        $email->getHeaders()->addTextHeader('X-PM-Tag', 'reset-password');

        self::getContainer()->get('mailer')->send($email);

        $this->mailer()->assertEmailSentTo('kevin@example.com', function(CustomTestEmail $email) {
            $email->assertHasPostmarkTag('reset-password');
        });
    }

    /**
     * @test
     * @dataProvider environmentProvider
     */
    public function can_access_sent_test_emails(string $environment): void
    {
        self::bootKernel(['environment' => $environment]);

        self::getContainer()->get('mailer')->send(new Email1());

        $this->assertInstanceOf(TestEmail::class, $this->mailer()->sentEmails()->all()[0]);
    }

    /**
     * @test
     * @dataProvider environmentProvider
     */
    public function can_access_sent_test_emails_with_custom_test_email_class(string $environment): void
    {
        self::bootKernel(['environment' => $environment]);

        self::getContainer()->get('mailer')->send(new Email1());

        $this->assertInstanceOf(CustomTestEmail::class, $this->mailer()->sentEmails()->all(CustomTestEmail::class)[0]);
    }

    /**
     * @test
     */
    public function sent_test_email_argument_must_be_an_instance_of_test_email(): void
    {
        self::getContainer()->get('mailer')->send(new Email1());

        $this->expectException(\InvalidArgumentException::class);

        $this->mailer()->sentEmails()->all(\stdClass::class);
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
    public function profiler_does_not_need_to_enabled(): void
    {
        self::bootKernel(['environment' => 'no_profiler']);

        self::getContainer()->get('mailer')->send(new Email1());

        $this->mailer()->assertSentEmailCount(1);
    }

    /**
     * @test
     * @dataProvider environmentProvider
     */
    public function emails_are_persisted_between_reboots(string $environment): void
    {
        self::bootKernel(['environment' => $environment]);

        $this->mailer()->assertNoEmailSent();

        self::getContainer()->get('mailer')->send(new Email1());

        $this->mailer()->assertSentEmailCount(1);

        self::ensureKernelShutdown();

        self::bootKernel(['environment' => $environment]);

        $this->mailer()->assertSentEmailCount(1);
    }

    /**
     * @test
     * @dataProvider environmentProvider
     */
    public function can_reset_collected_emails(string $environment): void
    {
        self::bootKernel(['environment' => $environment]);

        $this->mailer()->assertNoEmailSent();

        self::getContainer()->get('mailer')->send(new Email1());

        $this->mailer()
            ->assertSentEmailCount(1)
            ->reset()
            ->assertNoEmailSent()
        ;

        self::ensureKernelShutdown();

        self::bootKernel(['environment' => $environment]);

        self::getContainer()->get('mailer')->send(new Email1());

        $this->mailer()->assertSentEmailCount(1);
    }

    /**
     * @test
     */
    public function bundle_must_be_enabled(): void
    {
        self::bootKernel(['environment' => 'no_bundle']);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(\sprintf('Cannot access test mailer - is %s enabled in your test environment?', ZenstruckMailerTestBundle::class));

        $this->mailer();
    }
}
