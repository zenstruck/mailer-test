<?php

/*
 * This file is part of the zenstruck/mailer-test package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
        $this->browser(['environment' => $environment])
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
