<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\Rest\Query\Filter;

use Dbp\Relay\CoreBundle\Rest\Query\Filter\FilterTreeBuilder;
use Dbp\Relay\CoreBundle\Rest\Query\Filter\FromQueryFilterCreator;
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
                'filter' => 'filter[foo][condition][path]=field0&filter[foo][condition][operator]=I_CONTAINS&filter[foo][condition][value]=value0',
                'use_policy' => 'user.get("ROLE_USER")',
                'force_use_policy' => 'true',
            ],
            [
                'id' => 'filterShortcut',
                'filter' => 'filter[field0]=value0',
                'use_policy' => 'true',
                'force_use_policy' => 'user.get("ROLE_VIEWER")',
            ],
            [
                'id' => 'filterShortcut2',
                'filter' => 'filter[field0]=value2',
                'use_policy' => 'user.get("ROLE_ADMIN")',
                'force_use_policy' => 'user.get("ROLE_USER")',
            ],
            [
                'id' => 'filterDefault',
            ],
        ]];

        $this->preparedFilterProvider = new PreparedFilters();
        $this->preparedFilterProvider->loadConfig($this->config);
    }

    public function testGetUsePolicies()
    {
        $policies = $this->preparedFilterProvider->getUsePolicies();

        $this->assertCount(count($this->config['prepared_filters']), $policies);
        $this->assertEquals('user.get("ROLE_USER")', $policies['filter0']);
        $this->assertEquals('true', $policies['filterShortcut']);
        $this->assertEquals('user.get("ROLE_ADMIN")', $policies['filterShortcut2']);
        $this->assertEquals('false', $policies['filterDefault']);
    }

    public function testGetForceUsePolicies()
    {
        $policies = $this->preparedFilterProvider->getForceUsePolicies();

        $this->assertCount(count($this->config['prepared_filters']), $policies);
        $this->assertEquals('true', $policies['filter0']);
        $this->assertEquals('user.get("ROLE_VIEWER")', $policies['filterShortcut']);
        $this->assertEquals('user.get("ROLE_USER")', $policies['filterShortcut2']);
        $this->assertEquals('false', $policies['filterDefault']);
    }

    /**
     * @throws \Exception
     */
    public function testPreparedFilter()
    {
        $this->assertFalse($this->preparedFilterProvider->isPreparedFilterDefined('foo'));
        $this->assertTrue($this->preparedFilterProvider->isPreparedFilterDefined('filter0'));

        $this->assertNull($this->preparedFilterProvider->getPreparedFilterQueryString('foo'));
        $preparedFilterQueryString = $this->preparedFilterProvider->getPreparedFilterQueryString('filter0');
        $this->assertNotNull($preparedFilterQueryString);

        $preparedFilter = FromQueryFilterCreator::createFilterFromQueryParameters(
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

        $preparedFilter = FromQueryFilterCreator::createFilterFromQueryParameters(
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
