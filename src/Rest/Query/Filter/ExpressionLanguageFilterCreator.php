<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Rest\Query\Filter;

/**
 * @internal
 *
 * Represents a helper variable that creates filter tree builder in order to define
 * filters in symfony expressions
 */
class ExpressionLanguageFilterCreator
{
    public function create(): FilterTreeBuilder
    {
        return new FilterTreeBuilder();
    }
}
