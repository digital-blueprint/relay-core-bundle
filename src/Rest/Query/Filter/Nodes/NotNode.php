<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Rest\Query\Filter\Nodes;

class NotNode extends LogicalNode
{
    protected const NODE_TYPE = NodeType::NOT;

    public function isValid(string &$reason = null): bool
    {
        if (count($this->childNodes) !== 1) {
            $reason = 'logical \'not\' node must have exactly one child';

            return false;
        }

        return parent::isValid($reason);
    }

    public function apply(array $rowData): bool
    {
        return $this->childNodes[0]->apply($rowData) === false;
    }
}
