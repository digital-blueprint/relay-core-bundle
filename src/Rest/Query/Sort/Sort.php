<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Rest\Query\Sort;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class Sort
{
    public const PATH_KEY = 'path';
    public const DIRECTION_KEY = 'direction';

    public const DIRECTION_ASCENDING = 'ASC';
    public const DIRECTION_DESCENDING = 'DESC';

    public const ROOT_CONFIG_NODE = 'sort';
    public const ENABLE_SORTING_CONFIG_NODE = 'enable_sort';

    /**
     * The fields on which to sort ordered by sort prioriry (index 0 has highest priority).
     *
     * @var array[]
     */
    protected array $sortFields;

    public static function getConfigNodeDefinition(): NodeDefinition
    {
        $treeBuilder = new TreeBuilder(self::ROOT_CONFIG_NODE);
        $rootNode = $treeBuilder->getRootNode();

        $rootNode->children()
            ->scalarNode(self::ENABLE_SORTING_CONFIG_NODE)
            ->info('Indicates whether sorting using sort query parameters is enabled.')
            ->defaultFalse()
            ->end();

        return $rootNode;
    }

    public static function createSortField(string $path, string $direction = self::DIRECTION_ASCENDING): array
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
     *       Sorting::PATH_KEY => 'changed',
     *       Sorting::DIRECTION_KEY => Sorting::DIRECTION_DESCENDING,
     *     ],
     *     [
     *       Sorting::PATH_KEY => 'title',
     *       Sorting::DIRECTION_KEY => Sorting::DIRECTION_ASCENDING,
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
