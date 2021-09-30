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
    final protected static function _startTestMailerCollection(): void
    {
        MessageEventCollector::start();
    }

    /**
     * @internal
     * @after
     */
    final protected static function _resetTestMailerCollection(): void
    {
        MessageEventCollector::reset();
    }

    final protected function mailer(): TestMailer
    {
        if (!$this instanceof KernelTestCase) {
            throw new \LogicException(\sprintf('The %s trait can only be used with %s.', __TRAIT__, KernelTestCase::class));
        }

        if (!self::$container) {
            throw new \LogicException('The kernel must be booted before accessing the mailer.');
        }

        if (!self::$container->has('zenstruck_mailer_test.event_collector')) {
            throw new \LogicException(\sprintf('Cannot access collected emails - is %s enabled in your test environment?', ZenstruckMailerTestBundle::class));
        }

        return self::$container->get('zenstruck_mailer_test.event_collector')->mailer();
    }
}
