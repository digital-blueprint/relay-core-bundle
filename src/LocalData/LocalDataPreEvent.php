<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\LocalData;

use Symfony\Contracts\EventDispatcher\Event;

class LocalDataPreEvent extends Event
{
    /** @var string[] */
    private $queryParametersIn;

    /** @var array */
    private $options;

    public function __construct(array $options)
    {
        $this->queryParametersIn = [];
        $this->options = $options;
    }

    public function initQueryParameters(array $queryParametersIn): void
    {
        $this->queryParametersIn = $queryParametersIn;
    }

    public function getPendingQueryParameters(): array
    {
        return $this->queryParametersIn;
    }

    public function acknowledgeQueryParameter(string $queryParameterName): void
    {
        unset($this->queryParametersIn[$queryParameterName]);
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function setOptions(array $options): void
    {
        $this->options = $options;
    }
}
