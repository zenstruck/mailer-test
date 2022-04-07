<?php

namespace Zenstruck\Mailer\Test\Tests\Fixture;

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Zenstruck\Mailer\Test\ZenstruckMailerTestBundle;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function noEmail(): Response
    {
        return new Response();
    }

    public function sendEmail(): Response
    {
        $this->container->get('mailer')->send(new Email1());

        return new Response();
    }

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();

        if ('no_bundle' !== $this->environment) {
            yield new ZenstruckMailerTestBundle();
        }
    }

    private function configureContainer(ContainerBuilder $c, LoaderInterface $loader): void
    {
        $loader->load(\sprintf('%s/config/%s.yaml', __DIR__, $this->getEnvironment()));
    }

    private function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->add('no-email', '/no-email')->controller('kernel::noEmail');
        $routes->add('send-email', '/send-email')->controller('kernel::sendEmail');
    }
}
