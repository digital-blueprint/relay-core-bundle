<?php

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

        $ldapBuilder = new TreeBuilder('ldap');
        $ldapNode = $ldapBuilder->getRootNode()
            ->children()
            ->scalarNode('host')->end()
            ->scalarNode('base_dn')->end()
            ->scalarNode('username')->end()
            ->scalarNode('password')->end()
            ->end();
        $treeBuilder->getRootNode()->append($ldapNode);

        $coBuilder = new TreeBuilder('campus_online');
        $coNode = $coBuilder->getRootNode()
            ->children()
            ->scalarNode('api_url_organization')->end()
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
