<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Rest\Query\Sorting;

/**
 * Based on Drupal JSON:API sorting module implementation.
 *
 * https://github.com/drupal/drupal/blob/11.x/core/modules/jsonapi/src/Query/Sort.php
 * https://www.drupal.org/docs/core-modules-and-themes/core-modules/jsonapi-module/sorting.
 */
class FromQuerySortingCreator
{
    /**
     * The JSON:API sort key name.
     */
    private const KEY_NAME = 'sort';

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
     * @throws SortingException
     */
    public static function createSortingFromQueryParameter(mixed $sortQueryParameters): Sorting
    {
        // Expand a JSON:API compliant sort into a more expressive sort parameter.
        if (is_string($sortQueryParameters)) {
            $sortQueryParameters = static::expandFieldString($sortQueryParameters);
        }

        // Expand any defaults into the sort array.
        $expanded = [];
        foreach ($sortQueryParameters as $sort_index => $sort_item) {
            $expanded[$sort_index] = static::expandItem($sort_item);
        }

        return new Sorting($expanded);
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
     * @param array $sort_item The raw sort item
     *
     * @return array The expanded sort item
     *
     * @throws SortingException
     */
    protected static function expandItem(array $sort_item): array
    {
        $defaults = [
            self::DIRECTION_KEY => 'ASC',
        ];

        if (!isset($sort_item[self::PATH_KEY])) {
            throw new SortingException('You need to provide a field name for the sort parameter.');
        }

        $expected_keys = [
            self::PATH_KEY,
            self::DIRECTION_KEY,
        ];

        $expanded = array_merge($defaults, $sort_item);

        // Verify correct sort keys.
        if (count(array_diff($expected_keys, array_keys($expanded))) > 0) {
            throw new SortingException('You have provided an invalid set of sort keys.');
        }

        return $expanded;
    }
}
