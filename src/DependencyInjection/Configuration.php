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
            ->scalarNode('docs_title')
                ->defaultValue('Relay API Gateway')
            ->end()
            ->scalarNode('docs_description') // This supports markdown
                ->defaultValue('*part of the [Digital Blueprint](https://gitlab.tugraz.at/dbp) project*')
            ->end()
            ->end();

        return $treeBuilder;
    }
}
