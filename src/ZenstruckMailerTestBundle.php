<?php

namespace Zenstruck\Mailer\Test;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ZenstruckMailerTestBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->register('zenstruck_mailer_test.event_collector', MessageEventCollector::class)
            ->addTag('kernel.event_subscriber')
        ;
    }

    public function getContainerExtension(): ?ExtensionInterface
    {
        return null;
    }
}
