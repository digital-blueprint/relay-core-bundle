<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\Rest;

use Dbp\Relay\CoreBundle\Exception\ApiError;
use Dbp\Relay\CoreBundle\Rest\ErrorIds;
use Dbp\Relay\CoreBundle\Rest\Options;
use Dbp\Relay\CoreBundle\Rest\Query\Filter\Filter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class AbstractDataProviderTest extends TestCase
{
    /** @var TestDataProvider */
    private $testDataProvider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testDataProvider = TestDataProvider::create();

        $config = [
            'prepared_filters' => [
                [
                    'id' => 'filter0',
                    'filter' => 'filter[foo][condition][path]=field0&filter[foo][condition][operator]=I_CONTAINS&filter[foo][condition][value]=value0',
                    'apply_policy' => 'user.get("ROLE_TEST_USER")',
                ],
                [
                    'id' => 'filterForbidden',
                    'filter' => 'filter[foo][condition][path]=field0&filter[foo][condition][operator]=I_CONTAINS&filter[foo][condition][value]=value0',
                    'apply_policy' => 'user.get("ROLE_ADMIN")',
                ],
            ],
            'local_data' => [
                [
                    'local_data_attribute' => 'attribute0',
                    'read_policy' => 'true',
                ],
            ],
        ];

        $this->testDataProvider->setConfig($config);
    }

    public function testGetEntity()
    {
        // provide source data for 'id'
        $testEntity = $this->testDataProvider->getTestEntity('id', [], ['id' => []]);

        $this->assertEquals('id', $testEntity->getIdentifier());
    }

    public function testGetEntityNotFound()
    {
        // don't provide source data for 'id'
        $testEntity = $this->testDataProvider->getTestEntity('id');

        $this->assertNull($testEntity);
    }

    public function testGetEmptyPage()
    {
        $testEntities = $this->testDataProvider->getTestEntities();

        $this->assertEmpty($testEntities);
    }

    public function testGetCollection()
    {
        $testEntities = $this->testDataProvider->getTestEntities([], ['1' => [], '2' => []]);

        $this->assertCount(2, $testEntities);
    }

    public function testPaginationParameters()
    {
        $currentPage = 2;
        $itemsPerPage = 3;

        $filters = [
            'page' => strval($currentPage),
            'perPage' => strval($itemsPerPage),
        ];

        // must contain the page range for this test to work
        $sourceData = [
            [], [], [], [], [], [], [],
        ];

        $paginator = $this->testDataProvider->getTestEntityPaginator($filters, $sourceData);

        $this->assertEquals($itemsPerPage, $paginator->getItemsPerPage());
        $this->assertEquals($currentPage, $paginator->getCurrentPage());
        $this->assertCount($itemsPerPage, $paginator->getItems());
    }

    public function testPaginationParametersPageNotFull()
    {
        $currentPage = 3;
        $itemsPerPage = 3;

        $filters = [
            'page' => strval($currentPage),
            'perPage' => strval($itemsPerPage),
        ];

        $sourceData = [
            [], [], [], [], [], [], [],
        ];

        $paginator = $this->testDataProvider->getTestEntityPaginator($filters, $sourceData);

        $this->assertEquals($itemsPerPage, $paginator->getItemsPerPage());
        $this->assertEquals($currentPage, $paginator->getCurrentPage());
        $this->assertCount(1, $paginator->getItems());
    }

    /**
     * @throws \Exception
     */
    public function testFilterQueryParameter()
    {
        $filterParameters = [];
        parse_str('filter[field0]=value0', $filterParameters);

        $this->testDataProvider->getTestEntities($filterParameters, [[]]);
        $filter = $this->testDataProvider->getOptions()[Options::FILTER];

        $expectedFilter = Filter::create();
        $expectedFilter->getRootNode()->equals('field0', 'value0');

        $this->assertEquals($expectedFilter->toArray(), $filter->toArray());
    }

    /**
     * @throws \Exception
     */
    public function testFilterKeySquareBracketsMissing()
    {
        try {
            $this->testDataProvider->getTestEntities(['filter' => 'value'], [[]]);
        } catch (ApiError $exception) {
            $this->assertEquals(Response::HTTP_BAD_REQUEST, $exception->getStatusCode());
            $this->assertEquals(ErrorIds::FILTER_INVALID_FILTER_KEY_SQUARE_BRACKETS_MISSING, $exception->getErrorId());
        }
    }

    /**
     * @throws \Exception
     */
    public function testFilterInvalid()
    {
        try {
            $filterParameters = [];
            parse_str('filter[field0][foo][bar]=value', $filterParameters);

            $this->testDataProvider->getTestEntities($filterParameters, [[]]);
        } catch (ApiError $exception) {
            $this->assertEquals(Response::HTTP_BAD_REQUEST, $exception->getStatusCode());
            $this->assertEquals(ErrorIds::FILTER_INVALID, $exception->getErrorId());
        }
    }

    /**
     * @throws \Exception
     */
    public function testPreparedFilter()
    {
        $this->testDataProvider->getTestEntities(['preparedFilter' => 'filter0'], [[]]);
        $preparedFilter = $this->testDataProvider->getOptions()[Options::FILTER];

        $expectedFilter = Filter::create();
        $expectedFilter->getRootNode()->icontains('field0', 'value0');

        $this->assertEquals($expectedFilter->toArray(), $preparedFilter->toArray());
    }

    /**
     * @throws \Exception
     */
    public function testPreparedFilterUndefined()
    {
        try {
            $this->testDataProvider->getTestEntities(['preparedFilter' => '___'], [[]]);
        } catch (ApiError $exception) {
            $this->assertEquals(Response::HTTP_BAD_REQUEST, $exception->getStatusCode());
            $this->assertEquals(ErrorIds::PREPARED_FILTER_UNDEFINED, $exception->getErrorId());
        }
    }

    /**
     * @throws \Exception
     */
    public function testPreparedFilterForbidden()
    {
        try {
            $this->testDataProvider->getTestEntities(['preparedFilter' => 'filterForbidden'], [[]]);
        } catch (ApiError $exception) {
            $this->assertEquals(Response::HTTP_FORBIDDEN, $exception->getStatusCode());
            $this->assertEquals(ErrorIds::PREPARED_FILTER_ACCESS_DENIED, $exception->getErrorId());
        }
    }
}
