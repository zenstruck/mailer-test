# zenstruck/mailer-test

[![CI Status](https://github.com/zenstruck/mailer-test/workflows/CI/badge.svg)](https://github.com/zenstruck/mailer-test/actions?query=workflow%3ACI)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/zenstruck/mailer-test/badges/quality-score.png?b=1.x)](https://scrutinizer-ci.com/g/zenstruck/mailer-test/?branch=1.x)
[![Code Coverage](https://codecov.io/gh/zenstruck/mailer-test/branch/1.x/graph/badge.svg?token=R7OHYYGPKM)](https://codecov.io/gh/zenstruck/mailer-test)

Alternative, opinionated helpers for testing emails sent with `symfony/mailer`. This package is
an alternative to the FrameworkBundle's `MailerAssertionsTrait`.

## Installation

```bash
composer require --dev zenstruck/messenger-test
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
