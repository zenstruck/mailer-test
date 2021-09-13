<?php

namespace Zenstruck\Mailer\Test\Tests;

use Psr\Container\ContainerInterface;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait ContainerBC
{
    private static function container(): ContainerInterface
    {
        if (!\method_exists(static::class, 'getContainer')) {
            if (!static::$booted) {
                static::bootKernel();
            }

            return self::$container;
        }

        return self::getContainer();
    }
}
