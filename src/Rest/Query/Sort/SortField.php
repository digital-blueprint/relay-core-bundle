<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Rest\Query\Sort;

class SortField
{
    public const ASCENDING_DIRECTION = 'ASC';
    public const DESCENDING_DIRECTION = 'DESC';

    /**
     * @throws SortException
     */
    public function __construct(
        private string $path,
        private string $direction,
    ) {
        self::validateSortDirection($direction);
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    public function getDirection(): string
    {
        return $this->direction;
    }

    /**
     * @throws SortException
     */
    public function setDirection(string $direction): void
    {
        self::validateSortDirection($direction);

        $this->direction = $direction;
    }

    /**
     * @throws SortException
     */
    private static function validateSortDirection(string $direction): void
    {
        if (false === in_array($direction, [self::ASCENDING_DIRECTION, self::DESCENDING_DIRECTION], true)) {
            throw new SortException('Invalid sort direction: '.$direction, SortException::INVALID_SORT_DIRECTION);
        }
    }
}
