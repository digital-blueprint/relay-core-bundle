<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Rest\Query\Filter\Nodes;

class OperatorType
{
    /**
     * Equality operator (case-sensitive for strings).
     *
     * Passes if the field is equal to the value.
     *
     * The value and the field must be of the same type.
     */
    public const EQUALS_OPERATOR = 'EQUALS';

    /**
     * Numeric less than or equal operator.
     *
     * Passes if the field is less than or equal to the value.
     *
     * The value and the field must be of a numeric type.
     */
    public const LESS_THAN_OR_EQUAL_OPERATOR = 'LTE';

    /**
     * Numeric greater than or equal operator.
     *
     * Passes if the field is greater than or equal to the value.
     *
     * The value and the field must be of a numeric type.
     */
    public const GREATER_THAN_OR_EQUAL_OPERATOR = 'GTE';

    /**
     * Case-insensitive string contains operator.
     *
     * Passes if the field contains the value.
     *
     * The value and the field must be of type string.
     */
    public const I_CONTAINS_OPERATOR = 'I_CONTAINS';

    /**
     * Case-insensitive string ends with operator.
     *
     * Passes if the field ends with the value.
     *
     * The value and the field must be of type string.
     */
    public const I_ENDS_WITH_OPERATOR = 'I_ENDS_WITH';

    /**
     * Case-insensitive string starts with operator.
     *
     * Passes if the field starts with the value.
     *
     * The value and the field must be of type string.
     */
    public const I_STARTS_WITH_OPERATOR = 'I_STARTS_WITH';

    /*
     * In array operator.
     *
     * Passes if the field equal one of the array items.
     *
     * The value must be of array type. The field must be of a scalar type.
     */
    public const IN_ARRAY_OPERATOR = 'IN';

    /*
     * Has operator.
     *
     * Passes if the field contains a value that matches the value.
     *
     * The value must be of a scalar type. The field must be of array type.
     */
    public const HAS_OPERATOR = 'HAS';

    /*
     * Is null operator.
     *
     * Passes if the field is null.
     *
     * No value must be given.
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
        self::HAS_OPERATOR,
        self::IS_NULL_OPERATOR,
    ];

    private const STRING_OPERATORS = [
        self::I_CONTAINS_OPERATOR,
        self::I_STARTS_WITH_OPERATOR,
        self::I_ENDS_WITH_OPERATOR,
    ];

    public static function exists(string $operator): bool
    {
        return in_array($operator, self::ALL, true);
    }

    public static function isStringOperator(string $operator): bool
    {
        return in_array($operator, self::STRING_OPERATORS, true);
    }
}
