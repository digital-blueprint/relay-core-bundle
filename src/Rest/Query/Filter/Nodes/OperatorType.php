<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Rest\Query\Filter\Nodes;

class OperatorType
{
    /**
     * Equality operator (case-sensitive for strings).
     */
    public const EQUALS_OPERATOR = 'EQ';

    /**
     * Numeric less than or equal operator. The value must be of a numeric type.
     */
    public const LESS_THAN_OR_EQUAL_OPERATOR = 'LTE';

    /**
     * Numeric greater than or equal operator. The value must be of a numeric type.
     */
    public const GREATER_THAN_OR_EQUAL_OPERATOR = 'GTE';

    /**
     * Case-insensitive string contains operator. The value must be of type string.
     */
    public const I_CONTAINS_OPERATOR = 'I_CONTAINS';

    /**
     * Case-insensitive string ends with operator. The value must be of type string.
     */
    public const I_ENDS_WITH_OPERATOR = 'I_ENDS_WITH';

    /**
     * Case-insensitive string starts with operator. The value must be of type string.
     */
    public const I_STARTS_WITH_OPERATOR = 'I_STARTS_WITH';

    /*
     * In array operator. The value must be of array type.
     */
    public const IN_ARRAY_OPERATOR = 'IN';

    /*
     * Is null operator. The value is ignored.
     */
    public const IS_NULL_OPERATOR = 'IS_NULL';

    private const ALL = [
        self::EQUALS_OPERATOR,
        self::LESS_THAN_OR_EQUAL_OPERATOR,
        self::GREATER_THAN_OR_EQUAL_OPERATOR,
        self::I_CONTAINS_OPERATOR,
        self::I_ENDS_WITH_OPERATOR,
        self::I_STARTS_WITH_OPERATOR,
        self::IN_ARRAY_OPERATOR,
        self::IS_NULL_OPERATOR,
    ];

    public static function exists(string $operator): bool
    {
        return in_array($operator, self::ALL, true);
    }
}
