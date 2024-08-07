<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\Rest;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Dbp\Relay\CoreBundle\Exception\ApiError;
use Dbp\Relay\CoreBundle\LocalData\LocalDataEventDispatcher;
use Dbp\Relay\CoreBundle\Rest\AbstractDataProvider;
use Dbp\Relay\CoreBundle\Rest\Query\Pagination\Pagination;
use Dbp\Relay\CoreBundle\Rest\Query\Pagination\PartialPaginator;
use Dbp\Relay\CoreBundle\TestUtils\DataProviderTester;
use Dbp\Relay\CoreBundle\TestUtils\TestAuthorizationService;
use Dbp\Relay\CoreBundle\User\UserAttributeException;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @extends AbstractDataProvider<TestEntity>
 */
class TestDataProvider extends AbstractDataProvider
{
    public const ROLE_USER = 'ROLE_USER';
    public const ROLE_ADMIN = 'ROLE_ADMIN';
    public const TEST_USER_IDENTIFIER = TestAuthorizationService::TEST_USER_IDENTIFIER;
    public const ADMIN_USER_IDENTIFIER = TestAuthorizationService::ADMIN_USER_IDENTIFIER;
    public const INCLUDE_ADMIN_ONLY_ENTITIES_FILTER = 'includeAdminOnlyEntities';

    private LocalDataEventDispatcher $localDataEventDispatcher;

    private array $sourceData = [];

    /** The options to test */
    private array $options = [];

    private bool $allowUnauthenticatedAccess = false;
    private bool $isGetItemOperationAllowed = true;
    private bool $isGetCollectionOperationAllowed = true;

    public static function create(?EventDispatcher $eventDispatcher = null,
        string $currentUserIdentifier = self::TEST_USER_IDENTIFIER, ?array $currentUserAttributes = null): TestDataProvider
    {
        $testDataProvider = new TestDataProvider($eventDispatcher ?? new EventDispatcher());
        DataProviderTester::setUp($testDataProvider, $currentUserIdentifier,
            $currentUserAttributes ?? [
                self::ROLE_USER => $currentUserIdentifier === self::TEST_USER_IDENTIFIER,
                self::ROLE_ADMIN => $currentUserIdentifier === self::ADMIN_USER_IDENTIFIER,
            ]);
        $testDataProvider->__injectPropertyNameCollectionFactory(new TestEntityPropertyNameCollectionFactory());

        return $testDataProvider;
    }

    public static function login(TestDataProvider $testDataProvider,
        string $currentUserIdentifier = self::TEST_USER_IDENTIFIER, ?array $currentUserAttributes = null): void
    {
        DataProviderTester::login($testDataProvider, $currentUserIdentifier, $currentUserAttributes ?? [
            self::ROLE_USER => $currentUserIdentifier === self::TEST_USER_IDENTIFIER,
            self::ROLE_ADMIN => $currentUserIdentifier === self::ADMIN_USER_IDENTIFIER,
        ]);
    }

    public static function logout(TestDataProvider $testDataProvider): void
    {
        DataProviderTester::logout($testDataProvider);
    }

    protected function __construct(EventDispatcher $eventDispatcher)
    {
        parent::__construct();

        $this->localDataEventDispatcher = new LocalDataEventDispatcher(TestEntity::class, $eventDispatcher);
    }

    private static function createContext(array $filters): array
    {
        return [
            'filters' => $filters,
            'resource_class' => TestEntity::class,
            'groups' => ['TestEntity:output', 'LocalData:output'],
        ];
    }

    public function setAllowUnauthenticatedAccess(bool $allowUnauthenticatedAccess): void
    {
        $this->allowUnauthenticatedAccess = $allowUnauthenticatedAccess;
    }

    /**
     * @param array[] $sourceData
     */
    public function setSourceData(array $sourceData): void
    {
        $this->sourceData = $sourceData;
    }

    /**
     * @throws ApiError
     */
    public function getTestEntity(string $id, array $filters = [], array $sourceData = []): ?TestEntity
    {
        $this->setSourceData($sourceData);

        /** @var TestEntity|null */
        return $this->provide(new Get(), ['identifier' => $id], self::createContext($filters));
    }

    /**
     * @throws ApiError
     */
    public function getTestEntities(array $filters = [], array $sourceData = []): array
    {
        return $this->getTestEntityPaginator($filters, $sourceData)->getItems();
    }

    /**
     * @throws ApiError
     */
    public function getTestEntityPaginator(array $filters = [], array $sourceData = []): PartialPaginator
    {
        $this->setSourceData($sourceData);

        /** @var PartialPaginator */
        return $this->provide(new GetCollection(), [], self::createContext($filters));
    }

    /**
     * The $options parameter from the last getItemById or getPage call. Use to test for expected options.
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    public function setIsGetItemOperationAllowed(bool $isGetItemOperationAllowed): void
    {
        $this->isGetItemOperationAllowed = $isGetItemOperationAllowed;
    }

    public function setIsGetCollectionOperationAllowed(bool $isGetCollectionOperationAllowed): void
    {
        $this->isGetCollectionOperationAllowed = $isGetCollectionOperationAllowed;
    }

    protected function getItemById(string $id, array $filters = [], array $options = []): ?object
    {
        $this->localDataEventDispatcher->onNewOperation($options);

        $testEntity = null;
        $entitySourceData = $this->sourceData[$id] ?? null;
        if ($entitySourceData !== null) {
            $testEntity = new TestEntity($id);

            $this->localDataEventDispatcher->dispatch(new TestEntityPostEvent($testEntity, $entitySourceData));
        }

        $this->options = $options;

        return $testEntity;
    }

    protected function getPage(int $currentPageNumber, int $maxNumItemsPerPage, array $filters = [], array $options = []): array
    {
        $pageEntities = [];

        $this->localDataEventDispatcher->onNewOperation($options);

        $pageStartIndex = Pagination::getFirstItemIndex($currentPageNumber, $maxNumItemsPerPage);
        $currentIndex = 0;
        foreach ($this->sourceData as $entityId => $entitySourceData) {
            if ($currentIndex++ >= $pageStartIndex) {
                $testEntity = new TestEntity(strval($entityId));

                $this->localDataEventDispatcher->dispatch(new TestEntityPostEvent($testEntity, $entitySourceData));

                $pageEntities[] = $testEntity;
                if (count($pageEntities) === $maxNumItemsPerPage) {
                    break;
                }
            }
        }

        $this->options = $options;

        return $pageEntities;
    }

    protected function requiresAuthentication(int $operation): bool
    {
        return $this->allowUnauthenticatedAccess === false;
    }

    protected function isCurrentUserGrantedOperationAccess(int $operation): bool
    {
        return match ($operation) {
            self::GET_ITEM_OPERATION => $this->isGetItemOperationAllowed,
            self::GET_COLLECTION_OPERATION => $this->isGetCollectionOperationAllowed,
            default => false,
        };
    }

    /**
     * @throws UserAttributeException
     */
    protected function isCurrentUserAuthorizedToAccessItem(int $operation, $item, array $filters): bool
    {
        $testEntity = $item;
        assert($testEntity instanceof TestEntity);

        return ($this->isAuthenticated() && $this->getUserAttribute(self::ROLE_ADMIN))
            || !str_starts_with($testEntity->getIdentifier(), '_');
    }

    /**
     * @throws UserAttributeException
     */
    protected function isCurrentUserAuthorizedToGetCollection(array $filters): bool
    {
        return ($this->isAuthenticated() && $this->getUserAttribute(self::ROLE_ADMIN))
            || ($filters[self::INCLUDE_ADMIN_ONLY_ENTITIES_FILTER] ?? null) === null;
    }
}
