<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\Rest\Query\Filter;

use Dbp\Relay\CoreBundle\Rest\Query\Parameters;
use Dbp\Relay\CoreBundle\Rest\Query\Sort\FromQuerySortCreator;
use Dbp\Relay\CoreBundle\Rest\Query\Sort\Sort;
use PHPUnit\Framework\TestCase;

class CreateSortFromQueryTest extends TestCase
{
    /**
     * @throws \Exception
     */
    public function testCreateFromQueryParameters()
    {
        $queryParameters = [0 => [
            'path' => 'field0',
            'direction' => 'DESC',
        ]];

        $sort = FromQuerySortCreator::createSortFromQueryParameters($queryParameters, ['field0']);

        $sortFields = $sort->getSortFields();
        $this->assertCount(1, $sortFields);
        $this->assertEquals('field0', Sort::getPath($sortFields[0]));
        $this->assertEquals(Sort::DESCENDING_DIRECTION, Sort::getDirection($sortFields[0]));
    }

    public function testGetQueryParametersFromQueryString()
    {
        $querySting = 'sort[sort-field0][path]=field0&sort[sort-field0][direction]=DESC';

        $sortParameters =
            Parameters::getQueryParametersFromQueryString($querySting, 'sort');

        $queryParameters['sort-field0'] = [
            'path' => 'field0',
            'direction' => 'DESC',
        ];

        $this->assertEquals($queryParameters, $sortParameters);
    }

    /**
     * @throws \Exception
     */
    public function testCreateFromQueryStringDefaultDirection()
    {
        $querySting = 'sort[sort-field0][path]=field0';

        $sort = FromQuerySortCreator::createSortFromQueryParameters(
            Parameters::getQueryParametersFromQueryString($querySting, 'sort'), ['field0']);

        $sortFields = $sort->getSortFields();

        $this->assertCount(1, $sortFields);
        $this->assertEquals('field0', Sort::getPath($sortFields[0]));
        $this->assertEquals(Sort::ASCENDING_DIRECTION, Sort::getDirection($sortFields[0]));
    }

    /**
     * @throws \Exception
     */
    public function testCreateFromQueryStringAscending()
    {
        $querySting = 'sort[sort-field0][path]=field0&sort[sort-field0][direction]=ASC';

        $sort = FromQuerySortCreator::createSortFromQueryParameters(
            Parameters::getQueryParametersFromQueryString($querySting, 'sort'), ['field0']);

        $sortFields = $sort->getSortFields();

        $this->assertCount(1, $sortFields);
        $this->assertEquals('field0', Sort::getPath($sortFields[0]));
        $this->assertEquals(Sort::ASCENDING_DIRECTION, Sort::getDirection($sortFields[0]));
    }

    /**
     * @throws \Exception
     */
    public function testCreateFromQueryStringDescending()
    {
        $querySting = 'sort[sort-field0][path]=field0&sort[sort-field0][direction]=DESC';

        $sort = FromQuerySortCreator::createSortFromQueryParameters(
            Parameters::getQueryParametersFromQueryString($querySting, 'sort'), ['field0']);

        $sortFields = $sort->getSortFields();

        $this->assertCount(1, $sortFields);
        $this->assertEquals('field0', Sort::getPath($sortFields[0]));
        $this->assertEquals(Sort::DESCENDING_DIRECTION, Sort::getDirection($sortFields[0]));
    }

    /**
     * @throws \Exception
     */
    public function testCreateFromQueryStringShortDefault()
    {
        $querySting = 'sort=field0';

        $sort = FromQuerySortCreator::createSortFromQueryParameters(
            Parameters::getQueryParametersFromQueryString($querySting, 'sort'), ['field0']);

        $sortFields = $sort->getSortFields();

        $this->assertCount(1, $sortFields);
        $this->assertEquals('field0', Sort::getPath($sortFields[0]));
        $this->assertEquals(Sort::ASCENDING_DIRECTION, Sort::getDirection($sortFields[0]));
    }

    /**
     * @throws \Exception
     */
    public function testCreateFromQueryStringShortDescending()
    {
        $querySting = 'sort=-field0';

        $sort = FromQuerySortCreator::createSortFromQueryParameters(
            Parameters::getQueryParametersFromQueryString($querySting, 'sort'), ['field0']);

        $sortFields = $sort->getSortFields();

        $this->assertCount(1, $sortFields);
        $this->assertEquals('field0', Sort::getPath($sortFields[0]));
        $this->assertEquals(Sort::DESCENDING_DIRECTION, Sort::getDirection($sortFields[0]));
    }

    /**
     * @throws \Exception
     */
    public function testCreateFromQueryStringMultipleFields()
    {
        $querySting = 'sort[sort-field0][path]=field0&sort[sort-field0][direction]=DESC&sort[sort-field1][path]=field1&sort[sort-field1][direction]=ASC';

        $sort = FromQuerySortCreator::createSortFromQueryParameters(
            Parameters::getQueryParametersFromQueryString($querySting, 'sort'), ['field0', 'field1']);

        $sortFields = $sort->getSortFields();

        $this->assertCount(2, $sortFields);
        $this->assertEquals('field0', Sort::getPath($sortFields[0]));
        $this->assertEquals(Sort::DESCENDING_DIRECTION, Sort::getDirection($sortFields[0]));
        $this->assertEquals('field1', Sort::getPath($sortFields[1]));
        $this->assertEquals(Sort::ASCENDING_DIRECTION, Sort::getDirection($sortFields[1]));
    }

    /**
     * @throws \Exception
     */
    public function testCreateFromQueryStringMultipleFieldsWithDefaults1()
    {
        $querySting = 'sort[sort-field0][path]=field0&sort[sort-field1][path]=field1&sort[sort-field1][direction]=DESC';

        $sort = FromQuerySortCreator::createSortFromQueryParameters(
            Parameters::getQueryParametersFromQueryString($querySting, 'sort'), ['field0', 'field1']);

        $sortFields = $sort->getSortFields();

        $this->assertCount(2, $sortFields);
        $this->assertEquals('field0', Sort::getPath($sortFields[0]));
        $this->assertEquals(Sort::ASCENDING_DIRECTION, Sort::getDirection($sortFields[0]));
        $this->assertEquals('field1', Sort::getPath($sortFields[1]));
        $this->assertEquals(Sort::DESCENDING_DIRECTION, Sort::getDirection($sortFields[1]));
    }

    /**
     * @throws \Exception
     */
    public function testCreateFromQueryStringMultipleFieldsWithDefaults2()
    {
        $querySting = 'sort[sort-field0][path]=field0&sort[sort-field1][path]=field1';

        $sort = FromQuerySortCreator::createSortFromQueryParameters(
            Parameters::getQueryParametersFromQueryString($querySting, 'sort'), ['field0', 'field1']);

        $sortFields = $sort->getSortFields();

        $this->assertCount(2, $sortFields);
        $this->assertEquals('field0', Sort::getPath($sortFields[0]));
        $this->assertEquals(Sort::ASCENDING_DIRECTION, Sort::getDirection($sortFields[0]));
        $this->assertEquals('field1', Sort::getPath($sortFields[1]));
        $this->assertEquals(Sort::ASCENDING_DIRECTION, Sort::getDirection($sortFields[1]));
    }
}
