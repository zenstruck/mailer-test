<?php

/*
 * This file is part of the zenstruck/mailer-test package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Mailer\Test\Tests\Tracking;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Zenstruck\Mailer\Test\Tracking\EmailEventTracker;
use Zenstruck\Mailer\Test\Tracking\TrackingEnvelope;

final class TrackingEnvelopeTest extends TestCase
{
    /**
     * @test
     */
    public function sender_and_recipients_are_resolved_after_listeners_update_the_cloned_message(): void
    {
        $original = new Email();
        $envelope = new TrackingEnvelope($original);
        $copy = clone $original;
        $event = new MessageEvent($copy, clone $envelope, 'null://', true);
        $tracker = new EmailEventTracker();
        $tracker->prepare($event);
        $copy->from('from@example.com')->to('to@example.com');

        $this->assertSame('from@example.com', $event->getEnvelope()->getSender()->getAddress());
        $this->assertSame('to@example.com', $event->getEnvelope()->getRecipients()[0]->getAddress());
        $this->assertSame([], $original->getFrom());
        $this->assertSame([], $original->getTo());
    }

    /**
     * @test
     */
    public function explicit_envelopes_are_cloned_and_remain_independent_of_message_headers(): void
    {
        $email = (new Email())->from('from@example.com')->to('to@example.com');
        $original = new Envelope(new Address('bounce@example.com'), [new Address('override@example.com')]);
        $envelope = clone new TrackingEnvelope($email, $original);

        $this->assertSame('bounce@example.com', $envelope->getSender()->getAddress());
        $this->assertSame('override@example.com', $envelope->getRecipients()[0]->getAddress());

        $envelope->setSender(new Address('changed@example.com'));
        $envelope->setRecipients([new Address('changed@example.com')]);

        $this->assertSame('bounce@example.com', $original->getSender()->getAddress());
        $this->assertSame('override@example.com', $original->getRecipients()[0]->getAddress());
        $this->assertSame('changed@example.com', $envelope->getSender()->getAddress());
        $this->assertSame('changed@example.com', $envelope->getRecipients()[0]->getAddress());
    }
}
