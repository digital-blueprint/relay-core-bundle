<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Rest\Query\Filter;

use Dbp\Relay\CoreBundle\Rest\Query\Filter\Nodes\AndNode;
use Dbp\Relay\CoreBundle\Rest\Query\Filter\Nodes\NodeType;

class Filter
{
    /** @var AndNode */
    private $rootNode;

    public static function create(AndNode $rootNode = null): Filter
    {
        return new self($rootNode ?? new AndNode(null));
    }

    protected function __construct(AndNode $rootNode)
    {
        $this->rootNode = $rootNode;
    }

    public function getRootNode(): AndNode
    {
        return $this->rootNode;
    }

    public function isValid(string &$reason = null): bool
    {
        // as opposed to non-root and nodes, no children are ok (=> empty filter)
        if (count($this->rootNode->getChildren()) === 0) {
            return true;
        }

        return $this->rootNode->isValid($reason);
    }

    public function isEmpty(): bool
    {
        return count($this->rootNode->getChildren()) === 0;
    }

    /**
     * @throws \Exception
     */
    public function simplify(): void
    {
        $this->assertIsValid();

        $this->rootNode->simplifyRecursively();
    }

    public function toArray(): array
    {
        return [NodeType::AND.'_0' => $this->rootNode->toArray()];
    }

    /**
     * @return $this
     *
     * @throws \Exception If this filter of the other filter is invalid
     */
    public function combineWith(Filter $otherFilter): Filter
    {
        $this->assertIsValid();

        if ($otherFilter->isValid($reason) === false) {
            throw new \Exception('other filter is invalid: '.$reason);
        }

        foreach ($otherFilter->getRootNode()->getChildren() as $otherFiltersChild) {
            $this->rootNode->appendChild($otherFiltersChild);
        }

        $this->rootNode->simplifyRecursively();

        return $this;
    }

    /**
     * @throws \Exception
     */
    protected function assertIsValid(): void
    {
        if ($this->isValid($reason) === false) {
            throw new \Exception('filter is invalid: '.$reason);
        }
    }
}
