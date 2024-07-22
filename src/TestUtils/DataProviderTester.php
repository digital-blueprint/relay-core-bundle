<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\TestUtils;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Dbp\Relay\CoreBundle\Rest\AbstractDataProvider;
use Dbp\Relay\CoreBundle\Rest\Query\Pagination\PartialPaginator;

class DataProviderTester
{
    private AbstractDataProvider $dataProvider;
    private string $resourceClass;
    private array $normalizationGroups;

    /**
     * Use this to set up the given data provider (i.e. inject all required services and set up a test user)
     * and create a new data provider tester instance for it.
     *
     * @param string   $resourceClass       the fully qualified class name of the entity that this data provider provides
     * @param string[] $normalizationGroups the normalization groups of the entity that this data provider provides
     */
    public static function create(AbstractDataProvider $dataProvider, string $resourceClass, array $normalizationGroups = []): DataProviderTester
    {
        self::setUp($dataProvider);

        return new DataProviderTester($dataProvider, $resourceClass, $normalizationGroups);
    }

    /**
     * Use this to set up the given data provider (i.e. inject all required services and set up a test user).
     */
    public static function setUp(AbstractDataProvider $dataProvider,
        string $currentUserIdentifier = TestAuthorizationService::TEST_USER_IDENTIFIER, array $currentUserAttributes = []): void
    {
        $dataProvider->__injectLocale(new TestLocale());
        $dataProvider->__injectPropertyNameCollectionFactory(new TestPropertyNameCollectionFactory());

        TestAuthorizationService::setUp($dataProvider, $currentUserIdentifier, $currentUserAttributes);
    }

    /**
     * @param string[] $normalizationGroups
     */
    private function __construct(AbstractDataProvider $dataProvider, string $resourceClass, array $normalizationGroups = [])
    {
        $this->dataProvider = $dataProvider;
        $this->resourceClass = $resourceClass;
        $this->normalizationGroups = $normalizationGroups;
    }

    public function getItem(?string $identifier, array $filters = []): ?object
    {
        $uriVariables = $identifier !== null ? ['identifier' => $identifier] : [];

        /** @var object|null */
        return $this->dataProvider->provide(new Get(), $uriVariables, $this->createContext($filters));
    }

    public function getCollection(array $filters = []): array
    {
        /** @var PartialPaginator */
        $partialPaginator = $this->dataProvider->provide(new GetCollection(), [], $this->createContext($filters));

        return $partialPaginator->getItems();
    }

    /**
     * @param int $pageNumber One-based page number
     */
    public function getPage(int $pageNumber, int $maxNumItemsPerPage, array $filters = []): array
    {
        $filters = array_merge($filters, [
            'page' => $pageNumber,
            'perPage' => $maxNumItemsPerPage,
        ]);

        return $this->getCollection($filters);
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
