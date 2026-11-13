<?php

/*
 * This file is part of the zenstruck/mailer-test package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Mailer\Test\Tracking;

use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\RawMessage;

/**
 * @internal
 */
final class TrackingMailer implements MailerInterface
{
    public function __construct(private MailerInterface $mailer)
    {
    }

    public function send(RawMessage $message, ?Envelope $envelope = null): void
    {
        $this->mailer->send($message, new TrackingEnvelope($message, $envelope));
    }
}
