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

    /**
     * @param string[] $dependsOnEntityManagers Names of other entity managers that need to be migrated before this one
     */
    public static function prependEntityManagerConfig(ContainerBuilder $containerBuilder, string $entityManagerId,
        string $databaseUrl, string $entityDirectoryPath, string $entityNamespace, ?string $connectionId = null,
        array $dependsOnEntityManagers = []): void
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

        $entityManagers = $containerBuilder->hasParameter('dbp_api.entity_managers') ?
            $containerBuilder->getParameter('dbp_api.entity_managers') : [];
        assert(is_array($entityManagers));
        $entityManagers[$entityManagerId] = array_values(array_unique(
            array_merge($entityManagers[$entityManagerId] ?? [], $dependsOnEntityManagers)
        ));
        $containerBuilder->setParameter('dbp_api.entity_managers', $entityManagers);
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
