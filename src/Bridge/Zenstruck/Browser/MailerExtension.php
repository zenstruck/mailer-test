<?php

/*
 * This file is part of the zenstruck/mailer-test package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Mailer\Test\Bridge\Zenstruck\Browser;

use Zenstruck\Mailer\Test\SentEmailMixin;
use Zenstruck\Mailer\Test\SentEmails;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait MailerExtension
{
    use SentEmailMixin;

    private function sentEmails(): SentEmails
    {
        return (new MailerComponent($this))->sentEmails();
    }
}
