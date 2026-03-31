<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\ApiPlatform\State;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;

trait StateTrait
{
    private const FILTERS_CONTEXT_KEY = 'filters';
    private const GROUPS_CONTEXT_KEY = 'groups';
    private const RESOURCE_CLASS_CONTEXT_KEY = 'resource_class';

    protected static string $identifierName = 'identifier';
    private ?Operation $currentOperation = null;
    private array $currentUriVariables = [];

    protected function getCurrentOperationName(): ?string
    {
        return $this->currentOperation?->getName();
    }

    protected function getCurrentUriVariables(): array
    {
        return $this->currentUriVariables;
    }

    protected function isCurrentOperationACollectionOperation(): bool
    {
        return $this->currentOperation instanceof CollectionOperationInterface;
    }
}
