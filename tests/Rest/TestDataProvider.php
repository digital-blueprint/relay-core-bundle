<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\Rest;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Dbp\Relay\CoreBundle\Authorization\AuthorizationDataMuxer;
use Dbp\Relay\CoreBundle\Authorization\AuthorizationDataProviderProvider;
use Dbp\Relay\CoreBundle\LocalData\LocalDataEventDispatcher;
use Dbp\Relay\CoreBundle\Rest\AbstractDataProvider;
use Dbp\Relay\CoreBundle\Rest\Query\Pagination\Pagination;
use Dbp\Relay\CoreBundle\Rest\Query\Pagination\PartialPaginator;
use Dbp\Relay\CoreBundle\Tests\Locale\TestLocale;
use Dbp\Relay\CoreBundle\TestUtils\TestUserSession;
use Symfony\Component\EventDispatcher\EventDispatcher;

class TestDataProvider extends AbstractDataProvider
{
    /** @var LocalDataEventDispatcher */
    private $localDataEventDispatcher;

    /** @var array[] */
    private $sourceData = [];

    /** @var array The options to test */
    private $options = [];

    public static function create(EventDispatcher $eventDispatcher = null): TestDataProvider
    {
        $testDataProvider = new TestDataProvider($eventDispatcher ?? new EventDispatcher());
        $testDataProvider->__injectServices(
            new TestUserSession('testuser'),
            new AuthorizationDataMuxer(new AuthorizationDataProviderProvider([]), new EventDispatcher()));

        return $testDataProvider;
    }

    public function __construct(EventDispatcher $eventDispatcher)
    {
        parent::__construct();

        $this->localDataEventDispatcher = new LocalDataEventDispatcher(TestEntity::class, $eventDispatcher);
        $this->__injectLocale(new TestLocale('en'));
    }

    /**
     * @param array[] $sourceData
     */
    public function setSourceData(array $sourceData)
    {
        $this->sourceData = $sourceData;
    }

    public function getTestEntity(string $id, array $filters = [], array $sourceData = []): ?TestEntity
    {
        $this->setSourceData($sourceData);

        /** @var TestEntity */
        return $this->provide(new Get(), ['identifier' => $id], ['filters' => $filters]);
    }

    public function getTestEntities(array $filters = [], array $sourceData = []): array
    {
        return $this->getTestEntityPaginator($filters, $sourceData)->getItems();
    }

    public function getTestEntityPaginator(array $filters = [], array $sourceData = []): PartialPaginator
    {
        $this->setSourceData($sourceData);

        /** @var PartialPaginator */
        return $this->provide(new GetCollection(), [], ['filters' => $filters]);
    }

    /**
     * The $options parameter from the last getItemById or getPage call. Use to test for expected options.
     */
    public function getOptions(): array
    {
        return $this->options;
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

    protected function isUserGrantedOperationAccess(int $operation): bool
    {
        return true;
    }
}
