<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\Rest\Query\Filter;

use Dbp\Relay\CoreBundle\Rest\Query\Parameters;
use Dbp\Relay\CoreBundle\Rest\Query\Sort\FromQuerySortCreator;
use Dbp\Relay\CoreBundle\Rest\Query\Sort\Sort;
use Dbp\Relay\CoreBundle\Rest\Query\Sort\SortException;
use Dbp\Relay\CoreBundle\Rest\Query\Sort\SortField;
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

        $sort = self::createSortFromQueryParameters($queryParameters, ['field0']);

        $sortFields = $sort->getSortFields();
        $this->assertCount(1, $sortFields);
        $this->assertEquals('field0', $sortFields[0]->getPath());
        $this->assertEquals(SortField::DESCENDING_DIRECTION, $sortFields[0]->getDirection());
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

        $sort = self::createSortFromQueryParameters(
            Parameters::getQueryParametersFromQueryString($querySting, 'sort'), ['field0']);

        $sortFields = $sort->getSortFields();

        $this->assertCount(1, $sortFields);
        $this->assertEquals('field0', $sortFields[0]->getPath());
        $this->assertEquals(SortField::ASCENDING_DIRECTION, $sortFields[0]->getDirection());
    }

    /**
     * @throws \Exception
     */
    public function testCreateFromQueryStringAscending()
    {
        $querySting = 'sort[sort-field0][path]=field0&sort[sort-field0][direction]=ASC';

        $sort = self::createSortFromQueryParameters(
            Parameters::getQueryParametersFromQueryString($querySting, 'sort'), ['field0']);

        $sortFields = $sort->getSortFields();

        $this->assertCount(1, $sortFields);
        $this->assertEquals('field0', $sortFields[0]->getPath());
        $this->assertEquals(SortField::ASCENDING_DIRECTION, $sortFields[0]->getDirection());
    }

    /**
     * @throws \Exception
     */
    public function testCreateFromQueryStringDescending()
    {
        $querySting = 'sort[sort-field0][path]=field0&sort[sort-field0][direction]=DESC';

        $sort = self::createSortFromQueryParameters(
            Parameters::getQueryParametersFromQueryString($querySting, 'sort'), ['field0']);

        $sortFields = $sort->getSortFields();

        $this->assertCount(1, $sortFields);
        $this->assertEquals('field0', $sortFields[0]->getPath());
        $this->assertEquals(SortField::DESCENDING_DIRECTION, $sortFields[0]->getDirection());
    }

    /**
     * @throws \Exception
     */
    public function testCreateFromQueryStringShortDefault()
    {
        $querySting = 'sort=field0';

        $sort = self::createSortFromQueryParameters(
            Parameters::getQueryParametersFromQueryString($querySting, 'sort'), ['field0']);

        $sortFields = $sort->getSortFields();

        $this->assertCount(1, $sortFields);
        $this->assertEquals('field0', $sortFields[0]->getPath());
        $this->assertEquals(SortField::ASCENDING_DIRECTION, $sortFields[0]->getDirection());
    }

    /**
     * @throws \Exception
     */
    public function testCreateFromQueryStringShortDescending()
    {
        $querySting = 'sort=-field0';

        $sort = self::createSortFromQueryParameters(
            Parameters::getQueryParametersFromQueryString($querySting, 'sort'), ['field0']);

        $sortFields = $sort->getSortFields();

        $this->assertCount(1, $sortFields);
        $this->assertEquals('field0', $sortFields[0]->getPath());
        $this->assertEquals(SortField::DESCENDING_DIRECTION, $sortFields[0]->getDirection());
    }

    /**
     * @throws \Exception
     */
    public function testCreateFromQueryStringMultipleFields()
    {
        $querySting = 'sort[sort-field0][path]=field0&sort[sort-field0][direction]=DESC&sort[sort-field1][path]=field1&sort[sort-field1][direction]=ASC';

        $sort = self::createSortFromQueryParameters(
            Parameters::getQueryParametersFromQueryString($querySting, 'sort'), ['field0', 'field1']);

        $sortFields = $sort->getSortFields();

        $this->assertCount(2, $sortFields);
        $this->assertEquals('field0', $sortFields[0]->getPath());
        $this->assertEquals(SortField::DESCENDING_DIRECTION, $sortFields[0]->getDirection());
        $this->assertEquals('field1', $sortFields[1]->getPath());
        $this->assertEquals(SortField::ASCENDING_DIRECTION, $sortFields[1]->getDirection());
    }

    /**
     * @throws \Exception
     */
    public function testCreateFromQueryStringMultipleFieldsWithDefaults1()
    {
        $querySting = 'sort[sort-field0][path]=field0&sort[sort-field1][path]=field1&sort[sort-field1][direction]=DESC';

        $sort = self::createSortFromQueryParameters(
            Parameters::getQueryParametersFromQueryString($querySting, 'sort'), ['field0', 'field1']);

        $sortFields = $sort->getSortFields();

        $this->assertCount(2, $sortFields);
        $this->assertEquals('field0', $sortFields[0]->getPath());
        $this->assertEquals(SortField::ASCENDING_DIRECTION, $sortFields[0]->getDirection());
        $this->assertEquals('field1', $sortFields[1]->getPath());
        $this->assertEquals(SortField::DESCENDING_DIRECTION, $sortFields[1]->getDirection());
    }

    /**
     * @throws \Exception
     */
    public function testCreateFromQueryStringMultipleFieldsWithDefaults2()
    {
        $querySting = 'sort[sort-field0][path]=field0&sort[sort-field1][path]=field1';

        $sort = self::createSortFromQueryParameters(
            Parameters::getQueryParametersFromQueryString($querySting, 'sort'), ['field0', 'field1']);

        $sortFields = $sort->getSortFields();

        $this->assertCount(2, $sortFields);
        $this->assertEquals('field0', $sortFields[0]->getPath());
        $this->assertEquals(SortField::ASCENDING_DIRECTION, $sortFields[0]->getDirection());
        $this->assertEquals('field1', $sortFields[1]->getPath());
        $this->assertEquals(SortField::ASCENDING_DIRECTION, $sortFields[1]->getDirection());
    }

    public function testSortKeysUndefined()
    {
        $querySting = 'sort[sort-field0][path]=field0&sort[sort-field0][foo]=bar';

        try {
            self::createSortFromQueryParameters(
                Parameters::getQueryParametersFromQueryString($querySting, 'sort'), ['field0', 'field1']);
            $this->fail('Expected SortException was not thrown.');
        } catch (SortException $e) {
            $this->assertEquals(SortException::SORT_KEY_UNDEFINED, $e->getCode());
        }
    }

    public function testInvalidSortDirection()
    {
        $querySting = 'sort[sort-field0][path]=field0&sort[sort-field0][direction]=FOO';

        try {
            self::createSortFromQueryParameters(
                Parameters::getQueryParametersFromQueryString($querySting, 'sort'), ['field0', 'field1']);
            $this->fail('Expected SortException was not thrown.');
        } catch (SortException $e) {
            $this->assertEquals(SortException::INVALID_SORT_DIRECTION, $e->getCode());
        }
    }

    public function testAttributePathUndefined()
    {
        $querySting = 'sort[sort-field0][path]=field0&sort[sort-field0][direction]=DESC';

        try {
            self::createSortFromQueryParameters(
                Parameters::getQueryParametersFromQueryString($querySting, 'sort'), ['field1']);
            $this->fail('Expected SortException was not thrown.');
        } catch (SortException $e) {
            $this->assertEquals(SortException::ATTRIBUTE_PATH_UNDEFINED, $e->getCode());
        }
    }

    public function testAttributePathMissing()
    {
        $querySting = 'sort[sort-field0][direction]=DESC';

        try {
            self::createSortFromQueryParameters(
                Parameters::getQueryParametersFromQueryString($querySting, 'sort'), ['field0']);
            $this->fail('Expected SortException was not thrown.');
        } catch (SortException $e) {
            $this->assertEquals(SortException::ATTRIBUTE_PATH_MISSING, $e->getCode());
        }
    }

    /**
     * @throws SortException
     */
    private static function createSortFromQueryParameters(mixed $sortQueryParameters, array $definedAttributePaths): Sort
    {
        return FromQuerySortCreator::createSortFromQueryParameters(
            $sortQueryParameters,
            function (string $path) use ($definedAttributePaths): bool {
                return in_array($path, $definedAttributePaths, true);
            }
        );
    }
}
