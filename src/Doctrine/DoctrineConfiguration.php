<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Doctrine;

use Dbp\Relay\CoreBundle\Extension\ExtensionTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class DoctrineConfiguration
{
    use ExtensionTrait;

    public const IN_MEMORY_DATABASE_URL = 'sqlite:///:memory:';

    public static function prependEntityManagerConfig(ContainerBuilder $containerBuilder, string $entityManagerId,
        string $databaseUrl, string $entityDirectoryPath, string $entityNamespace, ?string $connectionId = null): void
    {
        self::ensureInPrepend($containerBuilder);

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
        string $migrationsNamespace, string $migrationsDirectoryPath): void
    {
        $containerBuilder->prependExtensionConfig('doctrine_migrations', [
            'migrations_paths' => [
                $migrationsNamespace => $migrationsDirectoryPath,
            ],
        ]);
    }
}
