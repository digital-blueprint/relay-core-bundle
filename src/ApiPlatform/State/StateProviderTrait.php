<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\ApiPlatform\State;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use Dbp\Relay\CoreBundle\Exception\ApiError;
use Dbp\Relay\CoreBundle\Rest\Query\Pagination\PartialPaginator;

trait StateProviderTrait
{
    use StateTrait;

    /**
     * @return PartialPaginator|object|null
     *
     * @throws ApiError
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $this->currentOperation = $operation;
        $this->currentUriVariables = $uriVariables;
        $this->currentRequestMethod = $context['request']?->getMethod();

        if ($operation instanceof CollectionOperationInterface) {
            return $this->getCollectionInternal($context);
        } else {
            return $this->getItemInternal($uriVariables[static::$identifierName] ?? self::NO_ID, $context);
        }
    }
}
