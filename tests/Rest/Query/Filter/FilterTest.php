<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\Rest\Query\Filter;

use Dbp\Relay\CoreBundle\Rest\Query\Filter\Filter;
use Dbp\Relay\CoreBundle\Rest\Query\Filter\FromQueryFilterCreator;
use Dbp\Relay\CoreBundle\Rest\Query\Filter\Nodes\ConditionNode;
use Dbp\Relay\CoreBundle\Rest\Query\Filter\Nodes\OperatorType;
use PHPUnit\Framework\TestCase;

class FilterTest extends TestCase
{
    /**
     * @throws \Exception
     */
    public function testFilterToArray()
    {
        $filter = Filter::create();
        $filter->getRootNode()
            ->or()
                ->icontains('field_1', '1')
                ->equals('field_2', '2')
            ->end()
            ->or()
                ->contains('field_3', '3')
                ->iequals('field_4', '4')
            ->end();

        $this->assertInstanceOf(Filter::class, $filter);
        $this->assertTrue($filter->isValid());

        $filterArray = $filter->toArray();

        $andNode = $filterArray['and_0'];

        $orNode = $andNode['or_0'];
        $this->assertIsArray($orNode);

        $conditionNode = $orNode[0];
        $this->assertIsArray($conditionNode);
        $this->assertEquals('1', $conditionNode[ConditionNode::VALUE_KEY]);
        $this->assertEquals('field_1', $conditionNode[ConditionNode::FIELD_KEY]);
        $this->assertEquals(OperatorType::ICONTAINS_OPERATOR, $conditionNode[ConditionNode::OPERATOR_KEY]);

        $conditionNode = $orNode[1];
        $this->assertIsArray($conditionNode);
        $this->assertEquals('2', $conditionNode[ConditionNode::VALUE_KEY]);
        $this->assertEquals('field_2', $conditionNode[ConditionNode::FIELD_KEY]);
        $this->assertEquals(OperatorType::EQUALS_OPERATOR, $conditionNode[ConditionNode::OPERATOR_KEY]);

        $orNode = $andNode['or_1'];
        $this->assertIsArray($orNode);

        $conditionNode = $orNode[0];
        $this->assertIsArray($conditionNode);
        $this->assertEquals('3', $conditionNode[ConditionNode::VALUE_KEY]);
        $this->assertEquals('field_3', $conditionNode[ConditionNode::FIELD_KEY]);
        $this->assertEquals(OperatorType::CONTAINS_OPERATOR, $conditionNode[ConditionNode::OPERATOR_KEY]);

        $conditionNode = $orNode[1];
        $this->assertIsArray($conditionNode);
        $this->assertEquals('4', $conditionNode[ConditionNode::VALUE_KEY]);
        $this->assertEquals('field_4', $conditionNode[ConditionNode::FIELD_KEY]);
        $this->assertEquals(OperatorType::IEQUALS_OPERATOR, $conditionNode[ConditionNode::OPERATOR_KEY]);
    }

    public function testCombineWith()
    {
        /** @var Filter */
        $referenceFilter = Filter::create();
        $referenceFilter->getRootNode()
            ->or()
                ->icontains('field_1', '1')
                ->equals('field_2', '2')
            ->end()
            ->or()
                ->contains('field_3', '3')
                ->iequals('field_4', '4')
            ->end();

        /** @var Filter */
        $filter1 = Filter::create();
        $filter1->getRootNode()
            ->or()
                ->icontains('field_1', '1')
                ->equals('field_2', '2')
            ->end();

        /** @var Filter */
        $filter2 = Filter::create();
        $filter2->getRootNode()
            ->or()
                ->contains('field_3', '3')
                ->iequals('field_4', '4')
            ->end();

        $filter1->combineWith($filter2);

        $this->assertEquals($referenceFilter->toArray(), $filter1->toArray());
    }

    public function testSimplify()
    {
        /** @var Filter */
        $filter = Filter::create();
        $filter->getRootNode()
            ->and()
            ->icontains('field_1', '1')
            ->equals('field_2', '2')
            ->end()
            ->or()
            ->or()
            ->contains('field_3', '3')
            ->not()->not()->iequals('field_4', '4')->end()->end()
            ->end()
            ->end();

        /** @var Filter */
        $desiredResultFilter = Filter::create();
        $desiredResultFilter->getRootNode()
            ->icontains('field_1', '1')
            ->equals('field_2', '2')
            ->or()
            ->contains('field_3', '3')
            ->iequals('field_4', '4')
            ->end();

        $filter->simplify();

        $this->assertEquals($desiredResultFilter->toArray(), $filter->toArray());
    }

    public function testEmptyFilter()
    {
        $filter = Filter::create();
        $this->assertTrue($filter->isEmpty());
        $this->assertTrue($filter->isValid());
    }

    public function testFilterInvalid()
    {
        /** @var Filter */
        $filter = Filter::create();
        $filter->getRootNode()->or();

        $reason = null;
        $this->assertFalse($filter->isValid($reason));
        $this->assertNotEmpty($reason);
    }

    /**
     * @throws \Exception
     */
    public function testCombineWithFirstEmpty()
    {
        /** @var Filter */
        $filter1 = Filter::create();

        /** @var Filter */
        $filter2 = Filter::create();
        $filter2->getRootNode()
            ->or()
                ->icontains('field_1', '1')
                ->equals('field_2', '2')
            ->end();

        $filter1->combineWith($filter2);

        $this->assertEquals($filter1->toArray(), $filter2->toArray());
    }

    public function testCombineWithSecondEmpty()
    {
        /** @var Filter */
        $filter1 = Filter::create();
        $filter1->getRootNode()
            ->or()
            ->icontains('field_1', '1')
            ->equals('field_2', '2')
            ->end();

        $filter1OriginalArray = $filter1->toArray();

        $filter1->combineWith(Filter::create());

        $this->assertEquals($filter1OriginalArray, $filter1->toArray());
    }

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
}
