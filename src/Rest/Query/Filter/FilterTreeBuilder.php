<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Rest\Query\Filter;

use Dbp\Relay\CoreBundle\Rest\Query\Filter\Nodes\AndNode;
use Dbp\Relay\CoreBundle\Rest\Query\Filter\Nodes\ConditionNode;
use Dbp\Relay\CoreBundle\Rest\Query\Filter\Nodes\LogicalNode;
use Dbp\Relay\CoreBundle\Rest\Query\Filter\Nodes\Node;
use Dbp\Relay\CoreBundle\Rest\Query\Filter\Nodes\NotNode;
use Dbp\Relay\CoreBundle\Rest\Query\Filter\Nodes\OperatorType;
use Dbp\Relay\CoreBundle\Rest\Query\Filter\Nodes\OrNode;

/**
 * An internal node is a node with child nodes.
 */
class FilterTreeBuilder
{
    /** @var LogicalNode */
    private $currentNode;

    public static function create(LogicalNode $rootNode = null): FilterTreeBuilder
    {
        return new FilterTreeBuilder($rootNode);
    }

    public function __construct(LogicalNode $rootNode = null)
    {
        $this->currentNode = $rootNode ?? new AndNode();
    }

    /**
     * @throws FilterException
     */
    public function createFilter(): Filter
    {
        if ($this->currentNode->getParent() !== null) {
            throw new FilterException('Filter tree is incomplete. Did you forget an \'end()\' statement?', FilterException::FILTER_TREE_INVALID);
        }

        return Filter::create($this->currentNode);
    }

    /**
     * @return $this
     */
    public function appendChild(Node $childNodeDefinition): FilterTreeBuilder
    {
        $this->currentNode->appendChild($childNodeDefinition);

        return $this;
    }

    /**
     * @return $this
     */
    public function and(): FilterTreeBuilder
    {
        $andNode = new AndNode();
        $this->currentNode->appendChild($andNode);
        $this->currentNode = $andNode;

        return $this;
    }

    /**
     * @return $this
     */
    public function or(): FilterTreeBuilder
    {
        $orNode = new OrNode();
        $this->currentNode->appendChild($orNode);
        $this->currentNode = $orNode;

        return $this;
    }

    /**
     * @return $this
     */
    public function not(): FilterTreeBuilder
    {
        $notNode = new NotNode();
        $this->currentNode->appendChild($notNode);
        $this->currentNode = $notNode;

        return $this;
    }

    /**
     * @return $this
     *
     * @throws FilterException
     */
    public function end(): FilterTreeBuilder
    {
        if ($this->currentNode === null) {
            throw new FilterException('Filter tree with too many \'end()\' statements.', FilterException::FILTER_TREE_INVALID);
        }

        $this->currentNode = $this->currentNode->getParent();

        return $this;
    }

    /**
     * @return $this
     *
     * @throws FilterException
     */
    public function iContains(string $field, string $value): FilterTreeBuilder
    {
        $this->currentNode->appendChild(new ConditionNode($field, OperatorType::I_CONTAINS_OPERATOR, $value));

        return $this;
    }

    /**
     * @return $this
     *
     * @throws FilterException
     */
    public function equals(string $field, $value): FilterTreeBuilder
    {
        $this->currentNode->appendChild(new ConditionNode($field, OperatorType::EQUALS_OPERATOR, $value));

        return $this;
    }

    /**
     * @return $this
     *
     * @throws FilterException
     */
    public function inArray(string $field, array $value): FilterTreeBuilder
    {
        $this->currentNode->appendChild(new ConditionNode($field, OperatorType::IN_ARRAY_OPERATOR, $value));

        return $this;
    }

    /**
     * @return $this
     *
     * @throws FilterException
     */
    public function isNull(string $field): FilterTreeBuilder
    {
        $this->currentNode->appendChild(new ConditionNode($field, OperatorType::IS_NULL_OPERATOR, null));

        return $this;
    }
}
