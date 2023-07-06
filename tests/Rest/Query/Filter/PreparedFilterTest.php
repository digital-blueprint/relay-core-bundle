<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\Rest\Query\Filter;

use Dbp\Relay\CoreBundle\Rest\Query\Filter\Filter;
use Dbp\Relay\CoreBundle\Rest\Query\Filter\PreparedFilterProvider;
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
                'filter' => 'filter[foo][condition][path]=field0&filter[foo][condition][operator]=CONTAINS&filter[foo][condition][value]=value0',
                'apply_policy' => 'true',
            ],
            [
                'id' => 'filterShortcut',
                'filter' => 'filter[field0]=value0',
                'apply_policy' => 'true',
            ],
            [
                'id' => 'filterForbidden',
                'filter' => 'filter[foo][condition][path]=field0&filter[foo][condition][operator]=CONTAINS&filter[foo][condition][value]=value0',
                'apply_policy' => 'false',
            ],
        ]];

        $this->preparedFilterProvider = new PreparedFilterProvider();
        $this->preparedFilterProvider->loadConfig($this->config);
    }

    public function testGetPolicies()
    {
        $policies = $this->preparedFilterProvider->getPolicies();

        $this->assertCount(count($this->config['prepared_filters']), $policies);
    }

    /**
     * @throws \Exception
     */
    public function testPreparedFilter()
    {
        $preparedFilter = $this->preparedFilterProvider->getPreparedFilterById('filter0');

        $expectedFilter = Filter::create();
        $expectedFilter->getRootNode()->contains('field0', 'value0');

        $this->assertEquals($expectedFilter->toArray(), $preparedFilter->toArray());
    }

    /**
     * @throws \Exception
     */
    public function testPreparedFilterUndefined()
    {
        $this->assertNull($this->preparedFilterProvider->getPreparedFilterById('___'));
    }
}
