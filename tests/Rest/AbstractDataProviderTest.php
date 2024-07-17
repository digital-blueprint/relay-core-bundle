<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\Rest;

use Dbp\Relay\CoreBundle\Exception\ApiError;
use Dbp\Relay\CoreBundle\Rest\ErrorIds;
use Dbp\Relay\CoreBundle\Rest\Options;
use Dbp\Relay\CoreBundle\Rest\Query\Filter\FilterTreeBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class AbstractDataProviderTest extends TestCase
{
    /** @var TestDataProvider */
    private $testDataProvider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->login(false);
    }

    private function login(bool $admin): void
    {
        $this->testDataProvider = TestDataProvider::create(null, $admin ? TestDataProvider::ADMIN_USER_IDENTIFIER : TestDataProvider::TEST_USER_IDENTIFIER);
        $this->configureTestDataProvider();
    }

    private function logout(): void
    {
        $this->testDataProvider = TestDataProvider::create(null, TestDataProvider::UNAUTHENTICATED_USER_IDENTIFIER);
        $this->configureTestDataProvider();
    }

    private function configureTestDataProvider(): void
    {
        $config = [
            'prepared_filters' => [
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
            ],
            'local_data' => [
                [
                    'local_data_attribute' => 'attribute0',
                    'read_policy' => 'true',
                ],
                [
                    'local_data_attribute' => 'forbiddenAttribute',
                    'read_policy' => 'false',
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

        $this->login(true);

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

        $this->login(true);

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
        $filterParameters = [];
        parse_str('filter[field0]=value0', $filterParameters);

        $this->testDataProvider->getTestEntities($filterParameters, [[]]);
        $filter = $this->testDataProvider->getOptions()[Options::FILTER];

        $expectedFilter = FilterTreeBuilder::create()->equals('field0', 'value0')->createFilter();

        $this->assertEquals($expectedFilter->toArray(), $filter->toArray());
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
            $filterParameters = [];
            parse_str('filter[field0][foo][bar]=value', $filterParameters);

            $this->testDataProvider->getTestEntities($filterParameters, [[]]);
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
        $preparedFilter = $this->testDataProvider->getOptions()[Options::FILTER];

        $expectedFilter = FilterTreeBuilder::create()->iContains('field0', 'value0')->createFilter();

        $this->assertEquals($expectedFilter->toArray(), $preparedFilter->toArray());
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
        $filterParameters = [];
        parse_str('filter[localData.attribute0]=value0', $filterParameters);

        $this->testDataProvider->getTestEntities($filterParameters, [[]]);
        $filter = $this->testDataProvider->getOptions()[Options::FILTER];

        $expectedFilter = FilterTreeBuilder::create()->equals('localData.attribute0', 'value0')->createFilter();

        $this->assertEquals($expectedFilter->toArray(), $filter->toArray());
    }

    /**
     * @throws \Exception
     */
    public function testFilterWithForbiddenLocalDataAttribute()
    {
        $filterParameters = [];
        parse_str('filter[localData.forbiddenAttribute]=value0', $filterParameters);

        self::assertEquals([], $this->testDataProvider->getTestEntities($filterParameters, [[]]));
    }

    /**
     * @throws \Exception
     */
    public function testFilterWithForbiddenLocalDataAttributeInAndGroup()
    {
        // condition with forbidden attribute evaluates to constant 'false' -> parent AND group evaluates to 'false'
        $querySting = 'filter[test_group][group][conjunction]=AND&filter[foo][condition][path]=field0&filter[foo][condition][operator]=I_CONTAINS&filter[foo][condition][value]=value0&&filter[foo][condition][memberOf]=test_group&filter[bar][condition][path]=localData.forbiddenAttribute&filter[bar][condition][operator]=EQUALS&filter[bar][condition][value]=value1&&filter[bar][condition][memberOf]=test_group';
        $filterParameters = [];
        parse_str($querySting, $filterParameters);

        self::assertEquals([], $this->testDataProvider->getTestEntities($filterParameters, [[]]));
    }

    /**
     * @throws \Exception
     */
    public function testFilterWithForbiddenLocalDataAttributeInOrGroup()
    {
        // condition with forbidden attribute evaluates to constant 'false', which is to be removed from the parent OR group
        $querySting = 'filter[test_group][group][conjunction]=OR&filter[foo][condition][path]=field0&filter[foo][condition][operator]=I_CONTAINS&filter[foo][condition][value]=value0&&filter[foo][condition][memberOf]=test_group&filter[bar][condition][path]=localData.forbiddenAttribute&filter[bar][condition][operator]=EQUALS&filter[bar][condition][value]=value1&&filter[bar][condition][memberOf]=test_group';
        $filterParameters = [];
        parse_str($querySting, $filterParameters);

        $this->testDataProvider->getTestEntities($filterParameters, [[]]);
        $filter = $this->testDataProvider->getOptions()[Options::FILTER];

        $expectedFilter = FilterTreeBuilder::create()
            ->iContains('field0', 'value0')->createFilter();

        $this->assertEquals($expectedFilter->toArray(), $filter->toArray());
    }
}
