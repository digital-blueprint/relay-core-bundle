<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Rest\Query\Filter\Nodes;

use Dbp\Relay\CoreBundle\Rest\Query\Filter\FilterException;

class ConditionNode extends Node
{
    public const FIELD_KEY = 'field';
    public const OPERATOR_KEY = 'operator';
    public const VALUE_KEY = 'value';

    protected const NODE_TYPE = NodeType::CONDITION;

    private string $field;
    private string $operator;
    private mixed $value;

    /**
     * @throws FilterException
     */
    public function __construct(string $field, string $operator, mixed $value)
    {
        parent::__construct();

        if ($field === '') {
            throw new FilterException('field must not be empty', FilterException::CONDITION_FIELD_EMPTY);
        }

        if (OperatorType::exists($operator) === false) {
            throw new FilterException('undefined condition operator: '.$operator, FilterException::CONDITION_OPERATOR_UNDEFINED);
        }

        $this->field = $field;
        $this->operator = $operator;
        $this->value = $value;
    }

    public function getField(): string
    {
        return $this->field;
    }

    /**
     * @throws FilterException
     */
    public function setField(string $field): void
    {
        if ($field === '') {
            throw new FilterException('field must not be empty', FilterException::CONDITION_FIELD_EMPTY);
        }

        $this->field = $field;
    }

    public function getOperator(): string
    {
        return $this->operator;
    }

    /**
     * @throws FilterException
     */
    public function setOperator(string $operator): void
    {
        if (OperatorType::exists($operator) === false) {
            throw new FilterException('undefined condition operator: '.$operator, FilterException::CONDITION_OPERATOR_UNDEFINED);
        }

        $this->operator = $operator;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value): void
    {
        $this->value = $value;
    }

    public function isValid(?string &$reason = null): bool
    {
        if ($this->field === '') {
            $reason = 'field must not be empty';

            return false;
        } elseif (OperatorType::exists($this->operator) === false) {
            $reason = 'unknown operator: '.$this->operator;

            return false;
        }

        return true;
    }

    public function apply(array $rowData): bool
    {
        $columnValue = $rowData[$this->field] ?? null;
        switch ($this->operator) {
            case OperatorType::I_CONTAINS_OPERATOR:
                return \str_contains(strtolower($columnValue), strtolower($this->value));
            case OperatorType::EQUALS_OPERATOR:
                return $columnValue === $this->value;
            default:
                throw new \UnexpectedValueException('unimplemented condition operator: '.$this->operator);
        }
    }

    public function toArray(): array
    {
        return [
            self::FIELD_KEY => $this->field,
            self::OPERATOR_KEY => $this->operator,
            self::VALUE_KEY => $this->value,
        ];
    }
}
