<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Rest\Query\Filter\Nodes;

class ConstantNode extends Node
{
    protected const NODE_TYPE = NodeType::CONSTANT;

    /** @var bool */
    private $value;

    public function __construct(bool $value)
    {
        parent::__construct();

        $this->value = $value;
    }

    public function isTrue(): bool
    {
        return $this->value;
    }

    public function isFalse(): bool
    {
        return $this->value === false;
    }

    public function isValid(?string &$reason = null): bool
    {
        return true;
    }

    public function apply(array $rowData): bool
    {
        return $this->value;
    }

    public function toArray(): array
    {
        return [$this->value];
    }
}
