<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\TestUtils;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Dbp\Relay\CoreBundle\ApiPlatform\State\StateProviderInterface;
use Dbp\Relay\CoreBundle\Rest\Query\Pagination\PartialPaginator;

class DataProviderTester
{
    /** @var StateProviderInterface */
    private $stateProvider;

    /** @var string */
    private $resourceClass;

    /** @var array */
    private $normalizationGroups;

    public function __construct(StateProviderInterface $dataProvider, string $resourceClass, array $normalizationGroups = [])
    {
        $this->stateProvider = $dataProvider;
        $this->resourceClass = $resourceClass;
        $this->normalizationGroups = $normalizationGroups;
    }

    public function getItem(string $identifier, array $filters = []): ?object
    {
        /** @var object|null */
        return $this->stateProvider->provide(new Get(), ['identifier' => $identifier], $this->createContext($filters));
    }

    public function getCollection(string $identifier, array $filters = []): array
    {
        /** @var PartialPaginator */
        $partialPaginator = $this->stateProvider->provide(new GetCollection(), [], $this->createContext($filters));

        return $partialPaginator->getItems();
    }

    private function createContext(array $filters): array
    {
        return [
            'filters' => $filters,
            'resource_class' => $this->resourceClass,
            'groups' => $this->normalizationGroups,
        ];
    }
}
