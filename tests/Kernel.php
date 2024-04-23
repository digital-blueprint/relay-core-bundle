<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests;

use ApiPlatform\Symfony\Bundle\ApiPlatformBundle;
use Dbp\Relay\CoreBundle\DbpRelayCoreBundle;
use Dbp\Relay\CoreBundle\Tests\TestApi\TestResource;
use Dbp\Relay\CoreBundle\Tests\TestApi\TestResourceController;
use Dbp\Relay\CoreBundle\Tests\TestApi\TestResourceProvider;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Nelmio\CorsBundle\NelmioCorsBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

class Kernel extends BaseKernel
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
        yield new DoctrineBundle();
        yield new DbpRelayCoreBundle();
    }

    protected function configureRoutes(RoutingConfigurator $routes)
    {
        $routes->import('@DbpRelayCoreBundle/Resources/config/routing.yaml');
    }

    protected function configureContainer(ContainerConfigurator $container)
    {
        $container->services()->set(TestResourceController::class)->public()->autoconfigure()->autowire();
        $container->services()->set(TestResource::class)->public()->autoconfigure()->autowire();
        $container->services()->set(TestResourceProvider::class)->public()->autoconfigure()->autowire();

        $container->import('@DbpRelayCoreBundle/Resources/config/services_test.yaml');
        $container->extension('framework', [
            'test' => true,
            'secret' => '',
            'annotations' => false,
        ]);

        $container->extension('api_platform', [
            'mapping' => [
                'paths' => [__DIR__.'/TestApi'],
            ],
        ]);

        $container->extension('doctrine', [
            'dbal' => [
                'connections' => [
                    'in_memory_test' => [
                        'url' => 'sqlite:///:memory:',
                        'driver' => 'pdo_sqlite',
                    ],
                ],
            ],
        ]);

        $container->extension('dbp_relay_core', [
            'queue_dsn' => 'doctrine://in_memory_test',
        ]);
    }
}
