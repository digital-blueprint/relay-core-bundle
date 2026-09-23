<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests;

use Dbp\Relay\CoreBundle\Tests\TestApi\Authorization\TestApiAuthorizationService;
use Dbp\Relay\CoreBundle\Tests\TestApi\Rest\TestResourceItemController;
use Dbp\Relay\CoreBundle\Tests\TestApi\Rest\TestResourceProcessor;
use Dbp\Relay\CoreBundle\Tests\TestApi\Rest\TestResourceProvider;
use Dbp\Relay\CoreBundle\Tests\TestApi\Rest\TestSubResourceProcessor;
use Dbp\Relay\CoreBundle\Tests\TestApi\Rest\TestSubResourceProvider;
use Dbp\Relay\CoreBundle\Tests\TestApi\Service\TestResourceService;
use Dbp\Relay\CoreBundle\Tests\TestApi\TestApi;
use Dbp\Relay\CoreBundle\TestUtils\CoreTestKernelTrait;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use CoreTestKernelTrait;

    protected function registerAdditionalBundles(): iterable
    {
        yield new DoctrineBundle();
    }

    protected function configureAdditionalContainer(ContainerConfigurator $container): void
    {
        $container->services()->set(TestApiAuthorizationService::class)->public()->autoconfigure()->autowire()
            ->call('setConfig', [TestApiAuthorizationService::getTestConfig()]);
        $container->services()->set(TestResourceService::class)->public()->autoconfigure()->autowire();
        $container->services()->set(TestResourceItemController::class)->public()->autoconfigure()->autowire();
        $container->services()->set(TestResourceProcessor::class)->public()->autoconfigure()->autowire();
        $container->services()->set(TestResourceProvider::class)->public()->autoconfigure()->autowire()
            ->call('setConfig', [TestResourceProvider::getTestConfig()]);
        $container->services()->set(TestSubResourceProcessor::class)->public()->autoconfigure()->autowire();
        $container->services()->set(TestSubResourceProvider::class)->public()->autoconfigure()->autowire();

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
