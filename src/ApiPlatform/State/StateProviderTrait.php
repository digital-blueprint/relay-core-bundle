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

        /**
         * Resource class and normalization groups are not set in the context when the provider is called by
         * IriConverterInterface.getResourceByIri(...).
         * WORKAROUND: copy the respective values from the operation.
         * Maybe this is generally the better source for getting those values?
         */
        if (false === isset($context[self::RESOURCE_CLASS_CONTEXT_KEY])) {
            $context[self::RESOURCE_CLASS_CONTEXT_KEY] = $operation->getClass();
        }
        if (false === isset($context[self::GROUPS_CONTEXT_KEY])) {
            $context[self::GROUPS_CONTEXT_KEY] = $operation->getNormalizationContext()['groups'] ?? [];
        }

        if ($operation instanceof CollectionOperationInterface) {
            return $this->getCollectionInternal($context);
        }

        return $this->getItemInternal($uriVariables[static::$identifierName] ?? self::NO_ID, $context);
    }
}
