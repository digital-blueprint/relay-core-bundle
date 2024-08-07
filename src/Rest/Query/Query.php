<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Rest\Query;

use Dbp\Relay\CoreBundle\Rest\Query\Filter\Filter;
use Dbp\Relay\CoreBundle\Rest\Query\Sort\Sort;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class Query
{
    public const ROOT_CONFIG_NODE = 'query';

    public static function getConfigNodeDefinition(): NodeDefinition
    {
        $treeBuilder = new TreeBuilder(self::ROOT_CONFIG_NODE);
        $rootNode = $treeBuilder->getRootNode();

        $rootNode->append(Filter::getConfigNodeDefinition());
        $rootNode->append(Sort::getConfigNodeDefinition());

        return $rootNode;
    }
}
