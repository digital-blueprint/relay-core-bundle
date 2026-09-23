<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\TestUtils;

use ApiPlatform\Symfony\Bundle\ApiPlatformBundle;
use Dbp\Relay\CoreBundle\DbpRelayCoreBundle;
use Nelmio\CorsBundle\NelmioCorsBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

trait CoreTestKernelTrait
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new SecurityBundle();
        yield new TwigBundle();
        yield new NelmioCorsBundle();
        yield new MonologBundle();
        yield new ApiPlatformBundle();
        yield from $this->registerAdditionalBundles();
        yield new DbpRelayCoreBundle();
    }

    protected function registerAdditionalBundles(): iterable
    {
        return [];
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import('@DbpRelayCoreBundle/Resources/config/routing.yaml');
        $this->configureAdditionalRoutes($routes);
    }

    protected function configureAdditionalRoutes(RoutingConfigurator $routes): void
    {
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->import('@DbpRelayCoreBundle/Resources/config/services_test.yaml');
        $container->extension('framework', [
            'test' => true,
            'secret' => 'something',
            'annotations' => false,
        ]);

        $this->configureAdditionalContainer($container);
    }

    protected function configureAdditionalContainer(ContainerConfigurator $container): void
    {
    }
}
