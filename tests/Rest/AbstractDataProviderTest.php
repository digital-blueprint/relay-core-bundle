<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\Rest;

use Dbp\Relay\CoreBundle\Exception\ApiError;
use Dbp\Relay\CoreBundle\Rest\ErrorIds;
use Dbp\Relay\CoreBundle\Rest\Options;
use Dbp\Relay\CoreBundle\Rest\Query\Filter\FilterTreeBuilder;
use Dbp\Relay\CoreBundle\Rest\Query\Sort\Sort;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class AbstractDataProviderTest extends TestCase
{
    private TestDataProvider $testDataProvider;

    protected function setUp(): void
    {
        $this->setUpAndConfigure(self::getTestConfig());
    }

    private function setUpAndConfigure(array $testConfig): void
    {
        $this->testDataProvider = TestDataProvider::create(null, TestDataProvider::TEST_USER_IDENTIFIER);
        $this->testDataProvider->setConfig($testConfig);
    }

    private function loginAdmin(): void
    {
        TestDataProvider::login($this->testDataProvider, TestDataProvider::ADMIN_USER_IDENTIFIER);
    }

    private function logout(): void
    {
        TestDataProvider::logout($this->testDataProvider);
    }

    private static function getTestConfig(): array
    {
        $config['rest']['query']['filter']['enable_query_filters'] = true;
        $config['rest']['query']['filter']['enable_prepared_filters'] = true;
        $config['rest']['query']['filter']['prepared_filters'] = [
            [
                'id' => 'filter0',
                'filter' => 'filter[foo][condition][path]=field0&filter[foo][condition][operator]=I_CONTAINS&filter[foo][condition][value]=value0',
                'apply_policy' => 'user.get("ROLE_USER")',
            ],
            [
                'id' => 'filterForbidden',
                'filter' => 'filter[foo][condition][path]=field0&filter[foo][condition][operator]=I_CONTAINS&filter[foo][condition][value]=value0',
                'apply_policy' => 'user.get("ROLE_ADMIN")',
            ],
        ];
        $config['rest']['query']['sort']['enable_sort'] = true;

        $config['local_data'] = [
            [
                'local_data_attribute' => 'attribute0',
                'read_policy' => 'true',
            ],
            [
                'local_data_attribute' => 'forbiddenAttribute',
                'read_policy' => 'false',
            ],
        ];

        return $config;
    }

    public function testGetEntity()
    {
        // provide source data for 'id'
        $testEntity = $this->testDataProvider->getTestEntity('id', [], ['id' => []]);

        $this->assertEquals('id', $testEntity->getIdentifier());
    }

    public function testGetEntityUnauthenticated()
    {
        $this->logout();
        try {
            $this->testDataProvider->getTestEntity('id', [], ['id' => []]);
            $this->fail('401 unauthorized exception not thrown as expected');
        } catch (ApiError $exception) {
            $this->assertEquals(401, $exception->getStatusCode());
        }
    }

    public function testGetEntityUnauthenticatedAllowed()
    {
        $this->logout();
        $this->testDataProvider->setAllowUnauthenticatedAccess(true);
        $testEntity = $this->testDataProvider->getTestEntity('id', [], ['id' => []]);

        $this->assertEquals('id', $testEntity->getIdentifier());
    }

    public function testGetEntityOperationAccessForbidden()
    {
        try {
            $this->testDataProvider->setIsGetItemOperationAllowed(false);
            $this->testDataProvider->getTestEntity('_forbidden', [], ['_forbidden' => []]);
            $this->fail('403 forbidden exception not thrown as expected');
        } catch (ApiError $exception) {
            $this->assertEquals(403, $exception->getStatusCode());
        }
    }

    public function testGetEntityAdminOnly()
    {
        try {
            $this->testDataProvider->getTestEntity('_forbidden', [], ['_forbidden' => []]);
            $this->fail('403 forbidden exception not thrown as expected');
        } catch (ApiError $exception) {
            $this->assertEquals(403, $exception->getStatusCode());
        }

        $this->loginAdmin();

        $testEntity = $this->testDataProvider->getTestEntity('_forbidden', [], ['_forbidden' => []]);
        $this->assertEquals('_forbidden', $testEntity->getIdentifier());
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
        $this->assertEquals('1', $testEntities[0]->getIdentifier());
        $this->assertEquals('2', $testEntities[1]->getIdentifier());
    }

    public function testGetCollectionUnauthenticated()
    {
        $this->logout();
        try {
            $this->testDataProvider->getTestEntities([], ['1' => [], '2' => []]);
            $this->fail('401 unauthorized exception not thrown as expected');
        } catch (ApiError $exception) {
            $this->assertEquals(401, $exception->getStatusCode());
        }
    }

    public function testGetCollectionUnauthenticatedAllowed()
    {
        $this->logout();
        $this->testDataProvider->setAllowUnauthenticatedAccess(true);
        $testEntities = $this->testDataProvider->getTestEntities([], ['1' => [], '2' => []]);
        $this->assertCount(2, $testEntities);
        $this->assertEquals('1', $testEntities[0]->getIdentifier());
        $this->assertEquals('2', $testEntities[1]->getIdentifier());
    }

    public function testGetCollectionAdminOnly()
    {
        try {
            $this->testDataProvider->getTestEntities([TestDataProvider::INCLUDE_ADMIN_ONLY_ENTITIES_FILTER => true],
                ['1' => [], '2' => []]);
            $this->fail('403 forbidden exception not thrown as expected');
        } catch (ApiError $exception) {
            $this->assertEquals(403, $exception->getStatusCode());
        }

        $this->loginAdmin();

        $testEntities = $this->testDataProvider->getTestEntities([], ['1' => [], '2' => []]);
        $this->assertCount(2, $testEntities);
    }

    public function testGetCollectionOperationAccessForbidden()
    {
        try {
            $this->testDataProvider->setIsGetCollectionOperationAllowed(false);
            $this->testDataProvider->getTestEntities([], ['1' => [], '2' => []]);
            $this->fail('403 forbidden exception not thrown as expected');
        } catch (ApiError $exception) {
            $this->assertEquals(403, $exception->getStatusCode());
        }
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
        $queryParameters = [];
        parse_str('filter[field0]=value0', $queryParameters);

        $this->testDataProvider->getTestEntities($queryParameters, [[]]);
        $filter = $this->testDataProvider->getOptions()[Options::FILTER];

        $expectedFilter = FilterTreeBuilder::create()->equals('field0', 'value0')->createFilter();

        $this->assertEquals($expectedFilter->toArray(), $filter->toArray());
    }

    /**
     * @throws \Exception
     */
    public function testFilterQueryParameterNotEnabled()
    {
        $config = self::getTestConfig();
        $config['rest']['query']['filter']['enable_query_filters'] = false;
        $this->setUpAndConfigure($config);

        $queryParameters = [];
        parse_str('filter[field0]=value0', $queryParameters);

        $this->testDataProvider->getTestEntities($queryParameters, [[]]);
        $this->assertNull(Options::getFilter($this->testDataProvider->getOptions()));
    }

    /**
     * @throws \Exception
     */
    public function testFilterKeySquareBracketsMissing()
    {
        try {
            $this->testDataProvider->getTestEntities(['filter' => 'value'], [[]]);
            $this->fail('exception not thrown as expected');
        } catch (ApiError $exception) {
            $this->assertEquals(Response::HTTP_BAD_REQUEST, $exception->getStatusCode());
            $this->assertEquals(ErrorIds::FILTER_PARAMETER_MUST_BE_AN_ARRAY, $exception->getErrorId());
        }
    }

    /**
     * @throws \Exception
     */
    public function testFilterInvalid()
    {
        try {
            $queryParameters = [];
            parse_str('filter[field0][foo][bar]=value', $queryParameters);

            $this->testDataProvider->getTestEntities($queryParameters, [[]]);
            $this->fail('exception not thrown as expected');
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
        $preparedFilter = Options::getFilter($this->testDataProvider->getOptions());

        $expectedFilter = FilterTreeBuilder::create()->iContains('field0', 'value0')->createFilter();

        $this->assertEquals($expectedFilter->toArray(), $preparedFilter->toArray());
    }

    /**
     * @throws \Exception
     */
    public function testPreparedFilterNotEnabled()
    {
        $config = self::getTestConfig();
        $config['rest']['query']['filter']['enable_prepared_filters'] = false;
        $this->setUpAndConfigure($config);

        $this->testDataProvider->getTestEntities(['preparedFilter' => 'filter0'], [[]]);
        $this->assertNull(Options::getFilter($this->testDataProvider->getOptions()));
    }

    /**
     * @throws \Exception
     */
    public function testPreparedFilterUndefined()
    {
        try {
            $this->testDataProvider->getTestEntities(['preparedFilter' => '___'], [[]]);
            $this->fail('exception not thrown as expected');
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
            $this->fail('exception not thrown as expected');
        } catch (ApiError $exception) {
            $this->assertEquals(Response::HTTP_FORBIDDEN, $exception->getStatusCode());
            $this->assertEquals(ErrorIds::PREPARED_FILTER_ACCESS_DENIED, $exception->getErrorId());
        }
    }

    /**
     * @throws \Exception
     */
    public function testFilterWithLocalDataAttribute()
    {
        $queryParameters = [];
        parse_str('filter[localData.attribute0]=value0', $queryParameters);

        $this->testDataProvider->getTestEntities($queryParameters, [[]]);
        $filter = Options::getFilter($this->testDataProvider->getOptions());

        $expectedFilter = FilterTreeBuilder::create()->equals('localData.attribute0', 'value0')->createFilter();

        $this->assertEquals($expectedFilter->toArray(), $filter->toArray());
    }

    /**
     * @throws \Exception
     */
    public function testFilterWithForbiddenLocalDataAttribute()
    {
        $queryParameters = [];
        parse_str('filter[localData.forbiddenAttribute]=value0', $queryParameters);

        self::assertEquals([], $this->testDataProvider->getTestEntities($queryParameters, [[]]));
    }

    /**
     * @throws \Exception
     */
    public function testFilterWithForbiddenLocalDataAttributeInAndGroup()
    {
        // condition with forbidden attribute evaluates to constant 'false' -> parent AND group evaluates to 'false'
        $querySting = 'filter[test_group][group][conjunction]=AND&filter[foo][condition][path]=field0&filter[foo][condition][operator]=I_CONTAINS&filter[foo][condition][value]=value0&&filter[foo][condition][memberOf]=test_group&filter[bar][condition][path]=localData.forbiddenAttribute&filter[bar][condition][operator]=EQUALS&filter[bar][condition][value]=value1&&filter[bar][condition][memberOf]=test_group';
        $queryParameters = [];
        parse_str($querySting, $queryParameters);

        self::assertEquals([], $this->testDataProvider->getTestEntities($queryParameters, [[]]));
    }

    /**
     * @throws \Exception
     */
    public function testFilterWithForbiddenLocalDataAttributeInOrGroup()
    {
        // condition with forbidden attribute evaluates to constant 'false', which is to be removed from the parent OR group
        $querySting = 'filter[test_group][group][conjunction]=OR&filter[foo][condition][path]=field0&filter[foo][condition][operator]=I_CONTAINS&filter[foo][condition][value]=value0&&filter[foo][condition][memberOf]=test_group&filter[bar][condition][path]=localData.forbiddenAttribute&filter[bar][condition][operator]=EQUALS&filter[bar][condition][value]=value1&&filter[bar][condition][memberOf]=test_group';
        $queryParameters = [];
        parse_str($querySting, $queryParameters);

        $this->testDataProvider->getTestEntities($queryParameters, [[]]);
        $filter = Options::getFilter($this->testDataProvider->getOptions());

        $expectedFilter = FilterTreeBuilder::create()
            ->iContains('field0', 'value0')->createFilter();

        $this->assertEquals($expectedFilter->toArray(), $filter->toArray());
    }

    public function testSortQueryParameter(): void
    {
        $querySting = 'sort[sort-field0][path]=field0';
        $queryParameters = [];
        parse_str($querySting, $queryParameters);

        $this->testDataProvider->getTestEntities($queryParameters, [[]]);
        $sort = Options::getSort($this->testDataProvider->getOptions());

        $sortFields = $sort->getSortFields();

        $this->assertCount(1, $sortFields);
        $this->assertEquals('field0', Sort::getPath($sortFields[0]));
        $this->assertEquals(Sort::ASCENDING_DIRECTION, Sort::getDirection($sortFields[0]));
    }

    public function testSortQueryParameterNotEnabled(): void
    {
        $testConfig = self::getTestConfig();
        $testConfig['rest']['query']['sort']['enable_sort'] = false;
        $this->setUpAndConfigure($testConfig);

        $querySting = 'sort[sort-field0][path]=field0';
        $queryParameters = [];
        parse_str($querySting, $queryParameters);

        $this->testDataProvider->getTestEntities($queryParameters, [[]]);
        $this->assertNull(Options::getSort($this->testDataProvider->getOptions()));
    }
}
