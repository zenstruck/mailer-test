<?php

namespace Zenstruck\Mailer\Test\Tests\Fixture;

use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Email1 extends Email
{
    public function __construct()
    {
        parent::__construct();

        $this
            ->from('webmaster@example.com')
            ->to(new Address('kevin@example.com', 'Kevin'))
            ->cc('cc@example.com')
            ->bcc('bcc@example.com')
            ->replyTo('reply@example.com')
            ->attachFromPath(__DIR__.'/files/attachment.txt')
            ->subject('email subject')
            ->html('html body')
            ->text('text body')
        ;
    }
}
