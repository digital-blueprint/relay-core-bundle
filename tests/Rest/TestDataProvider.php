<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\Rest;

use Dbp\Relay\CoreBundle\LocalData\LocalDataEventDispatcher;
use Dbp\Relay\CoreBundle\Rest\AbstractDataProvider;
use Dbp\Relay\CoreBundle\Rest\Query\Pagination\Pagination;
use Dbp\Relay\CoreBundle\Tests\Locale\TestLocale;
use Symfony\Component\EventDispatcher\EventDispatcher;

class TestDataProvider extends AbstractDataProvider
{
    /** @var LocalDataEventDispatcher */
    private $localDataEventDispatcher;

    /** @var array[] */
    private $sourceData = [];

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

    protected function getItemById($id, array $filters = [], array $options = []): object
    {
        $this->localDataEventDispatcher->onNewOperation($options);

        $testEntity = new TestEntity($id);

        $this->localDataEventDispatcher->dispatch(new TestEntityPostEvent($testEntity, $this->sourceData[$id] ?? []));

        return $testEntity;
    }

    protected function getPage(int $currentPageNumber, int $maxNumItemsPerPage, array $filters = [], array $options = []): array
    {
        $pageEntities = [];

        $this->localDataEventDispatcher->onNewOperation($options);

        $firstIndex = Pagination::getFirstItemIndex($currentPageNumber, $maxNumItemsPerPage);
        $breakIndex = min($firstIndex + $maxNumItemsPerPage, count($this->sourceData));

        for ($index = $firstIndex; $index < $breakIndex; ++$index) {
            $testEntity = new TestEntity(strval($index));

            $this->localDataEventDispatcher->dispatch(new TestEntityPostEvent($testEntity, $this->sourceData[$index] ?? []));

            $pageEntities[] = $testEntity;
        }

        return $pageEntities;
    }

    protected function isUserGrantedOperationAccess(int $operation): bool
    {
        return true;
    }
}
