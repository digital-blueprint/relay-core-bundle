<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Query\Filter\Nodes;

/**
 * An internal node is a node with child nodes.
 */
abstract class LogicalNode extends Node
{
    /** @var Node[] */
    protected $childNodes = [];

    public function getChildren(): array
    {
        return $this->childNodes;
    }

    public function and(): AndNode
    {
        $andFilter = new AndNode($this);
        $this->childNodes[] = $andFilter;

        return $andFilter;
    }

    public function or(): OrNode
    {
        $orFilter = new OrNode($this);
        $this->childNodes[] = $orFilter;

        return $orFilter;
    }

    public function not(): NotNode
    {
        $notFilter = new NotNode($this);
        $this->childNodes[] = $notFilter;

        return $notFilter;
    }

    /**
     * @return $this
     *
     * @throws \Exception
     */
    public function icontains(string $column, string $value): Node
    {
        $this->childNodes[] = new ConditionNode($column, ConditionNode::ICONTAINS_OPERATOR, $value);

        return $this;
    }

    /**
     * @return $this
     *
     * @throws \Exception
     */
    public function contains(string $column, string $value): Node
    {
        $this->childNodes[] = new ConditionNode($column, ConditionNode::CONTAINS_OPERATOR, $value);

        return $this;
    }

    /**
     * @return $this
     *
     * @throws \Exception
     */
    public function iequals(string $column, string $value): Node
    {
        $this->childNodes[] = new ConditionNode($column, ConditionNode::IEQAULS_OPERATOR, $value);

        return $this;
    }

    /**
     * @return $this
     *
     * @throws \Exception
     */
    public function equals(string $column, string $value): Node
    {
        $this->childNodes[] = new ConditionNode($column, ConditionNode::EQUALS_OPERATOR, $value);

        return $this;
    }

    /**
     * @return $this
     */
    public function appendChild(Node $childNodeDefinition): LogicalNode
    {
        $this->childNodes[] = $childNodeDefinition;

        return $this;
    }

    public function isValid(string &$reason = null): bool
    {
        foreach ($this->childNodes as $childNodeDefinition) {
            if ($childNodeDefinition->isValid($reason) === false) {
                return false;
            }
        }

        return true;
    }

    public function simplifyRecursively()
    {
        $childNodes = [];
        foreach ($this->childNodes as $childNode) {
            $appendChild = true;
            if ($childNode instanceof LogicalNode) {
                $childNode->simplifyRecursively();

                $childNodeType = $childNode->getNodeType();
                switch ($childNodeType) {
                    // and under and, or under or -> can be reduced to one
                    case self::AND_NODE_TYPE:
                    case self::OR_NODE_TYPE:
                        if ($childNodeType === static::NODE_TYPE) {
                            $childNodes = array_merge($childNodes, $childNode->getChildren());
                            $appendChild = false;
                        }
                        break;
                    // not under not -> both can be removed
                    case self::NOT_NODE_TYPE:
                        $grandChildNode = $childNode->getChildren()[0];
                        if ($grandChildNode instanceof NotNode) {
                            $childNodes[] = $grandChildNode->getChildren()[0];
                            $appendChild = false;
                        }
                        break;
                }
            }
            if ($appendChild) {
                $childNodes[] = $childNode;
            }
        }
        $this->childNodes = $childNodes;
    }

    protected function toArrayInternal(): array
    {
        $childArray = [];
        $currentChildIndex = 0;
        foreach ($this->childNodes as $childNodeDefinition) {
            $arrayKey = $childNodeDefinition instanceof LogicalNode ?
                $childNodeDefinition->getNodeType() : null;
            $childArray[$arrayKey !== null ? $arrayKey.'_'.$currentChildIndex : $currentChildIndex] =
                $childNodeDefinition->toArrayInternal();
            ++$currentChildIndex;
        }

        return $childArray;
    }
}
