<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\Rest\Query;

use Dbp\Relay\CoreBundle\Rest\Query\Filter\Filter;
use Dbp\Relay\CoreBundle\Rest\Query\Filter\FromQueryFilterCreator;
use PHPUnit\Framework\TestCase;

class CreateFilterFromQueryTest extends TestCase
{
    /**
     * @throws \Exception
     */
    public function testCreateFromQueryParameters()
    {
        $queryParameters = [];

        $queryParameters['foo'] = [
            'path' => 'field0',
            'operator' => 'CONTAINS',
            'value' => 'value0',
        ];

        $filter = FromQueryFilterCreator::createFilterFromQueryParameters($queryParameters);

        $expectedFilter = Filter::create();
        $expectedFilter->getRootNode()->contains('field0', 'value0');

        $this->assertEquals($expectedFilter->toArray(), $filter->toArray());
    }

//    /**
//     * @throws \Exception
//     */
//    public function testCreateFromQueryString()
//    {
//        $queryString = 'filter[foo][condition][path]=field0&filter[foo][condition][operator]=CONTAINS&filter[foo][condition][value]=value0';
//        $filter = FromQueryFilterCreator::createFilterFromQueryString($queryString);
//
//        $expectedFilter = Filter::create();
//        $expectedFilter->getRootNode()->contains('field0', 'value0');
//
//        $this->assertEquals($expectedFilter->toArray(), $filter->toArray());
//    }
}
