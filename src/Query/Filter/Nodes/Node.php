<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Query\Filter\Nodes;

abstract class Node
{
    public const ROOT_NODE_TYPE = 'root';
    public const CONDITION_NODE_TYPE = 'condition';
    public const AND_NODE_TYPE = 'and';
    public const OR_NODE_TYPE = 'or';
    public const NOT_NODE_TYPE = 'not';

    protected const NODE_TYPE = null;

    /** @var LogicalNode|null */
    protected $parentNodeDefinition;

    public function end(): ?LogicalNode
    {
        return $this->parentNodeDefinition;
    }

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

    abstract protected function toArrayInternal(): array;
}
