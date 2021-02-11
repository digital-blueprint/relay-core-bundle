<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('dbp_core');

        $keycloakBuilder = new TreeBuilder('keycloak');
        $keycloakNode = $keycloakBuilder->getRootNode()
            ->children()
            ->scalarNode('server_url')->end()
            ->scalarNode('realm')->end()
            ->scalarNode('client_id')->end()
            ->scalarNode('client_secret')->end()
            ->scalarNode('audience')->end()
            ->booleanNode('local_validation')->defaultTrue()->end()
            ->end();
        $treeBuilder->getRootNode()->append($keycloakNode);

        $coBuilder = new TreeBuilder('campus_online');
        $coNode = $coBuilder->getRootNode()
            ->children()
            ->scalarNode('web_url')->end()
            ->scalarNode('api_url')->end()
            ->scalarNode('api_token')->end()
            ->end();
        $treeBuilder->getRootNode()->append($coNode);

        $coBuilder = new TreeBuilder('api_docs');
        $coNode = $coBuilder->getRootNode()
            ->children()
            ->scalarNode('keycloak_client_id')->end()
            ->scalarNode('build_info')->end()
            ->scalarNode('build_info_url')->end()
            ->end();
        $treeBuilder->getRootNode()->append($coNode);

        return $treeBuilder;
    }
}
