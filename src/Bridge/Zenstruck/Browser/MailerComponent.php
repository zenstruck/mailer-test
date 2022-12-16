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

use Symfony\Component\Mailer\DataCollector\MessageDataCollector;
use Zenstruck\Browser\Component;
use Zenstruck\Browser\KernelBrowser;
use Zenstruck\Mailer\Test\SentEmailMixin;
use Zenstruck\Mailer\Test\SentEmails;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class MailerComponent extends Component
{
    use SentEmailMixin;

    public function sentEmails(): SentEmails
    {
        $browser = $this->browser();

        if (!$browser instanceof KernelBrowser) {
            throw new \RuntimeException(\sprintf('The "Mailer" component requires the browser be a %s.', KernelBrowser::class));
        }

        if (!$browser->profile()->hasCollector('mailer')) {
            throw new \RuntimeException('The profiler does not include the "mailer" collector. Is symfony/mailer installed?');
        }

        /** @var MessageDataCollector $collector */
        $collector = $browser->profile()->getCollector('mailer');

        return SentEmails::fromEvents($collector->getEvents());
    }
}
