<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Rest\Query\Filter;

use Dbp\Relay\CoreBundle\Rest\Query\Filter\Nodes\AndNode;
use Dbp\Relay\CoreBundle\Rest\Query\Filter\Nodes\ConditionNode;
use Dbp\Relay\CoreBundle\Rest\Query\Filter\Nodes\LogicalNode;
use Dbp\Relay\CoreBundle\Rest\Query\Filter\Nodes\NotNode;
use Dbp\Relay\CoreBundle\Rest\Query\Filter\Nodes\OperatorType;
use Dbp\Relay\CoreBundle\Rest\Query\Filter\Nodes\OrNode;

class FromQueryParameterFilterCreator
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

    /**
     * The allowed condition operators.
     *
     * @var string[]
     */
    private static $allowedOperators = [
        '=', '<>',
        '>', '>=', '<', '<=',
        'STARTS_WITH', 'CONTAINS', 'ENDS_WITH',
        'IN', 'NOT IN',
        'BETWEEN', 'NOT BETWEEN',
        'IS NULL', 'IS NOT NULL',
    ];

    private const ITEM_ID_KEY = 'id';

    /**
     * Creates a Filter object from an query parameter.
     *
     * @throws \Exception
     */
    public static function createFilter(array $filterQueryParameters): Filter
    {
        return static::buildFilter(static::expand($filterQueryParameters));
    }

    /**
     * Expands any filter parameters using shorthand notation.
     *
     * @param array $queryParameters The unexpanded filter data
     *
     * @return array The expanded filter data
     */
    protected static function expand(array $queryParameters): array
    {
        $expanded = [];
        foreach ($queryParameters as $key => $item) {
            // Allow extreme shorthand filters, f.e. `?filter[promote]=1`.
            if (!is_array($item)) {
                $item = [
                    self::VALUE_KEY => $item,
                ];
            }

            // Throw an exception if the query uses the reserved filter id for the
            // root group.
            if ($key === self::ROOT_ID) {
                $msg = sprintf("'%s' is a reserved filter id.", self::ROOT_ID);
                throw new \UnexpectedValueException($msg);
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

            // Expands shorthand filters.
            $expanded[$key] = self::expandItem($key, $item);
        }

        return $expanded;
    }

    /**
     * Expands a filter item in case a shortcut was used.
     *
     * Possible cases for the conditions:
     *   1. filter[uuid][value]=1234.
     *   2. filter[0][condition][field]=uuid&filter[0][condition][value]=1234.
     *   3. filter[uuid][condition][value]=1234.
     *   4. filter[uuid][value]=1234&filter[uuid][group]=my_group.
     *
     * @return array The expanded filter item
     */
    protected static function expandItem(string $filterIndex, array $filterItem): array
    {
        if (isset($filterItem[self::VALUE_KEY])) {
            if (!isset($filterItem[self::PATH_KEY])) {
                $filterItem[self::PATH_KEY] = $filterIndex;
            }

            $filterItem = [
                self::CONDITION_KEY => $filterItem,
                self::MEMBER_KEY => $filterItem[self::MEMBER_KEY],
            ];
        }

        if (!isset($filterItem[self::CONDITION_KEY][self::OPERATOR_KEY])) {
            $filterItem[self::CONDITION_KEY][self::OPERATOR_KEY] = '=';
        }

        return $filterItem;
    }

    /**
     * @throws \Exception
     */
    protected static function buildFilter(array $items): Filter
    {
        $rootGroup = [
            self::ITEM_ID_KEY => self::ROOT_ID,
            self::GROUP_KEY => ['conjunction' => 'AND'],
        ];

        /** @var AndNode */
        $rootNode = self::buildTreeRecursively($rootGroup, null, $items);

        return Filter::create($rootNode);
    }

    /**
     * @throws \Exception
     */
    protected static function buildTreeRecursively(array $currentItem, ?LogicalNode $currentParentNode, array $items): LogicalNode
    {
        $currentItemId = $currentItem[self::ITEM_ID_KEY];

        switch ($currentItem[self::GROUP_KEY]['conjunction']) {
            case 'AND':
                $logicalNode = new AndNode($currentParentNode);
                break;
            case 'OR':
                $logicalNode = new OrNode($currentParentNode);
                break;
            case 'NOT':
                $logicalNode = new NotNode($currentParentNode);
                break;
            default:
                throw new \InvalidArgumentException('invalid conjunction: '.$currentItem[self::GROUP_KEY]['conjunction']);
        }

        foreach ($items as $item) {
            if ($item[self::MEMBER_KEY] === $currentItemId) {
                if (isset($item[self::GROUP_KEY])) {
                    $childNode = self::buildTreeRecursively($item, $logicalNode, $items);
                } elseif (isset($item[self::CONDITION_KEY])) {
                    $childNode = self::createConditionNodeFromQueryParameter($item[self::CONDITION_KEY]);
                } else {
                    throw new \InvalidArgumentException('invalid filter query parameter item');
                }
                $logicalNode->appendChild($childNode);
            }
        }

        return $logicalNode;
    }

    /**
     * Creates a ConditionNode from a query parameter.
     *
     * @param array $parameter the `filter[condition]` query parameter from the request
     *
     * @throws \Exception
     */
    protected static function createConditionNodeFromQueryParameter(array $parameter): ConditionNode
    {
        self::validate($parameter);

        $field = $parameter[self::PATH_KEY];
        $value = (isset($parameter[self::VALUE_KEY])) ? $parameter[self::VALUE_KEY] : null;
        $operator = (isset($parameter[self::OPERATOR_KEY])) ? $parameter[self::OPERATOR_KEY] : null;

        return new ConditionNode($field, self::toConditionNodeOpertor($operator), $value);
    }

    protected static function toConditionNodeOpertor(string $operator): string
    {
        switch ($operator) {
            case 'CONTAINS':
                return OperatorType::CONTAINS_OPERATOR;
            case '=':
                return OperatorType::EQUALS_OPERATOR;
            default:
                throw new \InvalidArgumentException('unsupported operator type: '.$operator);
        }
    }

    /**
     * Validates the filter has the required fields.
     *
     * @throws \Exception
     */
    protected static function validate(array $parameter)
    {
        $valid_key_combinations = [
            [self::PATH_KEY, self::VALUE_KEY],
            [self::PATH_KEY, self::OPERATOR_KEY],
            [self::PATH_KEY, self::VALUE_KEY, self::OPERATOR_KEY],
        ];

        $given_keys = array_keys($parameter);
        $valid_key_set = array_reduce($valid_key_combinations, function ($valid, $set) use ($given_keys) {
            return ($valid) ?: count(array_diff($set, $given_keys)) === 0;
        }, false);

        $has_operator_key = isset($parameter[self::OPERATOR_KEY]);
        $has_path_key = isset($parameter[self::PATH_KEY]);
        $has_value_key = isset($parameter[self::VALUE_KEY]);

        if (!$valid_key_set) {
            // Try to provide a more specific exception is a key is missing.
            if (!$has_operator_key) {
                if (!$has_path_key) {
                    throw new \Exception("Filter parameter is missing a '".self::PATH_KEY."' key.");
                }
                if (!$has_value_key) {
                    throw new \Exception("Filter parameter is missing a '".self::VALUE_KEY."' key.");
                }
            }

            // Catchall exception.
            $reason = 'You must provide a valid filter condition. Check that you have set the required keys for your filter.';
            throw new \Exception($reason);
        }

        if ($has_operator_key) {
            $operator = $parameter[self::OPERATOR_KEY];
            if (!in_array($operator, self::$allowedOperators, true)) {
                $reason = "The '".$operator."' operator is not allowed in a filter parameter.";
                throw new \Exception($reason);
            }

            if (in_array($operator, ['IS NULL', 'IS NOT NULL'], true) && $has_value_key) {
                $reason = "Filters using the '".$operator."' operator should not provide a value.";
                throw new \Exception($reason);
            }
        }
    }
}
