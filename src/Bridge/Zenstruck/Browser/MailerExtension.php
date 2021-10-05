<?php

namespace Zenstruck\Mailer\Test\Bridge\Zenstruck\Browser;

use Zenstruck\Mailer\Test\SentEmailMixin;
use Zenstruck\Mailer\Test\SentEmails;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait MailerExtension
{
    use SentEmailMixin;

    public function sentEmails(): SentEmails
    {
        return (new MailerComponent($this))->sentEmails();
    }
}
