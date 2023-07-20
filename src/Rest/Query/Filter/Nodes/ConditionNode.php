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

    /** @var string */
    private $field;

    /** @var string */
    private $operator;

    /** @var mixed */
    private $value;

    /**
     * @throws FilterException
     */
    public function __construct(string $field, string $operator, $value)
    {
        if ($field === '') {
            throw new FilterException('condition field must not be empty', FilterException::CONDITION_FIELD_EMPTY);
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
        } elseif (OperatorType::exists($this->operator) === false) {
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
            case OperatorType::I_CONTAINS_OPERATOR:
                return \str_contains(strtolower($columnValue), strtolower($this->value));
            case OperatorType::EQUALS_OPERATOR:
                return $columnValue === $this->value;
            default:
                throw new \RuntimeException('unimplemented condition operator: '.$this->operator);
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
