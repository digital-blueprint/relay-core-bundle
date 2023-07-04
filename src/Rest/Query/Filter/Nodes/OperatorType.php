<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Rest\Query\Filter\Nodes;

class OperatorType
{
    public const ICONTAINS_OPERATOR = 'icontains';
    public const CONTAINS_OPERATOR = 'contains';
    public const IEQAULS_OPERATOR = 'iequals';
    public const EQUALS_OPERATOR = 'equals';

    private const OPERATORS = [
        self::ICONTAINS_OPERATOR,
        self::CONTAINS_OPERATOR,
        self::IEQAULS_OPERATOR,
        self::EQUALS_OPERATOR,
    ];

    public static function exists(string $operator): bool
    {
        return in_array($operator, self::OPERATORS, true);
    }
}
