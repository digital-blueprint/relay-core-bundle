<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\Rest\Query\Filter;

use Dbp\Relay\CoreBundle\Rest\Query\Filter\Filter;
use Dbp\Relay\CoreBundle\Rest\Query\Filter\FromQueryFilterCreator;
use Dbp\Relay\CoreBundle\Rest\Query\Utilities;
use PHPUnit\Framework\TestCase;

class CreateFilterFromQueryTest extends TestCase
{
    /**
     * @throws \Exception
     */
    public function testCreateFromQueryParameters()
    {
        $queryParameters = [];

        $queryParameters['foo'] = ['condition' => [
            'path' => 'field0',
            'operator' => 'CONTAINS',
            'value' => 'value0',
        ]];

        $filter = FromQueryFilterCreator::createFilterFromQueryParameters($queryParameters);

        $expectedFilter = Filter::create();
        $expectedFilter->getRootNode()->contains('field0', 'value0');

        $this->assertEquals($expectedFilter->toArray(), $filter->toArray());
    }

    public function testGetQueryParametersFromQueryString()
    {
        $querySting = 'filter[foo][condition][path]=field0&filter[foo][condition][operator]=CONTAINS&filter[foo][condition][value]=value0';

        $filterParameters =
            Utilities::getQueryParametersFromQueryString($querySting, 'filter');

        $queryParameters['foo'] = ['condition' => [
            'path' => 'field0',
            'operator' => 'CONTAINS',
            'value' => 'value0',
        ]];

        $this->assertEquals($queryParameters, $filterParameters);
    }

    /**
     * @throws \Exception
     */
    public function testCreateFromQueryString()
    {
        $querySting = 'filter[foo][condition][path]=field0&filter[foo][condition][operator]=CONTAINS&filter[foo][condition][value]=value0';

        $filter = FromQueryFilterCreator::createFilterFromQueryParameters(
            Utilities::getQueryParametersFromQueryString($querySting, 'filter'));

        $expectedFilter = Filter::create();
        $expectedFilter->getRootNode()->contains('field0', 'value0');

        $this->assertEquals($expectedFilter->toArray(), $filter->toArray());
    }

    /**
     * @throws \Exception
     */
    public function testCreateFromShortcut()
    {
        $queryParameters = [];

        $queryParameters['field0'] = 'value0';

        $filter = FromQueryFilterCreator::createFilterFromQueryParameters($queryParameters);

        $expectedFilter = Filter::create();
        $expectedFilter->getRootNode()->equals('field0', 'value0');

        $this->assertEquals($expectedFilter->toArray(), $filter->toArray());
    }
}
