<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Query\Filter\Nodes;

class NotNode extends LogicalNode
{
    protected const NODE_TYPE = self::NOT_NODE_TYPE;

    public function __construct(LogicalNode $parent)
    {
        parent::__construct($parent);
    }

    public function isValid(string &$reason = null): bool
    {
        if (count($this->childNodes) !== 1) {
            $reason = 'logical \'not\' node must have exactly one child';

            return false;
        }

        return parent::isValid($reason);
    }

    /**
     * @throws \Exception
     */
    public function apply(array $rowData): bool
    {
        if (count($this->childNodes) !== 1) {
            throw new \Exception('filter invalid: \'not\' must have exactly one child');
        }

        return $this->childNodes[0]->apply($rowData) === false;
    }
}
