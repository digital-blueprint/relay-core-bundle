<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Rest\Query\Sort;

/**
 * Based on Drupal JSON:API sorting module implementation.
 *
 * https://github.com/drupal/drupal/blob/11.x/core/modules/jsonapi/src/Query/Sort.php
 * https://www.drupal.org/docs/core-modules-and-themes/core-modules/jsonapi-module/sorting.
 */
class FromQuerySortCreator
{
    /**
     * The field key in the sort parameter: sort[lorem][<field>].
     */
    private const PATH_KEY = 'path';

    /**
     * The direction key in the sort parameter: sort[lorem][<direction>].
     */
    private const DIRECTION_KEY = 'direction';

    /**
     * Creates a Sort object from a query parameter.
     *
     * @param mixed $sortQueryParameters The `sort` query parameter from the Symfony request object
     *
     * @throws SortException
     */
    public static function createSortingFromQueryParameter(mixed $sortQueryParameters, array $availableAttributePaths): Sort
    {
        // Expand a JSON:API compliant sort into a more expressive sort parameter.
        if (is_string($sortQueryParameters)) {
            $sortQueryParameters = static::expandFieldString($sortQueryParameters);
        } elseif (!is_array($sortQueryParameters)) {
            throw new SortException('Invalid sort parameter type: Must be string or array. ',
                SortException::INVALID_QUERY_PARAMETER);
        }

        // Expand any defaults into the sort array.
        $expanded = [];
        foreach ($sortQueryParameters as $sortIndex => $sortItem) {
            $expanded[] = static::expandItem($sortItem, $availableAttributePaths);
        }

        return new Sort($expanded);
    }

    /**
     * Expands a simple string sort into a more expressive sort that we can use.
     *
     * @param string $fields The comma separated list of fields to expand into an array
     *
     * @return array The expanded sort
     */
    protected static function expandFieldString(string $fields): array
    {
        return array_map(function ($field) {
            $sort = [];

            if ($field[0] === '-') {
                $sort[self::DIRECTION_KEY] = 'DESC';
                $sort[self::PATH_KEY] = substr($field, 1);
            } else {
                $sort[self::DIRECTION_KEY] = 'ASC';
                $sort[self::PATH_KEY] = $field;
            }

            return $sort;
        }, explode(',', $fields));
    }

    /**
     * Expands a sort item in case a shortcut was used.
     *
     * @param array $sortItem The raw sort item
     *
     * @return array The expanded sort item
     *
     * @throws SortException
     */
    protected static function expandItem(array $sortItem, array $availableAttributePaths): array
    {
        $defaults = [
            self::DIRECTION_KEY => 'ASC',
        ];

        $attributePath = $sortItem[self::PATH_KEY] ?? null;
        if ($attributePath === null) {
            throw new SortException('Sort parameter is missing a \''.self::PATH_KEY.'\' key.', SortException::ATTRIBUTE_PATH_MISSING);
        }
        if (!in_array($attributePath, $availableAttributePaths, true)) {
            throw new SortException('Undefined attribute path: '.$attributePath, SortException::ATTRIBUTE_PATH_UNDEFINED);
        }

        $expected_keys = [
            self::PATH_KEY,
            self::DIRECTION_KEY,
        ];

        $expanded = array_merge($defaults, $sortItem);

        // Verify correct sort keys.
        if (!empty(array_diff($expected_keys, array_keys($expanded)))) {
            throw new SortException('You have provided an invalid set of sort keys.', SortException::SORT_KEYS_UNDEFINED);
        }

        return $expanded;
    }
}
