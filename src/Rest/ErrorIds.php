<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Rest;

class ErrorIds
{
    /**
     * Client filter.
     */
    public const FILTER_PARAMETER_MUST_BE_AN_ARRAY = 'core:filter-parameter-must-be-an-array';
    public const FILTER_INVALID = 'core:filter-invalid';

    /**
     * Prepared filter.
     */
    public const PREPARED_FILTER_ACCESS_DENIED = 'core:prepared-filter-access-denied';
    public const PREPARED_FILTER_UNDEFINED = 'core:prepared-filter-undefined';
}
