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
    private $preparedFilterProvider;

    private $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = ['prepared_filters' => [
            [
                'id' => 'filter0',
                'filter' => 'filter[foo][condition][path]=field0&filter[foo][condition][operator]=I_CONTAINS&filter[foo][condition][value]=value0',
                'apply_policy' => 'user.get("ROLE_USER")',
            ],
            [
                'id' => 'filterShortcut',
                'filter' => 'filter[field0]=value0',
                'apply_policy' => 'true',
            ],
        ]];

        $this->preparedFilterProvider = new PreparedFilters();
        $this->preparedFilterProvider->loadConfig($this->config);
    }

    public function testGetPolicies()
    {
        $policies = $this->preparedFilterProvider->getPolicies();

        $this->assertCount(count($this->config['prepared_filters']), $policies);
        $this->assertEquals('user.get("ROLE_USER")', $policies[PreparedFilters::getPolicyNameByFilterIdentifier('filter0')]);
        $this->assertEquals('true', $policies[PreparedFilters::getPolicyNameByFilterIdentifier('filterShortcut')]);
    }

    /**
     * @throws \Exception
     */
    public function testPreparedFilter()
    {
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
