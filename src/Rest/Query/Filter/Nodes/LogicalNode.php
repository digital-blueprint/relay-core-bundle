<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Rest\Query\Filter\Nodes;

use Dbp\Relay\CoreBundle\Helpers\Tools;

/**
 * A logical node is a node with child nodes.
 */
abstract class LogicalNode extends Node
{
    /** @var Node[] */
    protected $childNodes = [];

    public function getChildren(): array
    {
        return $this->childNodes;
    }

    /**
     * @return $this
     */
    public function appendChild(Node $childNode): LogicalNode
    {
        $this->childNodes[] = $childNode;
        $childNode->setParent($this);

        return $this;
    }

    /**
     * @return $this
     */
    public function removeChild(Node $childNode): LogicalNode
    {
        Tools::removeValueFromArray($this->childNodes, $childNode, true, true);
        $childNode->setParent(null);

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
                    // (A && B) && C => A && B && C, or: (A || B) || C => A || B || C
                    // (A) && B => A && B, or: (A) || B => A || B
                    // () && B => B, or: () && B => B
                    case NodeType::AND:
                    case NodeType::OR:
                        if ($childNodeType === static::NODE_TYPE ||
                            count($childNode->getChildren()) <= 1) {
                            $childNodes = array_merge($childNodes, $childNode->getChildren());
                            $appendChild = false;
                        }
                        break;

                    // !(!A) => A
                    case NodeType::NOT:
                        $grandChildNode = $childNode->getChildren()[0];
                        if ($grandChildNode instanceof NotNode) {
                            $childNodes[] = $grandChildNode->getChildren()[0];
                            $appendChild = false;
                        }
                        break;
                }
            } elseif ($childNode instanceof ConstantNode) {
                if (($childNode->isTrue() && static::NODE_TYPE === NodeType::OR) ||
                    ($childNode->isFalse() && static::NODE_TYPE === NodeType::AND)) {
                    // A || true => true
                    // A && false => false
                    $childNodes = [$childNode];
                    break;
                } else {
                    // A || false => A
                    // A && true => A
                    $appendChild = false;
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
