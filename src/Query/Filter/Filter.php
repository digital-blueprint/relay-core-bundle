<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Query\Filter;

use Dbp\Relay\CoreBundle\Query\Filter\Nodes\AndNode;

class Filter extends AndNode
{
    public static function create(): Filter
    {
        return new self();
    }

    protected function __construct()
    {
        parent::__construct(null);
    }

    public function isValid(string &$reason = null): bool
    {
        // as opposed to non-root and nodes, no children are ok (=> empty filter)
        if (count($this->childNodes) === 0) {
            return true;
        }

        return parent::isValid($reason);
    }

    public function isEmpty(): bool
    {
        return count($this->getChildren()) === 0;
    }

    /**
     * @throws \Exception
     */
    public function simplify(): void
    {
        $this->assertIsValid();

        $this->simplifyRecursively();
    }

    public function toArray(): array
    {
        return [self::NODE_TYPE.'_0' => $this->toArrayInternal()];
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

        foreach ($otherFilter->getChildren() as $otherFiltersChild) {
            $this->appendChild($otherFiltersChild);
        }

        $this->simplifyRecursively();

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
