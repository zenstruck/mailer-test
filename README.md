# zenstruck/mailer-test

[![CI Status](https://github.com/zenstruck/mailer-test/workflows/CI/badge.svg)](https://github.com/zenstruck/mailer-test/actions?query=workflow%3ACI)
[![Code Coverage](https://codecov.io/gh/zenstruck/mailer-test/branch/1.x/graph/badge.svg?token=R7OHYYGPKM)](https://codecov.io/gh/zenstruck/mailer-test)

Alternative, opinionated helpers for testing emails sent with `symfony/mailer`. This package is
an alternative to the FrameworkBundle's `MailerAssertionsTrait`.

## Installation

1. Install the library:
    ```bash
    composer require --dev zenstruck/mailer-test
    ```
2. If not added automatically by symfony/flex, enable `ZenstruckMailerTestBundle` in your
   `test` environment

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

        $this->mailer()->assertNoEmailSent();
        $this->mailer()->assertSentEmailCount(5);
        $this->mailer()->assertEmailSentTo('kevin@example.com', 'the subject');

        // For more advanced assertions, use a callback for the subject.
        // Note the \Zenstruck\Mailer\Test\TestEmail argument. This is a decorator
        // around \Symfony\Component\Mime\Email with some extra assertions.
        $this->mailer()->assertEmailSentTo('kevin@example.com', function(TestEmail $email) {
            $email
                ->assertSubject('Email Subject')
                ->assertSubjectContains('Subject')
                ->assertFrom('from@example.com')
                ->assertReplyTo('reply@example.com')
                ->assertCc('cc1@example.com')
                ->assertCc('cc2@example.com')
                ->assertBcc('bcc@example.com')
                ->assertTextContains('some text')
                ->assertHtmlContains('some text')
                ->assertContains('some text') // asserts text and html both contain a value
                ->assertHasFile('file.txt', 'text/plain', 'Hello there!')

                // tag/meta data assertions (https://symfony.com/doc/current/mailer.html#adding-tags-and-metadata-to-emails)
                ->assertHasTag('password-reset')
                ->assertHasMetadata('Color')
                ->assertHasMetadata('Color', 'blue')
            ;

            // Any \Symfony\Component\Mime\Email methods can be used
            $this->assertSame('value', $email->getHeaders()->get('X-SOME-HEADER')->getBodyAsString());
        });

        // reset collected emails
        $this->mailer()->reset();
    }
}
```

**NOTE**: Emails are persisted between kernel reboots within each test. You can reset the
collected emails with `$this->mailer()->reset()`.

### SentEmails Collection

You can access all the sent emails and filter down to just the ones you want to make assertions on.
Most methods are fluent.

```php
use Symfony\Component\Mime\Email;
use Zenstruck\Mailer\Test\SentEmails;
use Zenstruck\Mailer\Test\TestEmail;

/** @var SentEmails $sentEmails */
$sentEmails = $this->mailer()->sentEmails();

$sentEmails->all(); // TestEmail[]
$sentEmails->raw(); // Email[]

$sentEmails->first(); // First TestEmail in collection or fail if none
$sentEmails->last(); // Last TestEmail in collection or fail
$sentEmails->count(); // # of emails in collection
$sentEmails->dump(); // dump() the collection
$sentEmails->dd(); // dd() the collection

$sentEmails->each(function(TestEmail $email) {
    // do something with each email in collection
});
$sentEmails->each(function(Email $email) {
    // can typehint as Email
});

// iterate over collection
foreach ($sentEmails as $email) {
    /** @var TestEmail $email */
}

// assertions
$sentEmails->assertNone();
$sentEmails->assertCount(5);

// fails if collection is empty
$sentEmails->ensureSome();
$sentEmails->ensureSome('custom failure message');

// filters - returns new instance of SentEmails
$sentEmails->whereSubject('some subject'); // emails with subject "some subject"
$sentEmails->whereSubjectContains('subject'); // emails where subject contains "subject"
$sentEmails->whereFrom('sally@example.com'); // emails sent from "sally@example.com"
$sentEmails->whereTo('sally@example.com'); // emails sent to "sally@example.com"
$sentEmails->whereCc('sally@example.com'); // emails cc'd to "sally@example.com"
$sentEmails->whereBcc('sally@example.com'); // emails bcc'd to "sally@example.com"
$sentEmails->whereReplyTo('sally@example.com'); // emails with "sally@example.com" as a reply-to
$sentEmails->whereTag('password-reset'); // emails with "password-reset" tag (https://symfony.com/doc/current/mailer.html#adding-tags-and-metadata-to-emails)

// custom filter
$sentEmails->where(function(TestEmail $email): bool {
    return 'password-reset' === $email->tag() && 'Some subject' === $email->getSubject();
});

// combine filters
$sentEmails
    ->whereTag('password-reset')
    ->assertCount(2)
    ->each(function(TestEmail $email) {
        $email->assertSubjectContains('Password Reset');
    })
    ->whereTo('kevin@example.com')
    ->assertCount(1)
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

        // Type-hinting the callback with your custom TestEmail triggers it to be
        // injected instead of the standard TestEmail.
        $this->mailer()->assertEmailSentTo('kevin@example.com', function(AppTestEmail $email) {
            $email->assertHasPostmarkTag('password-reset');
        });

        $this->mailer()->sentEmails()->each(function(AppTestEmail $email) {
            $email->assertHasPostmarkTag('password-reset');
        });

        // add your custom TestEmail as an argument to these methods to change the return type
        $this->mailer()->sentEmails()->first(AppTestEmail::class); // AppTestEmail
        $this->mailer()->sentEmails()->last(AppTestEmail::class); // AppTestEmail
        $this->mailer()->sentEmails()->all(AppTestEmail::class); // AppTestEmail[]
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

        $component->sentEmails(); \Zenstruck\Mailer\Test\SentEmails
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
