<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Query;

class LogicalOperator
{
    public const AND = 'and';
    public const OR = 'or';
    public const AND_NOT = 'and_not';
    public const OR_NOT = 'or_not';
}
