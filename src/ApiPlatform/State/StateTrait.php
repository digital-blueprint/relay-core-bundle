<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\ApiPlatform\State;

use ApiPlatform\Metadata\Operation;

trait StateTrait
{
    protected static $identifierName = 'identifier';

    /* @var Operation */
    private $currentOperation;

    /* @var array */
    private $currentUriVariables;

    protected function getCurrentOperationName(): string
    {
        return $this->currentOperation->getName();
    }

    protected function getCurrentUriVariables(): array
    {
        return $this->currentUriVariables;
    }
}
