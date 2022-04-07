<?php

namespace Zenstruck\Mailer\Test;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait InteractsWithMailer
{
    /**
     * @internal
     * @before
     */
    final protected static function _startTestMailer(): void
    {
        TestMailer::start();
    }

    /**
     * @internal
     * @after
     */
    final protected static function _stopTestMailer(): void
    {
        TestMailer::stop();
    }

    final protected function mailer(): TestMailer
    {
        if (!$this instanceof KernelTestCase) {
            throw new \LogicException(\sprintf('The %s trait can only be used with %s.', __TRAIT__, KernelTestCase::class));
        }

        if (!self::$booted) {
            throw new \LogicException('The kernel must be booted before accessing the mailer.');
        }

        if (!self::getContainer()->has('zenstruck_mailer_test.mailer')) {
            throw new \LogicException(\sprintf('Cannot access test mailer - is %s enabled in your test environment?', ZenstruckMailerTestBundle::class));
        }

        return self::getContainer()->get('zenstruck_mailer_test.mailer');
    }
}
