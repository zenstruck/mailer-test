<?php

namespace Zenstruck\Mailer\Test;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait InteractsWithMailer
{
    final protected function mailer(): TestMailer
    {
        if (!$this instanceof KernelTestCase) {
            throw new \LogicException(\sprintf('The %s trait can only be used with %s.', __TRAIT__, KernelTestCase::class));
        }

        if (!self::$container) {
            throw new \LogicException('The kernel must be booted before accessing the mailer.');
        }

        if (self::$container->has('mailer.message_logger_listener')) {
            return new TestMailer(self::$container->get('mailer.message_logger_listener')->getEvents());
        }

        if (self::$container->has('mailer.logger_message_listener')) {
            return new TestMailer(self::$container->get('mailer.logger_message_listener')->getEvents());
        }

        throw new \LogicException('Mailer and/or profiling not enabled.');
    }
}
