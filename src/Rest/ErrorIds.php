<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Rest;

class ErrorIds
{
    /**
     * Query filter.
     */
    public const FILTER_PARAMETER_MUST_BE_AN_ARRAY = 'core:filter-parameter-must-be-an-array';
    public const FILTER_INVALID = 'core:filter-invalid';
    public const QUERY_FILTERS_DISABLED = 'core:query-filters-disabled';

    /**
     * Prepared filter.
     */
    public const PREPARED_FILTER_ACCESS_DENIED = 'core:prepared-filter-access-denied';
    public const PREPARED_FILTER_UNDEFINED = 'core:prepared-filter-undefined';
    public const PREPARED_FILTERS_DISABLED = 'core:prepared-filters-disabled';

    /**
     * Query sort.
     */
    public const SORT_INVALID = 'core:sort-invalid';
    public const SORT_DISABLED = 'core:sort-disabled';
}
