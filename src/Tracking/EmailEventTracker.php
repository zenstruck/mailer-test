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

use Symfony\Component\Mailer\Event\MessageEvent;

/**
 * @internal
 */
final class EmailEventTracker
{
    /** @var \WeakMap<MessageEvent, TrackingEnvelope> */
    private \WeakMap $envelopes;

    public function __construct()
    {
        $this->envelopes = new \WeakMap();
    }

    public function prepare(MessageEvent $event): void
    {
        $envelope = $event->getEnvelope();

        if (!$envelope instanceof TrackingEnvelope) {
            $envelope = new TrackingEnvelope($event->getMessage(), $envelope);
            $event->setEnvelope($envelope);
        }

        $envelope->bind($event->getMessage());
        $this->envelopes[$event] = $envelope;
    }

    public function finish(MessageEvent $event): void
    {
        $envelope = $this->envelopes[$event];

        if ($event->getEnvelope() !== $envelope) {
            $event->setEnvelope($envelope->forMessage($event->getMessage(), $event->getEnvelope()));
        }
    }
}
