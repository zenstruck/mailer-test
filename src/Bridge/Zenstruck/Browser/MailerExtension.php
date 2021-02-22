<?php

namespace Zenstruck\Mailer\Test\Bridge\Zenstruck\Browser;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait MailerExtension
{
    /**
     * @see TestMailer::assertNoEmailSent()
     *
     * @return static
     */
    public function assertNoEmailSent(): self
    {
        return $this->use(function(MailerComponent $component) {
            $component->assertNoEmailSent();
        });
    }

    /**
     * @see TestMailer::assertSentEmailCount()
     *
     * @return static
     */
    public function assertSentEmailCount(int $count): self
    {
        return $this->use(function(MailerComponent $component) use ($count) {
            $component->assertSentEmailCount($count);
        });
    }

    /**
     * @see TestMailer::assertEmailSentTo()
     *
     * @return static
     */
    public function assertEmailSentTo(string $expectedTo, $callback): self
    {
        return $this->use(function(MailerComponent $component) use ($expectedTo, $callback) {
            $component->assertEmailSentTo($expectedTo, $callback);
        });
    }
}
