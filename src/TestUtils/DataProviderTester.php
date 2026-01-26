<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\TestUtils;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Property\Factory\PropertyInfoPropertyNameCollectionFactory;
use Dbp\Relay\CoreBundle\Rest\AbstractDataProvider;
use Dbp\Relay\CoreBundle\Rest\Query\Pagination\PartialPaginator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;

class DataProviderTester extends AbstractRestTester
{
    /**
     * Use this to set up the given data provider (i.e. inject all required services and set up a test user)
     * and create a new data provider tester instance for it.
     *
     * @param string   $resourceClass       the fully qualified class name of the entity that this data provider provides
     * @param string[] $normalizationGroups the normalization groups of the entity that this data provider provides
     */
    public static function create(AbstractDataProvider $dataProvider, string $resourceClass, array $normalizationGroups = [],
        string $identifierName = 'identifier', ?string $entityShortName = null): DataProviderTester
    {
        self::setUp($dataProvider);

        return new DataProviderTester(
            $dataProvider, $resourceClass, $normalizationGroups, $identifierName, $entityShortName);
    }

    /**
     * Use this to set up the given data provider (i.e. inject all required services and set up a test user).
     */
    public static function setUp(AbstractDataProvider $dataProvider,
        string $currentUserIdentifier = TestAuthorizationService::TEST_USER_IDENTIFIER, array $currentUserAttributes = []): void
    {
        $dataProvider->__injectLocale(new TestLocale());
        $dataProvider->__injectPropertyNameCollectionFactory(
            new PropertyInfoPropertyNameCollectionFactory(new PropertyInfoExtractor(
                [new ReflectionExtractor()], [new ReflectionExtractor()])));

        self::login($dataProvider, $currentUserIdentifier, $currentUserAttributes);
    }

    /**
     * @param string[] $normalizationGroups
     */
    private function __construct(
        private readonly AbstractDataProvider $dataProvider,
        string $resourceClass,
        array $normalizationGroups,
        string $identifierName = 'identifier',
        ?string $entityShortName = null)
    {
        parent::__construct(
            $resourceClass,
            normalizationGroups: $normalizationGroups,
            identifierName: $identifierName,
            entityShortName: $entityShortName);
    }

    public function getItem(?string $identifier = null, array $filters = [], array $uriVariables = []): ?object
    {
        if ($identifier !== null) {
            $uriVariables[$this->identifierName] = $identifier;
        }

        /** @var object|null */
        return $this->dataProvider->provide(
            new Get(shortName: $this->entityShortName),
            $uriVariables,
            $this->createContext(Request::METHOD_GET, $identifier, $filters)
        );
    }

    public function getCollection(array $filters = [], array $uriVariables = []): array
    {
        /** @var PartialPaginator $partialPaginator */
        $partialPaginator = $this->dataProvider->provide(
            new GetCollection(shortName: $this->entityShortName),
            $uriVariables,
            $this->createContext(Request::METHOD_GET, filters: $filters)
        );

        return $partialPaginator->getItems();
    }

    /**
     * @param int $pageNumber One-based page number
     */
    public function getPage(int $pageNumber = 1, int $maxNumItemsPerPage = 30,
        array $filters = [], array $uriVariables = []): array
    {
        $filters = array_merge($filters, [
            'page' => $pageNumber,
            'perPage' => $maxNumItemsPerPage,
        ]);

        return $this->getCollection($filters, $uriVariables);
    }
}
