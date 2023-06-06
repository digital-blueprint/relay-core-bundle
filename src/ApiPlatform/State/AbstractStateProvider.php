<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\ApiPlatform\State;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Dbp\Relay\CoreBundle\Exception\ApiError;
use Dbp\Relay\CoreBundle\HttpOperations\AbstractDataProvider;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractStateProvider extends AbstractDataProvider implements ProviderInterface
{
    private const IDENTIFIER_URI_VARIABLES_KEY = 'identifier';
    private const FILTERS_CONTEXT_KEY = 'filters';

    public function provide(Operation $operation, array $uriVariables = [], array $context = [])
    {
        if ($operation instanceof GetCollection) {
            return $this->getCollectionInternal($context[self::FILTERS_CONTEXT_KEY]);
        } elseif ($operation instanceof Get) {
            return $this->getItemInternal($uriVariables[self::IDENTIFIER_URI_VARIABLES_KEY], $context[self::FILTERS_CONTEXT_KEY]);
        }

        throw ApiError::withDetails(Response::HTTP_INTERNAL_SERVER_ERROR, 'unknown provider operation: '.$operation->getShortName());
    }
}
