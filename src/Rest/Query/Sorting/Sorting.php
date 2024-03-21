<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Rest\Query\Sorting;

class Sorting
{
    public const PATH_KEY = 'path';
    public const DIRECTION_KEY = 'direction';

    public const DIRECTION_ASCENDING = 'ASC';
    public const DIRECTION_DESCENDING = 'DESC';

    /**
     * The fields on which to sort.
     *
     * @var array[]
     */
    protected array $sortFields;

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
     *
     * @param array[] $sortFields the entity query sort fields
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
