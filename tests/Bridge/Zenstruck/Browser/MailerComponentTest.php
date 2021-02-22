<?php

namespace Zenstruck\Mailer\Test\Tests\Bridge\Zenstruck\Browser;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Mailer\Test\Bridge\Zenstruck\Browser\MailerComponent;
use Zenstruck\Mailer\Test\Tests\EnvironmentProvider;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class MailerComponentTest extends KernelTestCase
{
    use EnvironmentProvider, HasBrowser;

    /**
     * @test
     * @dataProvider environmentProvider
     */
    public function can_use_component(string $environment): void
    {
        $this->kernelBrowser(['environment' => $environment])
            ->withProfiling()
            ->visit('/no-email')
            ->assertSuccessful()
            ->use(function(MailerComponent $component) {
                $component->assertNoEmailSent();
            })
            ->withProfiling()
            ->visit('/send-email')
            ->assertSuccessful()
            ->use(function(MailerComponent $component) {
                $component
                    ->assertSentEmailCount(1)
                    ->assertEmailSentTo('kevin@example.com', 'email subject')
                ;
            })
        ;
    }
}
