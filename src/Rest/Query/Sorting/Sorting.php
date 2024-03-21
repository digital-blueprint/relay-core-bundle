<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Rest\Query\Sorting;

class Sorting
{
    /**
     * The fields on which to sort.
     *
     * @var array[]
     */
    protected array $fields;

    /**
     * Constructs a new Sort object.
     *
     * Takes an array of sort fields. Example:
     *   [
     *     [
     *       'path' => 'changed',
     *       'direction' => 'DESC',
     *     ],
     *     [
     *       'path' => 'title',
     *       'direction' => 'ASC',
     *     ],
     *   ]
     *
     * @param array[] $fields the entity query sort fields
     */
    public function __construct(array $fields)
    {
        $this->fields = $fields;
    }
}
