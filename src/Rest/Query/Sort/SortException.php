<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Rest\Query\Sort;

class SortException extends \Exception
{
    public const INVALID_QUERY_PARAMETER = 1;
    public const ATTRIBUTE_PATH_MISSING = 2;
    public const SORT_KEYS_UNDEFINED = 3;
    public const ATTRIBUTE_PATH_UNDEFINED = 4;
}
