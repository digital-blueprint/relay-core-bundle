<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Rest\Query\Filter\Nodes;

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
        $this->childNodes[] = new ConditionNode($column, OperatorType::I_CONTAINS_OPERATOR, $value);

        return $this;
    }

    /**
     * @return $this
     *
     * @throws \Exception
     */
    public function equals(string $column, string $value): Node
    {
        $this->childNodes[] = new ConditionNode($column, OperatorType::EQUALS_OPERATOR, $value);

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
                    case NodeType::AND:
                    case NodeType::OR:
                        if ($childNodeType === static::NODE_TYPE) {
                            $childNodes = array_merge($childNodes, $childNode->getChildren());
                            $appendChild = false;
                        }
                        break;
                    // not under not -> both can be removed
                    case NodeType::NOT:
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

    public function toArray(): array
    {
        $childArray = [];
        $currentChildIndex = 0;
        foreach ($this->childNodes as $childNode) {
            $arrayKey = $childNode instanceof LogicalNode ?
                $childNode->getNodeType() : null;
            $childArray[$arrayKey !== null ? $arrayKey.'_'.$currentChildIndex : $currentChildIndex] =
                $childNode->toArray();
            ++$currentChildIndex;
        }

        return $childArray;
    }
}
