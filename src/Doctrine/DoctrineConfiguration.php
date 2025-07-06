<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Doctrine;

use Dbp\Relay\CoreBundle\Extension\ExtensionTrait;
use Doctrine\Migrations\Version\MigrationFactory as DoctrineMigrationsFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class DoctrineConfiguration
{
    use ExtensionTrait;

    public const IN_MEMORY_DATABASE_URL = 'sqlite:///:memory:';

    public static function prependEntityManagerConfig(ContainerBuilder $containerBuilder, string $entityManagerId,
        string $databaseUrl, string $entityDirectoryPath, string $entityNamespace, ?string $connectionId = null): void
    {
        self::ensureInPrepend($containerBuilder);

        if (!$containerBuilder->hasExtension('doctrine')) {
            throw new \RuntimeException('configuring doctrine requires the doctrine bundle to be loaded!');
        }

        $connectionId ??= $entityManagerId;

        $containerBuilder->prependExtensionConfig('doctrine', [
            'dbal' => [
                'connections' => [
                    $connectionId => [
                        'url' => $databaseUrl,
                    ],
                ],
            ],
            'orm' => [
                'entity_managers' => [
                    $entityManagerId => [
                        'naming_strategy' => 'doctrine.orm.naming_strategy.underscore_number_aware',
                        'connection' => $connectionId,
                        'mappings' => [
                            $entityManagerId => [
                                'type' => 'attribute',
                                'dir' => $entityDirectoryPath,
                                'prefix' => $entityNamespace,
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        self::extendArrayParameter(
            $containerBuilder, 'dbp_api.entity_managers', [$entityManagerId]
        );
    }

    public static function prependMigrationsConfig(ContainerBuilder $containerBuilder,
        string $migrationsDirectoryPath, string $migrationsNamespace): void
    {
        if (!$containerBuilder->hasExtension('doctrine_migrations')) {
            throw new \RuntimeException(
                'configuring doctrine migrations requires the doctrine_migrations bundle to be loaded!');
        }

        $containerBuilder->prependExtensionConfig('doctrine_migrations', [
            'migrations_paths' => [
                $migrationsNamespace => $migrationsDirectoryPath,
            ],
            'services' => [
                DoctrineMigrationsFactory::class => MigrationFactoryDecorator::class,
            ],
        ]);
    }
}
