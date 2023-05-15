<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Query;

class Filter
{
    private $field;
    private $operator;
    private $value;
    private $logicalOperator;

    public static function create(string $field, string $operator, $value, string $logicalOperator): Filter
    {
        return new self($field, $operator, $value, $logicalOperator);
    }

    public function __construct(string $field, string $operator, $value, string $logicalOperator = LogicalOperator::AND)
    {
        $this->field = $field;
        $this->operator = $operator;
        $this->value = $value;
        $this->logicalOperator = $logicalOperator;
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getOperator(): string
    {
        return $this->operator;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function getLogicalOperator(): string
    {
        return $this->logicalOperator;
    }
}
