<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\Rest\Query\Filter;

use Dbp\Relay\CoreBundle\Rest\Query\Filter\FilterTreeBuilder;
use Dbp\Relay\CoreBundle\Rest\Query\Filter\PreparedFilters;
use Dbp\Relay\CoreBundle\Rest\Query\Parameters;
use PHPUnit\Framework\TestCase;

class PreparedFilterTest extends TestCase
{
    private ?PreparedFilters $preparedFilterProvider = null;

    private ?array $config = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = ['prepared_filters' => [
            [
                'id' => 'filter0',
                'filter' => 'filter[foo][condition][path]=field0&filter[foo][condition][operator]=I_CONTAINS&filter[foo][condition][value]="value0"',
                'use_policy' => 'user.get("IS_USER")',
                'force_use_policy' => 'true',
            ],
            [
                'id' => 'filterShortcut',
                'filter' => 'filter[field0]="value0"',
                'use_policy' => 'true',
                'force_use_policy' => 'user.get("IS_VIEWER")',
            ],
            [
                'id' => 'filterShortcut2',
                'filter' => 'filter[field0]="value2"',
                'use_policy' => 'user.get("IS_ADMIN")',
                'force_use_policy' => 'user.get("IS_USER")',
            ],
            [
                'id' => 'filterBackendOnly',
                'filter' => 'filter[field0]="value0"',
                'use_policy' => null,
                'force_use_policy' => 'user.get("IS_ADMIN")',
            ],
            [
                'id' => 'filterFrontendOnly',
                'use_policy' => 'user.get("IS_USER")',
                'force_use_policy' => null,
            ],
        ]];

        $this->preparedFilterProvider = new PreparedFilters();
        $this->preparedFilterProvider->loadConfig($this->config);
    }

    public function testGetUsePolicies()
    {
        $policies = $this->preparedFilterProvider->getUsePolicies();

        $this->assertCount(4, $policies);
        $this->assertEquals('user.get("IS_USER")', $policies['filter0']);
        $this->assertEquals('true', $policies['filterShortcut']);
        $this->assertEquals('user.get("IS_ADMIN")', $policies['filterShortcut2']);
        $this->assertEquals('user.get("IS_USER")', $policies['filterFrontendOnly']);
    }

    public function testGetForceUsePolicies()
    {
        $policies = $this->preparedFilterProvider->getForceUsePolicies();

        $this->assertCount(4, $policies);
        $this->assertEquals('true', $policies['filter0']);
        $this->assertEquals('user.get("IS_VIEWER")', $policies['filterShortcut']);
        $this->assertEquals('user.get("IS_USER")', $policies['filterShortcut2']);
        $this->assertEquals('user.get("IS_ADMIN")', $policies['filterBackendOnly']);
    }

    /**
     * @throws \Exception
     */
    public function testPreparedFilter()
    {
        $this->assertFalse($this->preparedFilterProvider->isPreparedFilterDefinedForFrontend('foo'));
        $this->assertTrue($this->preparedFilterProvider->isPreparedFilterDefinedForFrontend('filter0'));
        $this->assertTrue($this->preparedFilterProvider->isPreparedFilterDefinedForFrontend('filterShortcut'));
        $this->assertTrue($this->preparedFilterProvider->isPreparedFilterDefinedForFrontend('filterShortcut2'));
        $this->assertFalse($this->preparedFilterProvider->isPreparedFilterDefinedForFrontend('filterBackendOnly'));
        $this->assertTrue($this->preparedFilterProvider->isPreparedFilterDefinedForFrontend('filterFrontendOnly'));

        $this->assertNull($this->preparedFilterProvider->getPreparedFilterQueryString('foo'));
        $this->assertNotNull($this->preparedFilterProvider->getPreparedFilterQueryString('filter0'));
        $this->assertNotNull($this->preparedFilterProvider->getPreparedFilterQueryString('filterShortcut'));
        $this->assertNotNull($this->preparedFilterProvider->getPreparedFilterQueryString('filterShortcut2'));
        $this->assertNotNull($this->preparedFilterProvider->getPreparedFilterQueryString('filterBackendOnly'));
        $this->assertNotNull($this->preparedFilterProvider->getPreparedFilterQueryString('filterFrontendOnly'));

        $preparedFilterQueryString = $this->preparedFilterProvider->getPreparedFilterQueryString('filter0');

        $preparedFilter = CreateFilterFromQueryTest::createFilterFromQueryParameters(
            Parameters::getQueryParametersFromQueryString($preparedFilterQueryString, Parameters::FILTER), ['field0']);

        $expectedFilter = FilterTreeBuilder::create()->iContains('field0', 'value0')->createFilter();

        $this->assertEquals($expectedFilter->toArray(), $preparedFilter->toArray());
    }

    /**
     * @throws \Exception
     */
    public function testPreparedFilterShortcut()
    {
        $preparedFilterQueryString = $this->preparedFilterProvider->getPreparedFilterQueryString('filterShortcut');
        $this->assertNotNull($preparedFilterQueryString);

        $preparedFilter = CreateFilterFromQueryTest::createFilterFromQueryParameters(
            Parameters::getQueryParametersFromQueryString($preparedFilterQueryString, Parameters::FILTER), ['field0']);

        $expectedFilter = FilterTreeBuilder::create()->equals('field0', 'value0')->createFilter();

        $this->assertEquals($expectedFilter->toArray(), $preparedFilter->toArray());
    }

    /**
     * @throws \Exception
     */
    public function testPreparedFilterUndefined()
    {
        $this->assertNull($this->preparedFilterProvider->getPreparedFilterQueryString('___'));
    }
}
