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
     * @throws FilterException
     */
    public function simplify(): void
    {
        $this->assertIsValid(__METHOD__.': ');

        $this->rootNode->simplifyRecursively();
    }

    public function toArray(): array
    {
        return [NodeType::AND.'_0' => $this->rootNode->toArray()];
    }

    /**
     * @return $this
     *
     * @throws FilterException If this filter or the other filter is invalid
     */
    public function combineWith(Filter $otherFilter): Filter
    {
        $this->assertIsValid(__METHOD__.': ');

        if ($otherFilter->isValid($reason) === false) {
            throw new FilterException(__METHOD__.': other filter is invalid: '.$reason, FilterException::FILTER_INVALID);
        }

        foreach ($otherFilter->getRootNode()->getChildren() as $otherFiltersChild) {
            $this->rootNode->appendChild($otherFiltersChild);
        }

        $this->rootNode->simplifyRecursively();

        return $this;
    }

    /**
     * @throws FilterException
     */
    public function apply(array $rowData): bool
    {
        $this->assertIsValid(__METHOD__.': ');

        return $this->rootNode->apply($rowData);
    }

    /**
     * @throws FilterException
     */
    private function assertIsValid(string $reasonPrefix = ''): void
    {
        if ($this->isValid($reason) === false) {
            throw new FilterException($reasonPrefix.'filter is invalid: '.$reason, FilterException::FILTER_INVALID);
        }
    }
}
