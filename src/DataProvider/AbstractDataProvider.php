<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\DataProvider;

use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use Dbp\Relay\CoreBundle\Rest\Query\Pagination\PartialPaginator;

/**
 * @deprecated Implemented ApiPlatform interfaces are deprecated. Use AbstractStateProvider instead.
 */
abstract class AbstractDataProvider extends \Dbp\Relay\CoreBundle\Rest\AbstractDataProvider implements RestrictedDataProviderInterface, ItemDataProviderInterface, CollectionDataProviderInterface
{
    public function supports(string $resourceClass, ?string $operationName = null, array $context = []): bool
    {
        return $resourceClass === $this->getResourceClass();
    }

    public function getCollection(string $resourceClass, ?string $operationName = null, array $context = []): PartialPaginator
    {
        return $this->getCollectionInternal($context);
    }

    public function getItem(string $resourceClass, $id, ?string $operationName = null, array $context = []): ?object
    {
        return $this->getItemInternal($id, $context);
    }

    abstract protected function getResourceClass(): string;
}
