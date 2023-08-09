<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Rest\Query\Filter\Nodes;

class NodeType
{
    public const CONSTANT = 'constant';
    public const CONDITION = 'condition';
    public const AND = 'and';
    public const OR = 'or';
    public const NOT = 'not';
}
