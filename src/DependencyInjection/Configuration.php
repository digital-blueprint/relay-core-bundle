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
        $treeBuilder->getRootNode()
            ->children()
            ->scalarNode('build_info')->end()
            ->scalarNode('build_info_url')->end()
            ->end();

        return $treeBuilder;
    }
}
