<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\Rest;

use Dbp\Relay\CoreBundle\Exception\ApiError;
use Dbp\Relay\CoreBundle\Rest\ErrorIds;
use Dbp\Relay\CoreBundle\Rest\Options;
use Dbp\Relay\CoreBundle\Rest\Query\Filter\FilterTreeBuilder;
use Dbp\Relay\CoreBundle\Rest\Query\Sort\Sort;
use Dbp\Relay\CoreBundle\TestUtils\DataProviderTester;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Response;

class AbstractDataProviderTest extends TestCase
{
    private TestDataProvider $testDataProvider;
    private DataProviderTester $testDataProviderTester;

    protected function setUp(): void
    {
        $this->testDataProvider = new TestDataProvider(new EventDispatcher());
        $this->testDataProvider->setConfig(self::getTestConfig());
        $this->testDataProviderTester = DataProviderTester::create($this->testDataProvider,
            TestEntity::class, ['TestEntity:output', 'LocalData:output']);

        DataProviderTester::login($this->testDataProvider,
            TestDataProvider::TEST_USER_IDENTIFIER, [
                'ROLE_USER' => true,
                'ROLE_ADMIN' => false,
            ]);
    }

    private function loginAdmin(): void
    {
        DataProviderTester::login($this->testDataProvider,
            TestDataProvider::ADMIN_USER_IDENTIFIER, [
                'ROLE_USER' => false,
                'ROLE_ADMIN' => true,
            ]);
    }

    private function logout(): void
    {
        DataProviderTester::logout($this->testDataProvider);
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
        $this->testDataProvider->setSourceData(['id' => []]);
        $testEntity = $this->testDataProviderTester->getItem('id');

        $this->assertEquals('id', $testEntity->getIdentifier());
    }

    public function testGetEntityUnauthenticated()
    {
        $this->logout();
        try {
            $this->testDataProviderTester->getItem('id');
            $this->fail('401 unauthorized exception not thrown as expected');
        } catch (ApiError $exception) {
            $this->assertEquals(401, $exception->getStatusCode());
        }
    }

    public function testGetEntityUnauthenticatedAllowed()
    {
        $this->logout();
        $this->testDataProvider->setAllowUnauthenticatedAccess(true);
        $this->testDataProvider->setSourceData(['id' => []]);
        $testEntity = $this->testDataProviderTester->getItem('id');

        $this->assertEquals('id', $testEntity->getIdentifier());
    }

    public function testGetEntityOperationAccessForbidden()
    {
        try {
            $this->testDataProvider->setIsGetItemOperationAllowed(false);
            $this->testDataProvider->setSourceData(['id' => []]);
            $this->testDataProviderTester->getItem('id');
            $this->fail('403 forbidden exception not thrown as expected');
        } catch (ApiError $exception) {
            $this->assertEquals(403, $exception->getStatusCode());
        }
    }

    public function testGetEntityAdminOnly()
    {
        try {
            $this->testDataProvider->setSourceData(['_admin_only' => []]);
            $this->testDataProviderTester->getItem('_admin_only');
            $this->fail('403 forbidden exception not thrown as expected');
        } catch (ApiError $exception) {
            $this->assertEquals(403, $exception->getStatusCode());
        }

        $this->loginAdmin();

        $testEntity = $this->testDataProviderTester->getItem('_admin_only');
        $this->assertEquals('_admin_only', $testEntity->getIdentifier());
    }

    public function testGetEntityNotFound()
    {
        // don't provide source data for 'id'
        $testEntity = $this->testDataProviderTester->getItem('id');

        $this->assertNull($testEntity);
    }

    public function testGetEmptyPage()
    {
        $testEntities = $this->testDataProviderTester->getPage(1, 4);

        $this->assertEmpty($testEntities);
    }

    public function testGetCollection()
    {
        $this->testDataProvider->setSourceData(['1' => [], '2' => []]);
        $testEntities = $this->testDataProviderTester->getPage();

        $this->assertCount(2, $testEntities);
        $this->assertEquals('1', $testEntities[0]->getIdentifier());
        $this->assertEquals('2', $testEntities[1]->getIdentifier());
    }

    public function testGetCollectionUnauthenticated()
    {
        $this->logout();
        try {
            $this->testDataProviderTester->getPage();
            $this->fail('401 unauthorized exception not thrown as expected');
        } catch (ApiError $exception) {
            $this->assertEquals(401, $exception->getStatusCode());
        }
    }

    public function testGetCollectionUnauthenticatedAllowed()
    {
        $this->logout();
        $this->testDataProvider->setAllowUnauthenticatedAccess(true);
        $this->testDataProvider->setSourceData(['1' => [], '2' => []]);
        $testEntities = $this->testDataProviderTester->getPage();
        $this->assertCount(2, $testEntities);
        $this->assertEquals('1', $testEntities[0]->getIdentifier());
        $this->assertEquals('2', $testEntities[1]->getIdentifier());
    }

    public function testGetCollectionAdminOnly()
    {
        try {
            $this->testDataProvider->setSourceData(['1' => [], '2' => []]);
            $this->testDataProviderTester->getPage(filters: [TestDataProvider::INCLUDE_ADMIN_ONLY_ENTITIES_FILTER => true]);
            $this->fail('403 forbidden exception not thrown as expected');
        } catch (ApiError $exception) {
            $this->assertEquals(403, $exception->getStatusCode());
        }

        $this->loginAdmin();

        $testEntities = $this->testDataProviderTester->getPage();
        $this->assertCount(2, $testEntities);
    }

    public function testGetCollectionOperationAccessForbidden()
    {
        try {
            $this->testDataProvider->setSourceData(['1' => [], '2' => []]);
            $this->testDataProvider->setIsGetCollectionOperationAllowed(false);
            $this->testDataProviderTester->getPage();
            $this->fail('403 forbidden exception not thrown as expected');
        } catch (ApiError $exception) {
            $this->assertEquals(403, $exception->getStatusCode());
        }
    }

    public function testPaginationParameters()
    {
        $currentPage = 2;
        $itemsPerPage = 3;

        // must contain the page range for this test to work
        $sourceData = [
            [], [], [], [], [], [], [],
        ];
        $this->testDataProvider->setSourceData($sourceData);
        $items = $this->testDataProviderTester->getPage($currentPage, $itemsPerPage);

        $this->assertCount($itemsPerPage, $items);
        $this->assertEquals(3, $items[0]->getIdentifier());
        $this->assertEquals(4, $items[1]->getIdentifier());
        $this->assertEquals(5, $items[2]->getIdentifier());
    }

    public function testPaginationParametersPageNotFull()
    {
        $currentPage = 3;
        $itemsPerPage = 3;

        // must contain the page range for this test to work
        $sourceData = [
            [], [], [], [], [], [], [],
        ];
        $this->testDataProvider->setSourceData($sourceData);
        $items = $this->testDataProviderTester->getPage($currentPage, $itemsPerPage);

        $this->assertCount(1, $items);
        $this->assertEquals(6, $items[0]->getIdentifier());
    }

    /**
     * @throws \Exception
     */
    public function testFilterQueryParameter()
    {
        $queryParameters = [];
        parse_str('filter[field0]=value0', $queryParameters);

        $this->testDataProvider->setSourceData([[]]);
        $this->testDataProviderTester->getPage(filters: $queryParameters);
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
        $this->testDataProvider->setConfig($config);

        $queryParameters = [];
        parse_str('filter[field0]=value0', $queryParameters);

        $this->testDataProvider->setSourceData([[]]);
        $this->testDataProviderTester->getPage(filters: $queryParameters);
        $this->assertNull(Options::getFilter($this->testDataProvider->getOptions()));
    }

    /**
     * @throws \Exception
     */
    public function testFilterKeySquareBracketsMissing()
    {
        try {
            $this->testDataProvider->setSourceData([[]]);
            $this->testDataProviderTester->getPage(filters: ['filter' => 'value']);
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

            $this->testDataProvider->setSourceData([[]]);
            $this->testDataProviderTester->getPage(filters: $queryParameters);
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
        $this->testDataProvider->setSourceData([[]]);
        $this->testDataProviderTester->getPage(filters: ['preparedFilter' => 'filter0']);
        $preparedFilter = Options::getFilter($this->testDataProvider->getOptions());

        $expectedFilter = FilterTreeBuilder::create()->iContains('field0', 'value0')->createFilter();

        $this->assertEquals($expectedFilter->toArray(), $preparedFilter->toArray());
    }

    /**
     * @throws \Exception
     */
    public function testEnforcedFilter()
    {
        $testConfig = self::getTestConfig();
        $testConfig['rest']['query']['filter']['enforced_filter'] = 'Filter.create().equals("localData.attribute0", true)';
        $this->testDataProvider->setConfig($testConfig);

        $this->testDataProvider->setSourceData([[]]);
        $this->testDataProviderTester->getPage();
        $filter = Options::getFilter($this->testDataProvider->getOptions());

        $expectedFilter = FilterTreeBuilder::create()->equals('localData.attribute0', true)->createFilter();

        $this->assertEquals($expectedFilter->toArray(), $filter->toArray());
    }

    public function testEnforcedFilterInvalid()
    {
        // expression does not return a filter tree
        $testConfig = self::getTestConfig();
        $testConfig['rest']['query']['filter']['enforced_filter'] = 'true';
        $this->testDataProvider->setConfig($testConfig);

        $this->testDataProvider->setSourceData([[]]);
        $this->expectException(\RuntimeException::class);
        $this->testDataProviderTester->getPage();
    }

    public function testEnforcedFilterInvalid2()
    {
        // filter misspelled
        $testConfig = self::getTestConfig();
        $testConfig['rest']['query']['filter']['enforced_filter'] = 'filter.create()';
        $this->testDataProvider->setConfig($testConfig);

        $this->testDataProvider->setSourceData([[]]);
        $this->expectException(\RuntimeException::class);
        $this->testDataProviderTester->getPage();
    }

    /**
     * @throws \Exception
     */
    public function testEnforcedFilterInvalid3()
    {
        // filter syntax (missing "end")
        $testConfig = self::getTestConfig();
        $testConfig['rest']['query']['filter']['enforced_filter'] = 'Filter.create().and()';
        $this->testDataProvider->setConfig($testConfig);

        $this->testDataProvider->setSourceData([[]]);
        $this->expectException(\RuntimeException::class);
        $this->testDataProviderTester->getPage();
    }

    /**
     * @throws \Exception
     */
    public function testCombineEnforcedFilterWithQueryFilter()
    {
        $testConfig = self::getTestConfig();
        $testConfig['rest']['query']['filter']['enforced_filter'] = 'Filter.create().equals("localData.attribute0", true)';
        $this->testDataProvider->setConfig($testConfig);

        $queryParameters = [];
        parse_str('filter[field0]=value0', $queryParameters);
        $queryParameters['preparedFilter'] = 'filter0';

        $this->testDataProvider->setSourceData([[]]);
        $this->testDataProviderTester->getPage(filters: $queryParameters);
        $filter = Options::getFilter($this->testDataProvider->getOptions());

        $expectedFilter = FilterTreeBuilder::create()
            ->equals('localData.attribute0', true)
            ->equals('field0', 'value0')
            ->iContains('field0', 'value0')
            ->createFilter();

        $this->assertEquals($expectedFilter->toArray(), $filter->toArray());
    }

    /**
     * @throws \Exception
     */
    public function testCombineEnforcedFilterWithQueryFilterAndPreparedFilter()
    {
        $testConfig = self::getTestConfig();
        $testConfig['rest']['query']['filter']['enforced_filter'] = 'Filter.create().equals("localData.attribute0", true)';
        $this->testDataProvider->setConfig($testConfig);

        $queryParameters = [];
        parse_str('filter[field0]=value0', $queryParameters);

        $this->testDataProvider->setSourceData([[]]);
        $this->testDataProviderTester->getPage(filters: $queryParameters);
        $filter = Options::getFilter($this->testDataProvider->getOptions());

        $expectedFilter = FilterTreeBuilder::create()
            ->equals('localData.attribute0', true)
            ->equals('field0', 'value0')
            ->createFilter();

        $this->assertEquals($expectedFilter->toArray(), $filter->toArray());
    }

    /**
     * @throws \Exception
     */
    public function testPreparedFilterNotEnabled()
    {
        $config = self::getTestConfig();
        $config['rest']['query']['filter']['enable_prepared_filters'] = false;
        $this->testDataProvider->setConfig($config);

        $this->testDataProvider->setSourceData([[]]);
        $this->testDataProviderTester->getPage(filters: ['preparedFilter' => 'filter0']);
        $this->assertNull(Options::getFilter($this->testDataProvider->getOptions()));
    }

    /**
     * @throws \Exception
     */
    public function testPreparedFilterUndefined()
    {
        try {
            $this->testDataProvider->setSourceData([[]]);
            $this->testDataProviderTester->getPage(filters: ['preparedFilter' => '___']);
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
            $this->testDataProvider->setSourceData([[]]);
            $this->testDataProviderTester->getPage(filters: ['preparedFilter' => 'filterForbidden']);
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

        $this->testDataProvider->setSourceData([[]]);
        $this->testDataProviderTester->getPage(filters: $queryParameters);
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

        $this->testDataProvider->setSourceData([[]]);
        $this->assertEmpty($this->testDataProviderTester->getPage(filters: $queryParameters));
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

        $this->testDataProvider->setSourceData([[]]);
        $this->assertEmpty($this->testDataProviderTester->getPage(filters: $queryParameters));
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

        $this->testDataProvider->setSourceData([[]]);
        $this->testDataProviderTester->getPage(filters: $queryParameters);
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

        $this->testDataProvider->setSourceData([[]]);
        $this->testDataProviderTester->getPage(filters: $queryParameters);
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
        $this->testDataProvider->setConfig($testConfig);

        $querySting = 'sort[sort-field0][path]=field0';
        $queryParameters = [];
        parse_str($querySting, $queryParameters);

        $this->testDataProvider->setSourceData([[]]);
        $this->testDataProviderTester->getPage(filters: $queryParameters);
        $this->assertNull(Options::getSort($this->testDataProvider->getOptions()));
    }
}
