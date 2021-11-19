<?php

namespace Zenstruck\Mailer\Test\Tests\Fixture;

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\Component\Routing\RouteCollectionBuilder;
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

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader): void
    {
        $loader->load(\sprintf('%s/config/%s.yaml', __DIR__, $this->getEnvironment()));
    }

    /**
     * @param RouteCollectionBuilder|RoutingConfigurator $routes
     */
    protected function configureRoutes($routes): void
    {
        if ($routes instanceof RouteCollectionBuilder) {
            // BC
            $routes->add('/no-email', 'kernel::noEmail');
            $routes->add('/send-email', 'kernel::sendEmail');

            return;
        }

        $routes->add('no-email', '/no-email')->controller('kernel::noEmail');
        $routes->add('send-email', '/send-email')->controller('kernel::sendEmail');
    }
}
