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
     * @throws \Exception
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = [])
    {
        $this->currentOperation = $operation;
        $this->currentUriVariables = $uriVariables;

        if ($operation instanceof CollectionOperationInterface) {
            return $this->getCollectionInternal($context);
        } else {
            return $this->getItemInternal($uriVariables[static::$identifierName], $context);
        }
    }
}
