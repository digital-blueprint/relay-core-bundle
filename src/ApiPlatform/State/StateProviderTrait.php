<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\ApiPlatform\State;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use Dbp\Relay\CoreBundle\Exception\ApiError;
use Dbp\Relay\CoreBundle\Rest\Query\Pagination\PartialPaginator;
use Symfony\Component\HttpFoundation\Response;

trait StateProviderTrait
{
    /**
     * @return PartialPaginator|object|null
     *
     * @throws \Exception
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = [])
    {
        if ($operation instanceof GetCollection) {
            return $this->getCollectionInternal($context);
        } elseif ($operation instanceof Get) {
            return $this->getItemInternal($uriVariables['identifier'], $context);
        }

        throw ApiError::withDetails(Response::HTTP_INTERNAL_SERVER_ERROR, 'unknown provider operation: '.$operation->getShortName());
    }
}
