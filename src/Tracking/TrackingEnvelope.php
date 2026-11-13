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
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\RawMessage;

/**
 * @internal
 */
final class TrackingEnvelope extends Envelope
{
    private string $identifier;

    public function __construct(private RawMessage $message, private ?Envelope $envelope = null, ?string $identifier = null)
    {
        $this->identifier = $identifier ?? \bin2hex(\random_bytes(16));
    }

    public function __clone()
    {
        if (null !== $this->envelope) {
            $this->envelope = clone $this->envelope;
        }
    }

    public function forMessage(RawMessage $message, ?Envelope $envelope): self
    {
        return new self($message, $envelope, $this->identifier);
    }

    public function bind(RawMessage $message): void
    {
        $this->message = $message;
    }

    public function getSender(): Address
    {
        $envelope = $this->envelope ?? Envelope::create($this->message);

        return $envelope->getSender();
    }

    public function setSender(Address $sender): void
    {
        $this->envelope ??= Envelope::create($this->message);
        $this->envelope->setSender($sender);
    }

    public function getRecipients(): array
    {
        $envelope = $this->envelope ?? Envelope::create($this->message);

        return $envelope->getRecipients();
    }

    public function setRecipients(array $recipients): void
    {
        $this->envelope ??= Envelope::create($this->message);
        $this->envelope->setRecipients($recipients);
    }

    /**
     * @param array<string, true> $collected
     */
    public function collectOnce(array &$collected): bool
    {
        if (isset($collected[$this->identifier])) {
            return false;
        }

        $collected[$this->identifier] = true;

        return true;
    }
}
