<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('dbp_relay_core');
        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('build_info')
                    ->info('Some string identifying the current build (commit hash)')
                    ->example('deadbeef')
                ->end()
                ->scalarNode('build_info_url')
                    ->info('Some URL identifying the current build (URL to the commit on some git web interface)')
                    ->example('https://gitlab.example.com/project/-/commit/deadbeef')
                ->end()
                ->scalarNode('docs_title')
                    ->info('The title text of the API docs page')
                    ->defaultValue('Relay API Gateway')
                ->end()
                ->scalarNode('docs_description')
                ->info('The description text of the API docs page (supports markdown)')
                    ->defaultValue('*part of the [Digital Blueprint](https://gitlab.tugraz.at/dbp) project*')
                ->end()
            ->end();

        return $treeBuilder;
    }
}
