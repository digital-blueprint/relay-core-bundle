<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Rest\Query\Filter\Nodes;

abstract class Node
{
    protected const NODE_TYPE = null;

    /** @var LogicalNode|null */
    protected $parentNodeDefinition;

    public function getParent(): ?LogicalNode
    {
        return $this->parentNodeDefinition;
    }

    protected function __construct(?LogicalNode $parent)
    {
        $this->parentNodeDefinition = $parent;
    }

    public function getNodeType(): string
    {
        return static::NODE_TYPE;
    }

    abstract public function isValid(string &$reason = null): bool;

    abstract public function apply(array $rowData): bool;

    abstract public function toArray(): array;
}
