<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\LocalData;

use Dbp\Relay\CoreBundle\Query\Filter;
use Dbp\Relay\CoreBundle\Query\LogicalOperator;
use Dbp\Relay\CoreBundle\Query\Operator;

/**
 * @internal
 */
class LocalQueryFilterFactory
{
    // Logical Operators:
    public $AND = LogicalOperator::AND;
    public $OR = LogicalOperator::OR;

    public $AND_NOT = LogicalOperator::AND_NOT;
    public $OR_NOT = LogicalOperator::OR_NOT;

    // Operators:
    public $CONTAINS = Operator::CONTAINS;
    public $ICONTAINS = Operator::ICONTAINS;
    public $EQUALS = Operator::EQUALS;
    public $IEQUALS = Operator::IEQUALS;

    public static function create($value, string $operator = Operator::ICONTAINS, string $logicalOperator = LogicalOperator::AND): Filter
    {
        return Filter::create('not needed here', $operator, $value, $logicalOperator);
    }
}
