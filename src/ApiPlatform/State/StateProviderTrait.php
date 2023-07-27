<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\ApiPlatform\State;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use Dbp\Relay\CoreBundle\Rest\Query\Pagination\PartialPaginator;

trait StateProviderTrait
{
    /**
     * @return PartialPaginator|object|null
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = [])
    {
        if ($operation instanceof CollectionOperationInterface) {
            return $this->getCollectionInternal($context);
        } else {
            return $this->getItemInternal($uriVariables['identifier'], $context);
        }
    }
}
