<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Rest\Query\Filter\Nodes;

class OperatorType
{
    public const EQUALS_OPERATOR = 'EQ';
    public const LESS_THAN_OPERATOR = 'LT';
    public const LESS_THAN_OR_EQUAL_OPERATOR = 'LTE';
    public const GREATER_THAN_OPERATOR = 'GT';
    public const GREATER_THAN_OR_EQUAL_OPERATOR = 'GTE';
    public const I_STARTS_WITH_OPERATOR = 'I_STARTS_WITH';
    public const I_CONTAINS_OPERATOR = 'I_CONTAINS';
    public const I_ENDS_WITH_OPERATOR = 'I_ENDS_WITH';
    public const IN_ARRAY_OPERATOR = 'IN';
    public const IS_NULL_OPERATOR = 'IS_NULL';

    private const OPERATORS = [
        self::EQUALS_OPERATOR,
        self::LESS_THAN_OPERATOR,
        self::LESS_THAN_OR_EQUAL_OPERATOR,
        self::GREATER_THAN_OPERATOR,
        self::GREATER_THAN_OR_EQUAL_OPERATOR,
        self::I_STARTS_WITH_OPERATOR,
        self::I_CONTAINS_OPERATOR,
        self::I_ENDS_WITH_OPERATOR,
        self::IN_ARRAY_OPERATOR,
        self::IS_NULL_OPERATOR,
    ];

    public static function exists(string $operator): bool
    {
        return in_array($operator, self::OPERATORS, true);
    }
}
