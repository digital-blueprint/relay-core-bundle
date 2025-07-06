<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests;

use ApiPlatform\Symfony\Bundle\ApiPlatformBundle;
use Dbp\Relay\CoreBundle\DbpRelayCoreBundle;
use Dbp\Relay\CoreBundle\Tests\TestApi\Authorization\TestApiAuthorizationService;
use Dbp\Relay\CoreBundle\Tests\TestApi\Rest\TestResourceItemController;
use Dbp\Relay\CoreBundle\Tests\TestApi\Rest\TestResourceProcessor;
use Dbp\Relay\CoreBundle\Tests\TestApi\Rest\TestResourceProvider;
use Dbp\Relay\CoreBundle\Tests\TestApi\Rest\TestSubResourceProcessor;
use Dbp\Relay\CoreBundle\Tests\TestApi\Rest\TestSubResourceProvider;
use Dbp\Relay\CoreBundle\Tests\TestApi\Service\TestResourceService;
use Dbp\Relay\CoreBundle\Tests\TestApi\TestApi;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle;
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
        yield new DoctrineMigrationsBundle();
        yield new DbpRelayCoreBundle();
    }

    protected function configureRoutes(RoutingConfigurator $routes)
    {
        $routes->import('@DbpRelayCoreBundle/Resources/config/routing.yaml');
    }

    protected function configureContainer(ContainerConfigurator $container)
    {
        $container->services()->set(TestApiAuthorizationService::class)->public()->autoconfigure()->autowire()
            ->call('setConfig', [TestApiAuthorizationService::getTestConfig()]);
        $container->services()->set(TestResourceService::class)->public()->autoconfigure()->autowire();
        $container->services()->set(TestResourceItemController::class)->public()->autoconfigure()->autowire();
        $container->services()->set(TestResourceProcessor::class)->public()->autoconfigure()->autowire();
        $container->services()->set(TestResourceProvider::class)->public()->autoconfigure()->autowire();
        $container->services()->set(TestSubResourceProcessor::class)->public()->autoconfigure()->autowire();
        $container->services()->set(TestSubResourceProvider::class)->public()->autoconfigure()->autowire();

        $container->import('@DbpRelayCoreBundle/Resources/config/services_test.yaml');
        $container->extension('framework', [
            'test' => true,
            'secret' => 'somesecret',
            'annotations' => false,
        ]);

        $container->extension('api_platform', [
            'mapping' => [
                'paths' => [__DIR__.'/TestApi'],
            ],
        ]);

        // Inspecting the failed transport doesn't work with the in-memory transport, so setup a sqlite
        // transport for testing.
        $container->extension('doctrine', [
            'dbal' => [
                'connections' => [
                    TestApi::ENTITY_MANAGER_ID => [
                        'url' => 'sqlite:///:memory:',
                        'driver' => 'pdo_sqlite',
                    ],
                ],
            ],
            'orm' => [
                'entity_managers' => [
                    TestApi::ENTITY_MANAGER_ID => [
                        'naming_strategy' => 'doctrine.orm.naming_strategy.underscore_number_aware',
                        'connection' => TestApi::ENTITY_MANAGER_ID,
                        'mappings' => [
                            TestApi::ENTITY_MANAGER_ID => [
                                'type' => 'attribute',
                                'dir' => __DIR__.'/TestApi/Entity',
                                'prefix' => 'Dbp\Relay\CoreBundle\Tests\TestApi\Entity',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $container->extension('dbp_relay_core', [
            'queue_dsn' => 'doctrine://'.TestApi::ENTITY_MANAGER_ID,
        ]);
    }
}
