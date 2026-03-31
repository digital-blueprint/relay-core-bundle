<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Rest\Query\Filter\Nodes;

use Dbp\Relay\CoreBundle\Rest\Query\Filter\FilterException;

class ConditionNode extends Node
{
    public const PATH_KEY = 'path';
    public const OPERATOR_KEY = 'operator';
    public const VALUE_KEY = 'value';

    protected const NODE_TYPE = NodeType::CONDITION;

    private string $path;
    private string $operator;
    private mixed $value;

    /**
     * @throws FilterException
     */
    public function __construct(string $path, string $operator, mixed $value)
    {
        parent::__construct();

        if ($path === '') {
            throw new FilterException('path must not be empty', FilterException::CONDITION_PATH_EMPTY);
        }

        if (OperatorType::exists($operator) === false) {
            throw new FilterException('undefined condition operator: '.$operator, FilterException::CONDITION_OPERATOR_UNDEFINED);
        }

        if (in_array($operator, [
            OperatorType::IS_NULL_OPERATOR,
            OperatorType::IS_NOT_NULL_OPERATOR,
        ], true)) {
            if ($value !== null) {
                throw new FilterException('Filters using the "'.$operator.
                    '" operator must not provide a value.', FilterException::CONDITION_VALUE_ERROR);
            }
        } elseif ($value === null) {
            throw new FilterException('Filters using the "'.$operator.
                '"" operator require a value.', FilterException::CONDITION_VALUE_ERROR);
        }

        if (OperatorType::isStringOperator($operator) && false === is_string($value)) {
            throw new FilterException('Filters using the "'.$operator.
                '"" operator require a non-empty string value', FilterException::CONDITION_VALUE_ERROR);
        } elseif ($operator === OperatorType::IN_ARRAY_OPERATOR && false === is_array($value)) {
            throw new FilterException('Filters using the "'.$operator.
                '"" operator require an array value.', FilterException::CONDITION_VALUE_ERROR);
        } elseif ($operator === OperatorType::HAS_OPERATOR && false === is_scalar($value)) {
            throw new FilterException('Filters using the "'.$operator.
                '"" operator require a scalar value', FilterException::CONDITION_VALUE_ERROR);
        }

        $this->path = $path;
        $this->operator = $operator;
        $this->value = $value;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @throws FilterException
     */
    public function setPath(string $path): void
    {
        if ($path === '') {
            throw new FilterException('condition path must not be empty', FilterException::CONDITION_PATH_EMPTY);
        }

        $this->path = $path;
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

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function setValue(mixed $value): void
    {
        $this->value = $value;
    }

    public function isValid(?string &$reason = null): bool
    {
        if ($this->path === '') {
            $reason = 'condition path must not be empty';

            return false;
        } elseif (OperatorType::exists($this->operator) === false) {
            $reason = 'unknown operator: '.$this->operator;

            return false;
        }

        return true;
    }

    /**
     * Indicates whether this condition node is reflected directly (unconditionally) in the filtered results,
     * i.e., there are neither OR nor NOT nodes in its ancestry.
     */
    public function isReflectedDirectly(): bool
    {
        $currentNode = $this;
        while ($currentNode = $currentNode->getParent()) {
            if (false === ($currentNode instanceof AndNode)) {
                return false;
            }
        }

        return true;
    }

    public function apply(array $rowData): bool
    {
        $columnValue = $rowData[$this->path] ?? null;
        switch ($this->operator) {
            case OperatorType::I_CONTAINS_OPERATOR:
                return \str_contains(strtolower($columnValue), strtolower($this->value));
            case OperatorType::EQUALS_OPERATOR:
                return $columnValue === $this->value;
            case OperatorType::NOT_EQUALS_OPERATOR:
                return $columnValue !== $this->value;
            case OperatorType::I_STARTS_WITH_OPERATOR:
                return str_starts_with(strtolower($columnValue), strtolower($this->value));
            case OperatorType::I_ENDS_WITH_OPERATOR:
                return str_ends_with(strtolower($columnValue), strtolower($this->value));
            case OperatorType::GREATER_THAN_OR_EQUAL_OPERATOR:
                return $columnValue >= $this->value;
            case OperatorType::LESS_THAN_OR_EQUAL_OPERATOR:
                return $columnValue <= $this->value;
            case OperatorType::IN_ARRAY_OPERATOR:
                return is_array($this->value) && in_array($columnValue, $this->value, true);
            case OperatorType::HAS_OPERATOR:
                return is_array($columnValue) && in_array($this->value, $columnValue, true);
            case OperatorType::IS_NULL_OPERATOR:
                return $columnValue === null;
            case OperatorType::IS_NOT_NULL_OPERATOR:
                return $columnValue !== null;
            default:
                throw new \UnexpectedValueException('unimplemented condition operator: '.$this->operator);
        }
    }

    public function toArray(): array
    {
        return [
            self::PATH_KEY => $this->path,
            self::OPERATOR_KEY => $this->operator,
            self::VALUE_KEY => $this->value,
        ];
    }
}
