<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Rest\Query\Filter;

class FilterException extends \Exception
{
    public const CONDITION_FIELD_EMPTY = 1;
    public const CONDITION_OPERATOR_UNDEFINED = 2;
    public const RESERVED_FILTER_ITEM_ID = 3;
    public const CONJUNCTION_UNDEFINED = 4;
    public const ATTRIBUTE_PATH_UNDEFINED = 5;
    public const FILTER_ITEM_INVALID = 6;
    public const CONDITION_PATH_MISSING = 7;
    public const CONDITION_VALUE_ERROR = 8;
    public const FILTER_TREE_INVALID = 9;
    public const FILTER_INVALID = 10;
}
