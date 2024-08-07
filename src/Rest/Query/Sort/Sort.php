<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Rest\Query\Sort;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class Sort
{
    public const PATH_KEY = 'path';
    public const DIRECTION_KEY = 'direction';

    public const ASCENDING_DIRECTION = 'ASC';
    public const DESCENDING_DIRECTION = 'DESC';

    public const ROOT_CONFIG_NODE = 'sort';
    public const ENABLE_SORT_CONFIG_NODE = 'enable_sort';

    /**
     * The fields on which to sort ordered by sort priority (index 0 has highest priority).
     *
     * @var array[]
     */
    protected array $sortFields;

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

    public static function createSortField(string $path, string $direction = self::ASCENDING_DIRECTION): array
    {
        return [
            self::PATH_KEY => $path,
            self::DIRECTION_KEY => $direction,
        ];
    }

    public static function getPath(array $sortField): string
    {
        return $sortField[self::PATH_KEY];
    }

    public static function getDirection(array $sortField): string
    {
        return $sortField[self::DIRECTION_KEY];
    }

    /**
     * Constructs a new Sort object.
     *
     * Takes an array of sort fields. Example:
     *   [
     *     [
     *       Sort::PATH_KEY => 'changed',
     *       Sort::DIRECTION_KEY => Sort::DIRECTION_DESCENDING,
     *     ],
     *     [
     *       Sort::PATH_KEY => 'title',
     *       Sort::DIRECTION_KEY => Sort::DIRECTION_ASCENDING,
     *     ],
     *   ]
     * The results will be sorted first by 'changed' (ascending) and then by 'title' (descending)
     *
     * @param array[] $sortFields A numeric array of sort fields where lower array indices have higher sort priority
     */
    public function __construct(array $sortFields)
    {
        $this->sortFields = $sortFields;
    }

    public function getSortFields(): array
    {
        return $this->sortFields;
    }
}
