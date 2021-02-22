<?php

namespace Zenstruck\Mailer\Test\Tests;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait EnvironmentProvider
{
    public static function environmentProvider(): iterable
    {
        yield ['test'];
        yield ['bus_sync'];
        yield ['bus_async'];
    }
}
