<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\LocalData;

use Symfony\Contracts\EventDispatcher\Event;

class LocalDataPreEvent extends Event
{
    /** @var string[] */
    private $queryParametersIn;

    /** @var string[] */
    private $queryParametersOut;

    public function __construct()
    {
        $this->queryParametersIn = [];
        $this->queryParametersOut = [];
    }

    /**
     * @deprecated Use getQueryParametersOut
     */
    public function getQueryParameters(): array
    {
        return $this->queryParametersOut;
    }

    public function initQueryParametersIn(array $queryParametersIn): void
    {
        $this->queryParametersIn = $queryParametersIn;
    }

    public function getPendingQueryParametersIn(): array
    {
        return $this->queryParametersIn;
    }

    public function acknowledgeQueryParameterIn(string $queryParameterName): void
    {
        unset($this->queryParametersIn[$queryParameterName]);
    }

    public function addQueryParameterOut(string $queryParameterName, string $queryParameterValue): void
    {
        $this->queryParametersOut[$queryParameterName] = $queryParameterValue;
    }

    public function getQueryParametersOut(): array
    {
        return $this->queryParametersOut;
    }
}
