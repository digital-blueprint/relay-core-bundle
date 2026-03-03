<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Rest\Query\Sort;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class Sort
{
    public const ROOT_CONFIG_NODE = 'sort';
    public const ENABLE_SORT_CONFIG_NODE = 'enable_sort';

    public static function getConfigNodeDefinition(): NodeDefinition
    {
        $treeBuilder = new TreeBuilder(self::ROOT_CONFIG_NODE);
        $rootNode = $treeBuilder->getRootNode();

        $rootNode->children()
            ->scalarNode(self::ENABLE_SORT_CONFIG_NODE)
            ->info('Indicates whether sorting using sort query parameters is enabled.')
            ->defaultFalse()
            ->end();

        return $rootNode;
    }

    public static function createSortField(string $path, string $direction = SortField::ASCENDING_DIRECTION): SortField
    {
        return new SortField($path, $direction);
    }

    /**
     * @param SortField[] $sortFields A numeric array of sort fields where lower array indices have higher sort priority
     */
    public function __construct(private array $sortFields = [])
    {
    }

    /**
     * @param SortField[] $sortFields A numeric array of sort fields where lower array indices have higher sort priority
     */
    public function setSortFields(array $sortFields): void
    {
        $this->sortFields = $sortFields;
    }

    public function addSortField(SortField $sortField): void
    {
        $this->sortFields[] = $sortField;
    }

    /**
     * @return SortField[]
     */
    public function getSortFields(): array
    {
        return $this->sortFields;
    }
}
