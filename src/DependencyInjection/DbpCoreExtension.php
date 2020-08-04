<?php

namespace DBP\API\CoreBundle\DependencyInjection;

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