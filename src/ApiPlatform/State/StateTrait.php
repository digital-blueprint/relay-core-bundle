<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\ApiPlatform\State;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;

trait StateTrait
{
    protected static string $identifierName = 'identifier';
    private ?Operation $currentOperation = null;
    private array $currentUriVariables = [];
    private ?string $currentRootRequestMethod = null;

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

    /**
     * The original (root) HTTP request method.
     */
    protected function getCurrentRootRequestMethod(): ?string
    {
        return $this->currentRootRequestMethod;
    }
}
