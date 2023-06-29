<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Query\Filter\Nodes;

class ConditionNode extends Node
{
    public const ICONTAINS_OPERATOR = 'icontains';
    public const CONTAINS_OPERATOR = 'contains';
    public const IEQAULS_OPERATOR = 'iequals';
    public const EQUALS_OPERATOR = 'equals';

    public const FIELD_KEY = 'field';
    public const OPERATOR_KEY = 'operator';
    public const VALUE_KEY = 'value';

    protected const NODE_TYPE = self::CONDITION_NODE_TYPE;

    private const OPERATORS = [
        self::ICONTAINS_OPERATOR,
        self::CONTAINS_OPERATOR,
        self::IEQAULS_OPERATOR,
        self::EQUALS_OPERATOR,
    ];

    /** @var string */
    private $field;

    /** @var string */
    private $operator;

    /** @var mixed */
    private $value;

    /**
     * @throws \Exception
     */
    public function __construct(string $column, string $operator, $value)
    {
        if ($column === '') {
            throw new \Exception('condition column must not be empty');
        }

        if (!in_array($operator, self::OPERATORS, true)) {
            throw new \Exception('unknown condition operator: '.$operator);
        }

        $this->field = $column;
        $this->operator = $operator;
        $this->value = $value;
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getOperator(): string
    {
        return $this->operator;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    public function isValid(string &$reason = null): bool
    {
        if ($this->field === '') {
            $reason = 'column must not be empty';

            return false;
        } elseif (in_array($this->operator, self::OPERATORS, true) === false) {
            $reason = 'unknown operator: '.$this->operator;

            return false;
        }

        return true;
    }

    /**
     * @throws \Exception
     */
    public function apply(array $rowData): bool
    {
        $columnValue = $rowData[$this->field] ?? null;
        switch ($this->operator) {
            case self::ICONTAINS_OPERATOR:
                return \str_contains(strtolower($columnValue), strtolower($this->value));
            case self::CONTAINS_OPERATOR:
                return \str_contains($columnValue, $this->value);
            case self::IEQAULS_OPERATOR:
                return strtolower($columnValue) === strtolower($this->value);
            case self::EQUALS_OPERATOR:
                return $columnValue === $this->value;
            default:
                throw new \Exception('unimplemented condition operator: '.$this->operator);
        }
    }

    protected function toArrayInternal(): array
    {
        return [
            self::FIELD_KEY => $this->field,
            self::OPERATOR_KEY => $this->operator,
            self::VALUE_KEY => $this->value,
            ];
    }
}
