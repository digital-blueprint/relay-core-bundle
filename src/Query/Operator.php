<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Query;

class Operator
{
    public const EQUALS = 'equals';
    public const IEQUALS = 'iequals';
    public const CONTAINS = 'contains';
    public const ICONTAINS = 'icontains';
}
