<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Rest\Query\Filter;

use Dbp\Relay\CoreBundle\Rest\Query\Filter\Nodes\ConditionNode;

class FilterTools
{
    public static function mapConditionPaths(Filter $filter, array $pathMapping): void
    {
        $filter->mapConditionNodes(
            function (ConditionNode $conditionNode) use ($pathMapping) {
                if ($pathToSet = $pathMapping[$conditionNode->getPath()] ?? null) {
                    $conditionNode->setPath($pathToSet);
                }

                return $conditionNode;
            });
    }
}
