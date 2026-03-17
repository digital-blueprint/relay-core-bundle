<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Rest\Query\Filter;

use Dbp\Relay\CoreBundle\Rest\Query\Filter\Nodes\ConditionNode;
use Dbp\Relay\CoreBundle\Rest\Query\Filter\Nodes\Node;
use Dbp\Relay\CoreBundle\Rest\Query\Filter\Nodes\OperatorType;

/**
 * Based on Drupal JSON:API filter module implementation.
 *
 * @see https://github.com/drupal/drupal/blob/11.x/core/modules/jsonapi/src/Query/Filter.php
 * @see https://www.drupal.org/docs/core-modules-and-themes/core-modules/jsonapi-module/filtering
 */
class FromQueryFilterCreator
{
    /**
     * The key for the implicit root group.
     */
    private const ROOT_ID = '@root';

    /**
     * Key in the filter[<id>] parameter for condition nodes.
     *
     * @var string
     */
    private const CONDITION_KEY = 'condition';

    /**
     * Key in the filter[<id>] parameter for logical nodes.
     *
     * @var string
     */
    private const LOGICAL_KEY = 'logical';

    /**
     * Key in the filter[<id>][condition] to define a child relationship.
     *
     * @var string
     */
    private const CHILD_OF_KEY = 'childOf';

    /**
     * Key in the filter condition filter[<id>>][condition] to the define the attribute path.
     *
     * @var string
     */
    private const PATH_KEY = 'path';

    /**
     * Key in the filter condition filter[<id>>][condition] to the define the filter value.
     *
     * @var string
     */
    private const VALUE_KEY = 'value';

    /**
     * Key in the filter condition filter[<id>>][condition] to the define the filter operator.
     *
     * @var string
     */
    private const OPERATOR_KEY = 'operator';

    private const EQUALS_OPERATOR = 'EQUALS';
    private const NOT_EQUALS_OPERATOR = 'NOT_EQUALS';
    private const LESS_THAN_OR_EQUAL_OPERATOR = 'LTE';
    private const GREATER_THAN_OR_EQUAL_OPERATOR = 'GTE';
    private const I_CONTAINS_OPERATOR = 'I_CONTAINS';
    private const I_ENDS_WITH_OPERATOR = 'I_ENDS_WITH';
    private const I_STARTS_WITH_OPERATOR = 'I_STARTS_WITH';
    private const IN_ARRAY_OPERATOR = 'IN';
    private const HAS_OPERATOR = 'HAS';
    private const IS_NULL_OPERATOR = 'IS_NULL';
    private const IS_NOT_NULL_OPERATOR = 'IS_NOT_NULL';

    private const ITEM_ID_KEY = 'id';

    private const LOGICAL_AND = 'AND';
    private const LOGICAL_OR = 'OR';
    private const LOGICAL_NOT = 'NOT';

    /**
     * Creates a Filter object from an query parameter.
     *
     * @param callable(string): bool $isAttributePathDefined
     *
     * @throws FilterException
     */
    public static function createFilterFromQueryParameters(array $filterQueryParameters, callable $isAttributePathDefined): Filter
    {
        return self::buildFilter(self::expand($filterQueryParameters), $isAttributePathDefined);
    }

    /**
     * Expands any filter parameters using shorthand notation.
     *
     * @param array $queryParameters The unexpanded filter data
     *
     * @return array The expanded filter data
     *
     * @throws FilterException
     */
    private static function expand(array $queryParameters): array
    {
        $expanded = [];
        foreach ($queryParameters as $key => $item) {
            $item = self::tryExpandConditionShortcut($key, $item);

            if ($key === self::ROOT_ID) {
                $msg = sprintf("'%s' is a reserved filter id.", self::ROOT_ID);
                throw new FilterException($msg, FilterException::RESERVED_FILTER_ITEM_ID);
            }

            if (isset($item[self::CONDITION_KEY][self::CHILD_OF_KEY])) {
                $item[self::CHILD_OF_KEY] = $item[self::CONDITION_KEY][self::CHILD_OF_KEY];
                unset($item[self::CONDITION_KEY][self::CHILD_OF_KEY]);
            } elseif (isset($item[self::LOGICAL_KEY][self::CHILD_OF_KEY])) {
                $item[self::CHILD_OF_KEY] = $item[self::LOGICAL_KEY][self::CHILD_OF_KEY];
                unset($item[self::LOGICAL_KEY][self::CHILD_OF_KEY]);
            } else {
                $item[self::CHILD_OF_KEY] = self::ROOT_ID;
            }

            $item[self::ITEM_ID_KEY] = $key;
            $expanded[$key] = $item;
        }

        return $expanded;
    }

    /**
     * Expands a filter item in case a shortcut was used.
     */
    private static function tryExpandConditionShortcut(string $key, mixed $item): array
    {
        if (false === is_array($item)) { // the case for 'filter[path]=value0'
            $item = [self::CONDITION_KEY => [
                self::VALUE_KEY => $item,
                self::PATH_KEY => $key,
            ]];
        } elseif (isset($item[self::VALUE_KEY])) { // the case for 'filter[path][value]=value0'
            $item = [self::CONDITION_KEY => [
                self::VALUE_KEY => $item[self::VALUE_KEY],
                self::PATH_KEY => $key,
                self::OPERATOR_KEY => $item[self::OPERATOR_KEY] ?? null,
            ]];
        }

        if (isset($item[self::CONDITION_KEY]) // the case for 'filter[label][condition][value]=value0'
            && false === isset($item[self::CONDITION_KEY][self::OPERATOR_KEY])) {
            // if there is no operator, use the equals operator by default
            $item[self::CONDITION_KEY][self::OPERATOR_KEY] = self::EQUALS_OPERATOR;
        }

        return $item;
    }

    /**
     * @param callable(string): bool $isAttributePathDefined
     *
     * @throws FilterException
     */
    private static function buildFilter(array $items, callable $isAttributePathDefined): Filter
    {
        // filter tree builder appends an AND root node automatically
        $filterTreeBuilder = FilterTreeBuilder::create();
        self::appendChildren(self::ROOT_ID, $filterTreeBuilder, $items, $isAttributePathDefined);

        return $filterTreeBuilder->createFilter();
    }

    /**
     * @throws FilterException
     */
    private static function appendLogicalNode(
        array $groupItem, FilterTreeBuilder $filterTreeBuilder, array $items, callable $isAttributePathDefined): void
    {
        $groupId = $groupItem[self::ITEM_ID_KEY];
        $logicalOperator = $groupItem[self::LOGICAL_KEY][self::OPERATOR_KEY] ?? null;

        switch ($logicalOperator) {
            case self::LOGICAL_AND:
                $filterTreeBuilder->and();
                break;
            case self::LOGICAL_OR:
                $filterTreeBuilder->or();
                break;
            case self::LOGICAL_NOT:
                $filterTreeBuilder->not();
                break;
            default:
                throw new FilterException('logical operator undefined: '.$logicalOperator,
                    FilterException::LOGICAL_OPERATOR_UNDEFINED);
        }

        self::appendChildren($groupId, $filterTreeBuilder, $items, $isAttributePathDefined);

        $filterTreeBuilder->end();
    }

    /**
     * @param callable(string): bool $isAttributePathDefined
     *
     * @throws FilterException
     */
    private static function appendChildren(
        string $parentId, FilterTreeBuilder $filterTreeBuilder, array $items, callable $isAttributePathDefined): void
    {
        foreach ($items as $item) {
            if ($item[self::CHILD_OF_KEY] === $parentId) {
                if (isset($item[self::LOGICAL_KEY])) {
                    self::appendLogicalNode($item, $filterTreeBuilder, $items, $isAttributePathDefined);
                } elseif (isset($item[self::CONDITION_KEY])) {
                    self::appendConditionNode($item[self::CONDITION_KEY], $filterTreeBuilder, $isAttributePathDefined);
                } else {
                    throw new FilterException('invalid filter item', FilterException::FILTER_ITEM_INVALID);
                }
            }
        }
    }

    /**
     * Creates a ConditionNode from a query parameter.
     *
     * @param callable(string): bool $isAttributePathDefined
     *
     * @throws FilterException
     */
    private static function appendConditionNode(array $condition, FilterTreeBuilder $filterTreeBuilder, callable $isAttributePathDefined): void
    {
        $attributePath = $condition[self::PATH_KEY] ?? null;
        if ($attributePath === null) {
            throw new FilterException("Filter parameter is missing a '".self::PATH_KEY."' key.", FilterException::CONDITION_PATH_MISSING);
        }

        $value = $condition[self::VALUE_KEY] ?? null;
        $operator = $condition[self::OPERATOR_KEY] ?? null;

        if (false === $isAttributePathDefined($attributePath)) {
            throw new FilterException('Undefined attribute path: '.$attributePath, FilterException::ATTRIBUTE_PATH_UNDEFINED);
        }

        $filterTreeBuilder->appendChild(new ConditionNode($attributePath,
            self::toConditionNodeOperator($operator), self::toConditionNodeValue($value)));
    }

    /**
     * @throws FilterException
     */
    private static function toConditionNodeOperator(string $operator): string
    {
        return match ($operator) {
            self::EQUALS_OPERATOR => OperatorType::EQUALS_OPERATOR,
            self::NOT_EQUALS_OPERATOR => OperatorType::NOT_EQUALS_OPERATOR,
            self::LESS_THAN_OR_EQUAL_OPERATOR => OperatorType::LESS_THAN_OR_EQUAL_OPERATOR,
            self::GREATER_THAN_OR_EQUAL_OPERATOR => OperatorType::GREATER_THAN_OR_EQUAL_OPERATOR,
            self::I_STARTS_WITH_OPERATOR => OperatorType::I_STARTS_WITH_OPERATOR,
            self::I_CONTAINS_OPERATOR => OperatorType::I_CONTAINS_OPERATOR,
            self::I_ENDS_WITH_OPERATOR => OperatorType::I_ENDS_WITH_OPERATOR,
            self::IN_ARRAY_OPERATOR => OperatorType::IN_ARRAY_OPERATOR,
            self::HAS_OPERATOR => OperatorType::HAS_OPERATOR,
            self::IS_NULL_OPERATOR => OperatorType::IS_NULL_OPERATOR,
            self::IS_NOT_NULL_OPERATOR => OperatorType::IS_NOT_NULL_OPERATOR,
            default => throw new FilterException('undefined condition operator: '.$operator,
                FilterException::CONDITION_OPERATOR_UNDEFINED),
        };
    }

    /**
     * @throws FilterException
     */
    private static function toConditionNodeValue(string|array|null $value): string|int|bool|float|array|null
    {
        if ($value === null) {
            return null;
        } elseif (is_array($value)) {
            $convertedArray = [];
            foreach ($value as $arrayElement) {
                $convertedArray[] = self::toConditionNodeValue($arrayElement);
            }

            return $convertedArray;
        }
        if ($value === 'true') {
            return true;
        } elseif ($value === 'false') {
            return false;
        } elseif (is_numeric($value)) {
            return $value + 0;
        }
        if (preg_match('/^"(.+)"$/', $value, $matches)) {
            return $matches[1]; // The string without the quotes
        }
        throw new FilterException('string values must be quoted', FilterException::CONDITION_VALUE_ERROR);
    }
}
