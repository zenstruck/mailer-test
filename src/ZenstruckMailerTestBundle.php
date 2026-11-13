<?php

/*
 * This file is part of the zenstruck/mailer-test package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Mailer\Test;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\Mailer\Event\MessageEvent;
use Zenstruck\Mailer\Test\Bridge\Symfony\Notifier\EmailMessageBus;
use Zenstruck\Mailer\Test\Tracking\EmailEventTracker;
use Zenstruck\Mailer\Test\Tracking\TrackingMailer;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ZenstruckMailerTestBundle extends Bundle implements CompilerPassInterface
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass($this, priority: -1);
        $container->register('zenstruck_mailer_test.mailer', TestMailer::class)
            ->setPublic(true)
            ->addTag('kernel.event_listener', ['event' => MessageEvent::class, 'method' => 'add'])
        ;
    }

    public function getContainerExtension(): ?ExtensionInterface
    {
        return null;
    }

    public function process(ContainerBuilder $container): void
    {
        // Symfony 8.1 also registers bundle compiler passes automatically.
        if (!$container->has('mailer.mailer') || $container->hasDefinition('zenstruck_mailer_test.notifier_email_bus')) {
            return;
        }

        $container->register('zenstruck_mailer_test.tracking_mailer', TrackingMailer::class)
            ->setDecoratedService('mailer.mailer')
            ->setArguments([new Reference('zenstruck_mailer_test.tracking_mailer.inner')])
        ;
        $container->register('zenstruck_mailer_test.email_event_tracker', EmailEventTracker::class)
            ->addTag('kernel.event_listener', ['event' => MessageEvent::class, 'method' => 'prepare', 'priority' => \PHP_INT_MAX])
            ->addTag('kernel.event_listener', ['event' => MessageEvent::class, 'method' => 'finish', 'priority' => \PHP_INT_MIN])
        ;

        if (!$container->hasDefinition('notifier.channel.email')) {
            return;
        }

        $channel = $container->getDefinition('notifier.channel.email');
        $bus = $channel->getArgument(1);

        if (!$bus instanceof Definition && (!$bus instanceof Reference || !$container->has((string) $bus))) {
            return;
        }

        $container->register('zenstruck_mailer_test.notifier_email_bus', EmailMessageBus::class)
            ->setArguments([$bus, new Reference('event_dispatcher'), new Reference('mailer.transports')])
            ->addTag('kernel.event_listener', ['event' => MessageEvent::class, 'method' => 'markCollected'])
        ;
        $channel->setArgument(1, new Reference('zenstruck_mailer_test.notifier_email_bus'));
    }
}
