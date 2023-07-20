<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Rest\Query\Filter;

use Dbp\Relay\CoreBundle\Rest\Query\Filter\Nodes\AndNode;
use Dbp\Relay\CoreBundle\Rest\Query\Filter\Nodes\ConditionNode;
use Dbp\Relay\CoreBundle\Rest\Query\Filter\Nodes\LogicalNode;
use Dbp\Relay\CoreBundle\Rest\Query\Filter\Nodes\NotNode;
use Dbp\Relay\CoreBundle\Rest\Query\Filter\Nodes\OperatorType;
use Dbp\Relay\CoreBundle\Rest\Query\Filter\Nodes\OrNode;

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
     * Key in the filter[<key>] parameter for conditions.
     *
     * @var string
     */
    private const CONDITION_KEY = 'condition';

    /**
     * Key in the filter[<key>] parameter for groups.
     *
     * @var string
     */
    private const GROUP_KEY = 'group';

    /**
     * Key in the filter[<id>][<key>] parameter for group membership.
     *
     * @var string
     */
    private const MEMBER_KEY = 'memberOf';

    /**
     * The field key in the filter condition: filter[lorem][condition][<field>].
     *
     * @var string
     */
    private const PATH_KEY = 'path';

    /**
     * The value key in the filter condition: filter[lorem][condition][<value>].
     *
     * @var string
     */
    private const VALUE_KEY = 'value';

    /**
     * The operator key in the condition: filter[lorem][condition][<operator>].
     *
     * @var string
     */
    private const OPERATOR_KEY = 'operator';

    private const EQUALS_OPERATOR = 'EQ';
    private const LESS_THAN_OR_EQUAL_OPERATOR = 'LTE';
    private const GREATER_THAN_OR_EQUAL_OPERATOR = 'GTE';
    private const I_CONTAINS_OPERATOR = 'I_CONTAINS';
    private const I_ENDS_WITH_OPERATOR = 'I_ENDS_WITH';
    private const I_STARTS_WITH_OPERATOR = 'I_STARTS_WITH';
    private const IN_ARRAY_OPERATOR = 'IN';
    private const IS_NULL_OPERATOR = 'IS_NULL';

    /**
     * The allowed condition operators.
     *
     * @var string[]
     */
    private static $allowedOperators = [
        self::EQUALS_OPERATOR,
        self::LESS_THAN_OR_EQUAL_OPERATOR,
        self::GREATER_THAN_OR_EQUAL_OPERATOR,
        self::I_CONTAINS_OPERATOR,
        self::I_ENDS_WITH_OPERATOR,
        self::I_STARTS_WITH_OPERATOR,
        self::IN_ARRAY_OPERATOR,
        self::IS_NULL_OPERATOR,
    ];

    private const ITEM_ID_KEY = 'id';

    private const CONJUNCTION_KEY = 'conjunction';

    private const AND_CONJUNCTION = 'AND';
    private const OR_CONJUNCTION = 'OR';
    private const NOT_AND_CONJUNCTION = 'NOT_AND';
    private const NOT_OR_CONJUNCTION = 'NOT_OR';

    /**
     * Creates a Filter object from an query parameter.
     *
     * @throws FilterException
     */
    public static function createFilterFromQueryParameters(array $filterQueryParameters, array $availableAttributePaths, array &$usedAttributePaths = null): Filter
    {
        return self::buildFilter(self::expand($filterQueryParameters), $availableAttributePaths, $usedAttributePaths);
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
            $item = self::expandShortcut($key, $item);

            // Throw an exception if the query uses the reserved filter id for the
            // root group.
            if ($key === self::ROOT_ID) {
                $msg = sprintf("'%s' is a reserved filter id.", self::ROOT_ID);
                throw new FilterException($msg, FilterException::RESERVED_FILTER_ITEM_ID);
            }

            // Add a memberOf key to all items.
            if (isset($item[self::CONDITION_KEY][self::MEMBER_KEY])) {
                $item[self::MEMBER_KEY] = $item[self::CONDITION_KEY][self::MEMBER_KEY];
                unset($item[self::CONDITION_KEY][self::MEMBER_KEY]);
            } elseif (isset($item[self::GROUP_KEY][self::MEMBER_KEY])) {
                $item[self::MEMBER_KEY] = $item[self::GROUP_KEY][self::MEMBER_KEY];
                unset($item[self::GROUP_KEY][self::MEMBER_KEY]);
            } else {
                $item[self::MEMBER_KEY] = self::ROOT_ID;
            }

            // Add the filter id to all items.
            $item[self::ITEM_ID_KEY] = $key;

            $expanded[$key] = $item;
        }

        return $expanded;
    }

    /**
     * Expands a filter item in case a shortcut was used.
     */
    private static function expandShortcut(string $key, $item): array
    {
        if (!is_array($item)) { // the case for `filter[foo]=bar`
            $item = [self::CONDITION_KEY => [
                self::VALUE_KEY => $item,
                self::PATH_KEY => $key,
                ]];
        } elseif (isset($item[self::VALUE_KEY])) { // the case for filter[foo][value]=bar'
            $item = [self::CONDITION_KEY => [
                self::VALUE_KEY => $item[self::VALUE_KEY],
                self::PATH_KEY => $key,
            ]];
        }

        // for condition items, add default operator if not set
        if (isset($item[self::CONDITION_KEY]) && !isset($item[self::CONDITION_KEY][self::OPERATOR_KEY])) {
            $item[self::CONDITION_KEY][self::OPERATOR_KEY] = self::EQUALS_OPERATOR;
        }

        return $item;
    }

    /**
     * @throws FilterException
     */
    private static function buildFilter(array $items, array $availableAttributePaths, array &$usedAttributePaths = null): Filter
    {
        $rootGroup = [
            self::ITEM_ID_KEY => self::ROOT_ID,
            self::GROUP_KEY => [self::CONJUNCTION_KEY => self::AND_CONJUNCTION],
        ];

        /** @var AndNode */
        $rootNode = self::buildTreeRecursively($rootGroup, null, $items, $availableAttributePaths, $usedAttributePaths);

        return Filter::create($rootNode);
    }

    /**
     * @throws FilterException
     */
    private static function buildTreeRecursively(array $currentItem, ?LogicalNode $currentParentNode, array $items, array $availableAttributePaths, array &$usedAttributePaths = null): LogicalNode
    {
        $currentItemId = $currentItem[self::ITEM_ID_KEY];
        $currentItemConjunction = $currentItem[self::GROUP_KEY][self::CONJUNCTION_KEY];

        switch ($currentItemConjunction) {
            case self::AND_CONJUNCTION:
                $logicalNode = new AndNode($currentParentNode);
                break;
            case self::OR_CONJUNCTION:
                $logicalNode = new OrNode($currentParentNode);
                break;
            case self::NOT_AND_CONJUNCTION:
                $notNode = new NotNode($currentParentNode);
                $logicalNode = new AndNode($notNode);
                break;
            case self::NOT_OR_CONJUNCTION:
                $notNode = new NotNode($currentParentNode);
                $logicalNode = new OrNode($notNode);
                break;
            default:
                throw new FilterException('conjunction undefined: '.$currentItemConjunction, FilterException::CONJUNCTION_UNDEFINED);
        }

        foreach ($items as $item) {
            if ($item[self::MEMBER_KEY] === $currentItemId) {
                if (isset($item[self::GROUP_KEY])) {
                    $childNode = self::buildTreeRecursively($item, $logicalNode, $items, $availableAttributePaths, $usedAttributePaths);
                } elseif (isset($item[self::CONDITION_KEY])) {
                    $childNode = self::createConditionNodeFromCondition($item[self::CONDITION_KEY], $availableAttributePaths, $usedAttributePaths);
                } else {
                    throw new FilterException('invalid filter item', FilterException::FILTER_ITEM_INVALID);
                }
                $logicalNode->appendChild($childNode);
            }
        }

        return $logicalNode;
    }

    /**
     * Creates a ConditionNode from a query parameter.
     *
     * @throws FilterException
     */
    private static function createConditionNodeFromCondition(array $condition, array $availableAttributePaths, array &$usedAttributePaths = null): ConditionNode
    {
        self::validateConditionFilterItem($condition);

        $attributePath = $condition[self::PATH_KEY];
        $value = (isset($condition[self::VALUE_KEY])) ? $condition[self::VALUE_KEY] : null;
        $operator = (isset($condition[self::OPERATOR_KEY])) ? $condition[self::OPERATOR_KEY] : null;

        if (!in_array($attributePath, $availableAttributePaths, true)) {
            throw new FilterException('undefined attribute: '.$attributePath, FilterException::ATTRIBUTE_PATH_UNDEFINED);
        }

        if ($usedAttributePaths !== null) {
            $usedAttributePaths[] = $attributePath;
        }

        return new ConditionNode($attributePath, self::toConditionNodeOperator($operator), $value);
    }

    private static function toConditionNodeOperator(string $operator): string
    {
        switch ($operator) {
            case self::EQUALS_OPERATOR:
                return OperatorType::EQUALS_OPERATOR;
            case self::LESS_THAN_OR_EQUAL_OPERATOR:
                return OperatorType::LESS_THAN_OR_EQUAL_OPERATOR;
            case self::GREATER_THAN_OR_EQUAL_OPERATOR:
                return OperatorType::GREATER_THAN_OR_EQUAL_OPERATOR;
            case self::I_STARTS_WITH_OPERATOR:
                return OperatorType::I_STARTS_WITH_OPERATOR;
            case self::I_CONTAINS_OPERATOR:
                return OperatorType::I_CONTAINS_OPERATOR;
            case self::I_ENDS_WITH_OPERATOR:
                return OperatorType::I_ENDS_WITH_OPERATOR;
            case self::IN_ARRAY_OPERATOR:
                return OperatorType::IN_ARRAY_OPERATOR;
            case self::IS_NULL_OPERATOR:
                return OperatorType::IS_NULL_OPERATOR;
            default:
                throw new \UnexpectedValueException('unsupported operator type: '.$operator);
        }
    }

    /**
     * Validates the filter has the required fields.
     *
     * @throws FilterException
     */
    private static function validateConditionFilterItem(array $conditionFilterItem)
    {
        if (!isset($conditionFilterItem[self::PATH_KEY])) {
            throw new FilterException("Filter parameter is missing a '".self::PATH_KEY."' key.", FilterException::CONDITION_PATH_MISSING);
        }

        $operator = $conditionFilterItem[self::OPERATOR_KEY];
        $value = $conditionFilterItem[self::VALUE_KEY] ?? null;
        if ($value === null) {
            if ($operator !== self::IS_NULL_OPERATOR) {
                throw new FilterException("Filter parameter is missing a '".self::VALUE_KEY."' key.", FilterException::CONDITION_VALUE_ERROR);
            }
        } elseif ($operator === self::IS_NULL_OPERATOR) {
            throw new FilterException('Filters using the '.$operator.' operator must not provide a value.', FilterException::CONDITION_VALUE_ERROR);
        } elseif ($operator === self::IN_ARRAY_OPERATOR) {
            if (!is_array($value)) {
                throw new FilterException('Filters using the "'.$operator.'"" operator must provide an array type value.', FilterException::CONDITION_VALUE_ERROR);
            }
        }

        if (!in_array($operator, self::$allowedOperators, true)) {
            throw new FilterException('Undefined condition operator: '.$operator, FilterException::CONDITION_OPERATOR_UNDEFINED);
        }
    }
}
