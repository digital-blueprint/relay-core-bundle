<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Rest;

class ErrorIds
{
    /**
     * Client filter.
     */
    public const FILTER_INVALID_FILTER_KEY_SQUARE_BRACKETS_MISSING = '1400';
    public const FILTER_INVALID = '1401';

    /**
     * Prepared filter.
     */
    public const PREPARED_FILTER_ACCESS_DENIED = '1420';
    public const PREPARED_FILTER_UNDEFINED = '1421';
}
