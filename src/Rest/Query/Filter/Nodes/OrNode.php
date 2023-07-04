<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Rest\Query\Filter\Nodes;

class OrNode extends LogicalNode
{
    protected const NODE_TYPE = NodeType::OR;

    public function __construct(LogicalNode $parent)
    {
        parent::__construct($parent);
    }

    public function isValid(string &$reason = null): bool
    {
        if (count($this->childNodes) === 0) {
            $reason = 'logical \'or\' node must have at least one child';

            return false;
        }

        return parent::isValid($reason);
    }

    /**
     * @throws \Exception
     */
    public function apply(array $rowData): bool
    {
        if (count($this->childNodes) === 0) {
            throw new \Exception('filter invalid: \'or\' must have at least one child');
        }

        foreach ($this->childNodes as $child) {
            if ($child->apply($rowData) === true) {
                return true;
            }
        }

        return false;
    }
}
