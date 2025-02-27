<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\Rest\Query\Filter;

use Dbp\Relay\CoreBundle\Rest\Query\Filter\FilterException;
use Dbp\Relay\CoreBundle\Rest\Query\Filter\FilterTreeBuilder;
use Dbp\Relay\CoreBundle\Rest\Query\Filter\FromQueryFilterCreator;
use Dbp\Relay\CoreBundle\Rest\Query\Parameters;
use PHPUnit\Framework\TestCase;

class CreateFilterFromQueryTest extends TestCase
{
    /**
     * @throws \Exception
     */
    public function testCreateFromQueryParameters()
    {
        $queryParameters = ['foo' => ['condition' => [
            'path' => 'field0',
            'operator' => 'I_CONTAINS',
            'value' => 'value0',
        ]]];

        $filter = FromQueryFilterCreator::createFilterFromQueryParameters($queryParameters, ['field0']);

        $expectedFilter = FilterTreeBuilder::create()->iContains('field0', 'value0')->createFilter();

        $this->assertEquals($expectedFilter->toArray(), $filter->toArray());
    }

    public function testGetQueryParametersFromQueryString()
    {
        $querySting = 'filter[foo][condition][path]=field0&filter[foo][condition][operator]=I_CONTAINS&filter[foo][condition][value]=value0';

        $filterParameters =
            Parameters::getQueryParametersFromQueryString($querySting, 'filter');

        $queryParameters['foo'] = ['condition' => [
            'path' => 'field0',
            'operator' => 'I_CONTAINS',
            'value' => 'value0',
        ]];

        $this->assertEquals($queryParameters, $filterParameters);
    }

    /**
     * @throws \Exception
     */
    public function testCreateFromQueryString()
    {
        $querySting = 'filter[foo][condition][path]=field0&filter[foo][condition][operator]=I_CONTAINS&filter[foo][condition][value]=value0';

        $usedAttributePaths = [];
        $filter = FromQueryFilterCreator::createFilterFromQueryParameters(
            Parameters::getQueryParametersFromQueryString($querySting, 'filter'), ['field0'], $usedAttributePaths);
        $this->assertEquals(['field0'], $usedAttributePaths);

        $expectedFilter = FilterTreeBuilder::create()->iContains('field0', 'value0')->createFilter();

        $this->assertEquals($expectedFilter->toArray(), $filter->toArray());
    }

    /**
     * @throws \Exception
     */
    public function testAndGroup()
    {
        $querySting = 'filter[test_group][group][conjunction]=AND&filter[foo][condition][path]=field0&filter[foo][condition][operator]=I_CONTAINS&filter[foo][condition][value]=value0&&filter[foo][condition][memberOf]=test_group&filter[bar][condition][path]=field1&filter[bar][condition][operator]=EQUALS&filter[bar][condition][value]=value1&&filter[bar][condition][memberOf]=test_group';

        $usedAttributePaths = [];
        $filter = FromQueryFilterCreator::createFilterFromQueryParameters(
            Parameters::getQueryParametersFromQueryString($querySting, 'filter'), ['field0', 'field1'], $usedAttributePaths);
        $this->assertEquals(['field0', 'field1'], $usedAttributePaths);

        $expectedFilter = FilterTreeBuilder::create()
            ->and()
            ->iContains('field0', 'value0')
            ->equals('field1', 'value1')
            ->end()
            ->createFilter();

        $this->assertEquals($expectedFilter->toArray(), $filter->toArray());
    }

    /**
     * @throws \Exception
     */
    public function testOrGroup()
    {
        $querySting = 'filter[test_group][group][conjunction]=OR&filter[foo][condition][path]=field0&filter[foo][condition][operator]=I_CONTAINS&filter[foo][condition][value]=value0&&filter[foo][condition][memberOf]=test_group&filter[bar][condition][path]=field1&filter[bar][condition][operator]=EQUALS&filter[bar][condition][value]=value1&&filter[bar][condition][memberOf]=test_group';

        $usedAttributePaths = [];
        $filter = FromQueryFilterCreator::createFilterFromQueryParameters(
            Parameters::getQueryParametersFromQueryString($querySting, 'filter'), ['field0', 'field1'], $usedAttributePaths);
        $this->assertEquals(['field0', 'field1'], $usedAttributePaths);

        $expectedFilter = FilterTreeBuilder::create()
            ->or()
            ->iContains('field0', 'value0')
            ->equals('field1', 'value1')
            ->end()
            ->createFilter();

        $this->assertEquals($expectedFilter->toArray(), $filter->toArray());
    }

    /**
     * @throws \Exception
     */
    public function testNotAndGroup()
    {
        $querySting = 'filter[test_group][group][conjunction]=NOT_AND&filter[foo][condition][path]=field0&filter[foo][condition][operator]=I_CONTAINS&filter[foo][condition][value]=value0&&filter[foo][condition][memberOf]=test_group&filter[bar][condition][path]=field1&filter[bar][condition][operator]=EQUALS&filter[bar][condition][value]=value1&&filter[bar][condition][memberOf]=test_group';

        $usedAttributePaths = [];
        $filter = FromQueryFilterCreator::createFilterFromQueryParameters(
            Parameters::getQueryParametersFromQueryString($querySting, 'filter'), ['field0', 'field1'], $usedAttributePaths);
        $this->assertEquals(['field0', 'field1'], $usedAttributePaths);

        $expectedFilter = FilterTreeBuilder::create()
            ->not()
            ->and()
            ->iContains('field0', 'value0')
            ->equals('field1', 'value1')
            ->end()
            ->end()
            ->createFilter();

        $this->assertEquals($expectedFilter->toArray(), $filter->toArray());
    }

    /**
     * @throws \Exception
     */
    public function testNotOrGroup()
    {
        $querySting = 'filter[test_group][group][conjunction]=NOT_OR&filter[foo][condition][path]=field0&filter[foo][condition][operator]=I_CONTAINS&filter[foo][condition][value]=value0&&filter[foo][condition][memberOf]=test_group&filter[bar][condition][path]=field1&filter[bar][condition][operator]=EQUALS&filter[bar][condition][value]=value1&&filter[bar][condition][memberOf]=test_group';

        $usedAttributePaths = [];
        $filter = FromQueryFilterCreator::createFilterFromQueryParameters(
            Parameters::getQueryParametersFromQueryString($querySting, 'filter'), ['field0', 'field1'], $usedAttributePaths);
        $this->assertEquals(['field0', 'field1'], $usedAttributePaths);

        $expectedFilter = FilterTreeBuilder::create()
            ->not()
            ->or()
            ->iContains('field0', 'value0')
            ->equals('field1', 'value1')
            ->end()
            ->end()
            ->createFilter();

        $this->assertEquals($expectedFilter->toArray(), $filter->toArray());
    }

    /**
     * @throws \Exception
     */
    public function testConditionDefaultOperatorEquals()
    {
        $querySting = 'filter[foo][condition][path]=field0&filter[foo][condition][value]=value0';

        $filter = FromQueryFilterCreator::createFilterFromQueryParameters(
            Parameters::getQueryParametersFromQueryString($querySting, 'filter'), ['field0']);

        $expectedFilter = FilterTreeBuilder::create()->equals('field0', 'value0')->createFilter();

        $this->assertEquals($expectedFilter->toArray(), $filter->toArray());
    }

    /**
     * @throws \Exception
     */
    public function testCreateFromShortcut()
    {
        $querySting = 'filter[field0][value]=value0';

        $filter = FromQueryFilterCreator::createFilterFromQueryParameters(
            Parameters::getQueryParametersFromQueryString($querySting, 'filter'), ['field0']);

        $expectedFilter = FilterTreeBuilder::create()->equals('field0', 'value0')->createFilter();

        $this->assertEquals($expectedFilter->toArray(), $filter->toArray());
    }

    /**
     * @throws \Exception
     */
    public function testCreateFromUltraShortcut()
    {
        $querySting = 'filter[field0]=value0';

        $filter = FromQueryFilterCreator::createFilterFromQueryParameters(
            Parameters::getQueryParametersFromQueryString($querySting, 'filter'), ['field0']);

        $expectedFilter = FilterTreeBuilder::create()->equals('field0', 'value0')->createFilter();

        $this->assertEquals($expectedFilter->toArray(), $filter->toArray());
    }

    /**
     * @throws \Exception
     */
    public function testAttributePathUndefinedException()
    {
        $querySting = 'filter[field0]=value0';

        try {
            FromQueryFilterCreator::createFilterFromQueryParameters(
                Parameters::getQueryParametersFromQueryString($querySting, 'filter'), []);
        } catch (FilterException $exception) {
            $this->assertEquals(FilterException::ATTRIBUTE_PATH_UNDEFINED, $exception->getCode());
        }
    }

    /**
     * @throws \Exception
     */
    public function testConditionPathMissingException()
    {
        $querySting = 'filter[foo][condition][value]=1';

        try {
            FromQueryFilterCreator::createFilterFromQueryParameters(
                Parameters::getQueryParametersFromQueryString($querySting, 'filter'), ['field0']);
        } catch (FilterException $exception) {
            $this->assertEquals(FilterException::CONDITION_PATH_MISSING, $exception->getCode());
        }
    }

    /**
     * @throws \Exception
     */
    public function testConditionValueMissingException()
    {
        $querySting = 'filter[foo][condition][path]=field0';

        try {
            FromQueryFilterCreator::createFilterFromQueryParameters(
                Parameters::getQueryParametersFromQueryString($querySting, 'filter'), ['field0']);
        } catch (FilterException $exception) {
            $this->assertEquals(FilterException::CONDITION_VALUE_ERROR, $exception->getCode());
        }
    }

    public function testConditionIsNullOperator()
    {
        $querySting = 'filter[foo][condition][path]=field0&filter[foo][condition][operator]=IS_NULL';

        $filter = FromQueryFilterCreator::createFilterFromQueryParameters(
            Parameters::getQueryParametersFromQueryString($querySting, 'filter'), ['field0']);

        $expectedFilter = FilterTreeBuilder::create()->isNull('field0')->createFilter();

        $this->assertEquals($expectedFilter->toArray(), $filter->toArray());
    }

    /**
     * @throws \Exception
     */
    public function testConditionNullOperatorWithValueException()
    {
        $querySting = 'filter[foo][condition][path]=field0&filter[foo][condition][operator]=IS_NULL&filter[foo][condition][value]=value0';

        try {
            FromQueryFilterCreator::createFilterFromQueryParameters(
                Parameters::getQueryParametersFromQueryString($querySting, 'filter'), ['field0']);
        } catch (FilterException $exception) {
            $this->assertEquals(FilterException::CONDITION_VALUE_ERROR, $exception->getCode());
        }
    }

    /**
     * @throws \Exception
     */
    public function testConditionInArrayOperator()
    {
        $querySting = 'filter[foo][condition][path]=field0&filter[foo][condition][operator]=IN&filter[foo][condition][value][0]=value0&filter[foo][condition][value][1]=value1';

        $filter = FromQueryFilterCreator::createFilterFromQueryParameters(
            Parameters::getQueryParametersFromQueryString($querySting, 'filter'), ['field0']);

        $expectedFilter = FilterTreeBuilder::create()->inArray('field0', ['value0', 'value1'])->createFilter();

        $this->assertEquals($expectedFilter->toArray(), $filter->toArray());
    }

    /**
     * @throws \Exception
     */
    public function testConditionInArrayOperatorWithNonArrayValueException()
    {
        $querySting = 'filter[foo][condition][path]=field0&filter[foo][condition][operator]=IN&filter[foo][condition][value]=value0';

        try {
            FromQueryFilterCreator::createFilterFromQueryParameters(
                Parameters::getQueryParametersFromQueryString($querySting, 'filter'), ['field0']);
        } catch (FilterException $exception) {
            $this->assertEquals(FilterException::CONDITION_VALUE_ERROR, $exception->getCode());
        }
    }

    /**
     * @throws \Exception
     */
    public function testFilterItemInvalid()
    {
        $querySting = 'filter[foo][bar]=1';

        try {
            FromQueryFilterCreator::createFilterFromQueryParameters(
                Parameters::getQueryParametersFromQueryString($querySting, 'filter'), ['field0']);
        } catch (FilterException $exception) {
            $this->assertEquals(FilterException::FILTER_ITEM_INVALID, $exception->getCode());
        }
    }

    /**
     * @throws \Exception
     */
    public function testConditionOperatorUndefinedException()
    {
        $querySting = 'filter[foo][condition][path]=field0&filter[foo][condition][operator]=foobar&filter[foo][condition][value]=value0';

        try {
            FromQueryFilterCreator::createFilterFromQueryParameters(
                Parameters::getQueryParametersFromQueryString($querySting, 'filter'), ['field0']);
        } catch (FilterException $exception) {
            $this->assertEquals(FilterException::CONDITION_OPERATOR_UNDEFINED, $exception->getCode());
        }
    }

    /**
     * @throws \Exception
     */
    public function testReservedFilterItemId()
    {
        $querySting = 'filter[@root][condition][path]=field0&filter[@root][condition][operator]=EQ&filter[@root][condition][value]=value0';

        try {
            FromQueryFilterCreator::createFilterFromQueryParameters(
                Parameters::getQueryParametersFromQueryString($querySting, 'filter'), ['field0']);
        } catch (FilterException $exception) {
            $this->assertEquals(FilterException::RESERVED_FILTER_ITEM_ID, $exception->getCode());
        }
    }

    /**
     * @throws \Exception
     */
    public function testConjunctionUndefined()
    {
        $querySting = 'filter[or_group][group][conjunction]=ORISH&filter[foo][condition][path]=field0&filter[foo][condition][operator]=EQ&filter[foo][condition][value]=value0&filter[bar][condition][path]=field0&filter[bar][condition][operator]=EQ&filter[bar][condition][value]=value1';

        try {
            FromQueryFilterCreator::createFilterFromQueryParameters(
                Parameters::getQueryParametersFromQueryString($querySting, 'filter'), ['field0']);
        } catch (FilterException $exception) {
            $this->assertEquals(FilterException::CONJUNCTION_UNDEFINED, $exception->getCode());
        }
    }
}
