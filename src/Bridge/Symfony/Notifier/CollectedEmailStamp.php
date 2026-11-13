<?php

/*
 * This file is part of the zenstruck/mailer-test package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Mailer\Test\Bridge\Symfony\Notifier;

use Symfony\Component\Mailer\Messenger\SendEmailMessage;
use Symfony\Component\Messenger\Stamp\NonSendableStampInterface;
use Zenstruck\Mailer\Test\Tracking\TrackingEnvelope;

/**
 * @internal
 */
final class CollectedEmailStamp implements NonSendableStampInterface
{
    public function __construct(private ?TrackingEnvelope $envelope = null)
    {
    }

    public function envelope(SendEmailMessage $message): TrackingEnvelope
    {
        return $this->envelope
            ? $this->envelope->forMessage($message->getMessage(), $message->getEnvelope())
            : new TrackingEnvelope($message->getMessage(), $message->getEnvelope());
    }
}
