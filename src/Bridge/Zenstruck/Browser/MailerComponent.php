<?php

namespace Zenstruck\Mailer\Test\Bridge\Zenstruck\Browser;

use Zenstruck\Browser\BrowserKitBrowser;
use Zenstruck\Browser\Component;
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

        if (!$browser instanceof BrowserKitBrowser) {
            throw new \RuntimeException(\sprintf('The "Mailer" component requires the browser be a %s.', BrowserKitBrowser::class));
        }

        if (!$browser->profile()->hasCollector('mailer')) {
            throw new \RuntimeException('The profiler does not include the "mailer" collector. Is symfony/mailer installed?');
        }

        return SentEmails::fromEvents($browser->profile()->getCollector('mailer')->getEvents());
    }
}
