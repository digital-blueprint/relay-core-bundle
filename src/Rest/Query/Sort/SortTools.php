<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Rest\Query\Sort;

class SortTools
{
    public static function mapSortPaths(Sort $sort, array $pathMapping): void
    {
        foreach ($sort->getSortFields() as $sortFieldNode) {
            if ($pathToSet = $pathMapping[$sortFieldNode->getPath()] ?? null) {
                $sortFieldNode->setPath($pathToSet);
            }
        }
    }
}
