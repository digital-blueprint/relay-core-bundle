<?php

namespace DBP\API\CoreBundle\DependencyInjection;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

class DbpCoreExtension extends ConfigurableExtension
{
    public function loadInternal(array $configs, ContainerBuilder $container)
    {
        $this->extendArrayParameter(
            $container, 'api_platform.resource_class_directories', [__DIR__ . '/../Entity']);

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );
        $loader->load('services.yaml');

        $def = $container->register('dbp_api.cache.core.campus_online', FilesystemAdapter::class);
        $def->setArguments(['core-campus-online', 60, '%kernel.cache_dir%/dbp/core-campus-online']);
        $def->setPublic(true);
        $def->addTag("cache.pool");

        $def = $container->register('dbp_api.cache.core.keycloak_cert', FilesystemAdapter::class);
        $def->setArguments(['core-keycloak-cert', 60, '%kernel.cache_dir%/dbp/core-keycloak-cert']);
        $def->setPublic(true);
        $def->addTag("cache.pool");

        $def = $container->register('dbp_api.cache.core.auth_person', FilesystemAdapter::class);
        $def->setArguments(['core-auth-person', 60, '%kernel.cache_dir%/dbp/core-auth-person']);
        $def->setPublic(true);
        $def->addTag("cache.pool");

        $container->setParameter('dbp_api.core.keycloak_config', $configs['keycloak']);
        $container->setParameter('dbp_api.core.ldap_config', $configs['ldap']);
        $container->setParameter('dbp_api.core.co_config', $configs['campus_online']);
    }

    private function extendArrayParameter(ContainerBuilder $container, string $parameter, array $values) {
        if (!$container->hasParameter($parameter))
            $container->setParameter($parameter, []);
        $oldValues = $container->getParameter($parameter);
        assert(is_array($oldValues));
        $container->setParameter($parameter, array_merge($oldValues, $values));
    }
}