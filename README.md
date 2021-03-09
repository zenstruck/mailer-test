# zenstruck/mailer-test

[![CI Status](https://github.com/zenstruck/mailer-test/workflows/CI/badge.svg)](https://github.com/zenstruck/mailer-test/actions?query=workflow%3ACI)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/zenstruck/mailer-test/badges/quality-score.png?b=1.x)](https://scrutinizer-ci.com/g/zenstruck/mailer-test/?branch=1.x)
[![Code Coverage](https://codecov.io/gh/zenstruck/mailer-test/branch/1.x/graph/badge.svg?token=R7OHYYGPKM)](https://codecov.io/gh/zenstruck/mailer-test)

Alternative, opinionated helpers for testing emails sent with `symfony/mailer`. This package is
an alternative to the FrameworkBundle's `MailerAssertionsTrait`.

## Installation

```bash
composer require --dev zenstruck/mailer-test
```

## Usage

You can interact with the mailer in your tests by using the `InteractsWithMailer` trait in
your `KernelTestCase`/`WebTestCase` tests:

```php
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Mailer\Test\InteractsWithMailer;
use Zenstruck\Mailer\Test\TestEmail;

class MyTest extends KernelTestCase // or WebTestCase
{
    use InteractsWithMailer;

    public function test_something(): void
    {
        // ...some code that sends emails...

        $this->mailer()->sentEmails(); // \Symfony\Component\Mime\Email[]

        $this->mailer()->assertNoEmailSent();
        $this->mailer()->assertSentEmailCount(5);
        $this->mailer()->assertEmailSentTo('kevin@example.com', 'the subject');

        // For more advanced assertions, use a callback for the subject.
        // Note the \Zenstruck\Mailer\Test\TestEmail argument. This is a decorator
        // around \Symfony\Component\Mime\Email with some extra assertions.
        $this->mailer()->assertEmailSentTo('kevin@example.com', function(TestEmail $email) {
            $email
                ->assertSubject('Email Subject')
                ->assertFrom('from@example.com')
                ->assertReplyTo('reply@example.com')
                ->assertCc('cc1@example.com')
                ->assertCc('cc2@example.com')
                ->assertBcc('bcc@example.com')
                ->assertTextContains('some text')
                ->assertHtmlContains('some text')
                ->assertContains('some text') // asserts text and html both contain a value
                ->assertHasFile('file.txt', 'text/plain', 'Hello there!')
            ;

            // Any \Symfony\Component\Mime\Email methods can be used
            $this->assertSame('value', $email->getHeaders()->get('X-SOME-HEADER')->getBodyAsString());
        });
        
        $this->mailer()->sentTestEmails(); // TestEmail[]
    }
}
```

### Custom TestEmail

The `TestEmail` class shown above is a decorator for `\Symfony\Component\Mime\Email`
with some assertions. You can extend this to add your own assertions:

```php
namespace App\Tests;

use PHPUnit\Framework\Assert;
use Zenstruck\Mailer\Test\TestEmail;

class AppTestEmail extends TestEmail
{
    public function assertHasPostmarkTag(string $expected): self
    {
        Assert::assertTrue($this->getHeaders()->has('X-PM-Tag'));
        Assert::assertSame($expected, $this->getHeaders()->get('X-PM-Tag')->getBodyAsString());

        return $this;
    }
}
```

Then, use in your tests:

```php
use App\Tests\AppTestEmail;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Mailer\Test\InteractsWithMailer;

class MyTest extends KernelTestCase // or WebTestCase
{
use InteractsWithMailer;

    public function test_something(): void
    {
        // ...some code that sends emails...

        $this->mailer()->sentTestEmails(AppTestEmail::class); // AppTestEmail[]

        // Type-hinting the callback with your custom TestEmail triggers it to be
        // injected instead of the standard TestEmail.
        $this->mailer()->assertEmailSentTo('kevin@example.com', function(AppTestEmail $email) {
            $email->assertHasPostmarkTag('password-reset');
        });
    }
}
```

### zenstruck/browser Integration

This library provides a [zenstruck/browser](https://github.com/zenstruck/browser)
"[Component](https://github.com/zenstruck/browser#custom-components)" and
"[Extension](https://github.com/zenstruck/browser#custom-browser)". Since browser's
make HTTP requests to your app, the messages are accessed via the profiler (using
`symfony/mailer`'s data collector). Because of this, the `InteractsWithMailer` trait
is not required in your test case. Since the profiler is required, this functionality
is not available with `PantherBrowser`.

#### MailerComponent

The simplest way to get started testing emails with `zenstruck/browser` is to use the
`MailerComponent`:

```php
use Zenstruck\Mailer\Test\Bridge\Zenstruck\Browser\MailerComponent;
use Zenstruck\Mailer\Test\TestEmail;

/** @var \Zenstruck\Browser\KernelBrowser $browser **/
$browser
    ->withProfiling() // enable the profiler for the next request
    ->visit('/page/that/does/not/send/email')
    ->use(function(MailerComponent $component) {
        $component->assertNoEmailSent();
    })
    
    ->withProfiling() // enable the profiler for the next request
    ->visit('/page/that/sends/email')
    ->use(function(MailerComponent $component) {
        $component
            ->assertSentEmailCount(1)
            ->assertEmailSentTo('kevin@example.com', 'Email Subject')
            ->assertEmailSentTo('kevin@example.com', function(TestEmail $email) {
                // see Usage section above for full API
            })
        ;
    })
;
```

#### MailerExtension

If many of your tests make email assertions the [MailerComponent](#mailercomponent)'s API
can be a little verbose. Alternatively, you can add the methods directly on a
[custom browser](https://github.com/zenstruck/browser#custom-browser) using the provided
`MailerExtension` trait:

```php
namespace App\Tests;

use Zenstruck\Browser\KernelBrowser;
use Zenstruck\Mailer\Test\Bridge\Zenstruck\Browser\MailerExtension;

class AppBrowser extends KernelBrowser
{
    use MailerExtension;
}
```

Now, within your tests using this custom browser, the following email assertion
API is available:

```php
use Zenstruck\Mailer\Test\TestEmail;

/** @var \App\Tests\AppBrowser $browser **/
$browser
    ->withProfiling() // enable the profiler for the next request
    ->visit('/page/that/does/not/send/email')
    ->assertNoEmailSent()
    
    ->withProfiling() // enable the profiler for the next request
    ->visit('/page/that/sends/email')
    ->assertSentEmailCount(1)
    ->assertEmailSentTo('kevin@example.com', 'Email Subject')
    ->assertEmailSentTo('kevin@example.com', function(TestEmail $email) {
        // see Usage section above for full API
    })
;
```
