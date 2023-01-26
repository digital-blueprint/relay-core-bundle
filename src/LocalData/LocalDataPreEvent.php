<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\LocalData;

use Symfony\Contracts\EventDispatcher\Event;

class LocalDataPreEvent extends Event
{
    public const NAME = 'dbp.relay.relay_core.local_data_aware_event.pre';

    /** @var array */
    private $queryParameters;

    public function __construct()
    {
        $this->queryParameters = [];
    }

    public function setQueryParameters(array $queryParameters): void
    {
        $this->queryParameters = $queryParameters;
    }

    public function getQueryParameters(): array
    {
        return $this->queryParameters;
    }
}
