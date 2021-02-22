<?php

namespace Zenstruck\Mailer\Test\Bridge\Zenstruck\Browser;

use Zenstruck\Browser\BrowserKitBrowser;
use Zenstruck\Browser\Component;
use Zenstruck\Mailer\Test\TestMailer;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class MailerComponent extends Component
{
    /**
     * @see TestMailer::assertNoEmailSent()
     */
    public function assertNoEmailSent(): self
    {
        $this->testMailer()->assertNoEmailSent();

        return $this;
    }

    /**
     * @see TestMailer::assertSentEmailCount()
     */
    public function assertSentEmailCount(int $count): self
    {
        $this->testMailer()->assertSentEmailCount($count);

        return $this;
    }

    /**
     * @see TestMailer::assertEmailSentTo()
     */
    public function assertEmailSentTo(string $expectedTo, $callback): self
    {
        $this->testMailer()->assertEmailSentTo($expectedTo, $callback);

        return $this;
    }

    private function testMailer(): TestMailer
    {
        $browser = $this->browser();

        if (!$browser instanceof BrowserKitBrowser) {
            throw new \RuntimeException(\sprintf('The "Mailer" component requires the browser be a %s.', BrowserKitBrowser::class));
        }

        if (!$browser->profile()->hasCollector('mailer')) {
            throw new \RuntimeException('The profiler does not include the "mailer" collector. Is symfony/mailer installed?');
        }

        return new TestMailer($browser->profile()->getCollector('mailer')->getEvents());
    }
}
