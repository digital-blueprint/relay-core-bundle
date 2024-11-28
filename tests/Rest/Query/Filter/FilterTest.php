<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\Rest\Query\Filter;

use Dbp\Relay\CoreBundle\Rest\Query\Filter\Filter;
use Dbp\Relay\CoreBundle\Rest\Query\Filter\FilterException;
use Dbp\Relay\CoreBundle\Rest\Query\Filter\FilterTreeBuilder;
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
        $filter = FilterTreeBuilder::create()
            ->or()
                ->iContains('field_1', '1')
                ->equals('field_2', '2')
            ->end()
            ->or()
                ->iContains('field_3', '3')
                ->equals('field_4', '4')
            ->end()->createFilter();

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
        $this->assertEquals(OperatorType::I_CONTAINS_OPERATOR, $conditionNode[ConditionNode::OPERATOR_KEY]);

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
        $this->assertEquals(OperatorType::I_CONTAINS_OPERATOR, $conditionNode[ConditionNode::OPERATOR_KEY]);

        $conditionNode = $orNode[1];
        $this->assertIsArray($conditionNode);
        $this->assertEquals('4', $conditionNode[ConditionNode::VALUE_KEY]);
        $this->assertEquals('field_4', $conditionNode[ConditionNode::FIELD_KEY]);
        $this->assertEquals(OperatorType::EQUALS_OPERATOR, $conditionNode[ConditionNode::OPERATOR_KEY]);
    }

    /**
     * @throws FilterException
     * @throws \Exception
     */
    public function testCombineWith()
    {
        /** @var Filter */
        $filter = FilterTreeBuilder::create()
            ->or()
                ->iContains('field_1', '1')
                ->equals('field_2', '2')
            ->end()
            ->or()
                ->iContains('field_3', '3')
                ->equals('field_4', '4')
            ->end()->createFilter();

        /** @var Filter */
        $filter1 = FilterTreeBuilder::create()
            ->or()
                ->iContains('field_1', '1')
                ->equals('field_2', '2')
            ->end()->createFilter();

        /** @var Filter */
        $filter2 = FilterTreeBuilder::create()
            ->or()
                ->iContains('field_3', '3')
                ->equals('field_4', '4')
            ->end()->createFilter();

        $filter1->combineWith($filter2);

        $this->assertEquals($filter->toArray(), $filter1->toArray());
    }

    /**
     * @throws FilterException
     * @throws \Exception
     */
    public function testSimplify()
    {
        /** @var Filter */
        $filter = FilterTreeBuilder::create()
            ->and()
            ->iContains('field_1', '1')
            ->equals('field_2', '2')
            ->end()
            ->or()
            ->or()
            ->iContains('field_3', '3')
            ->not()->not()->equals('field_4', '4')->end()->end()
            ->end()
            ->end()->createFilter();

        /** @var Filter */
        $desiredResultFilter = FilterTreeBuilder::create()
            ->iContains('field_1', '1')
            ->equals('field_2', '2')
            ->or()
            ->iContains('field_3', '3')
            ->equals('field_4', '4')
            ->end()->createFilter();

        $filter->simplify();

        $this->assertEquals($desiredResultFilter->toArray(), $filter->toArray());
    }

    public function testEmptyFilter()
    {
        $filter = Filter::create();
        $this->assertTrue($filter->isEmpty());
        $this->assertTrue($filter->isValid());
    }

    /**
     * @throws FilterException
     */
    public function testEmptyFilterWithTreeBuilder()
    {
        $filter = FilterTreeBuilder::create()->createFilter();
        $this->assertTrue($filter->isEmpty());
        $this->assertTrue($filter->isValid());
    }

    /**
     * @throws FilterException
     */
    public function testFilterInvalid()
    {
        // missing children under 'or' node
        /** @var Filter */
        $filter = FilterTreeBuilder::create()->or()->end()->createFilter();

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
        $filter2 = FilterTreeBuilder::create()
            ->or()
                ->iContains('field_1', '1')
                ->equals('field_2', '2')
            ->end()->createFilter();

        $filter1->combineWith($filter2);

        $this->assertEquals($filter1->toArray(), $filter2->toArray());
    }

    /**
     * @throws FilterException
     * @throws \Exception
     */
    public function testCombineWithSecondEmpty()
    {
        /** @var Filter */
        $filter1 = FilterTreeBuilder::create()
            ->or()
            ->iContains('field_1', '1')
            ->equals('field_2', '2')
            ->end()->createFilter();

        $filter1OriginalArray = $filter1->toArray();

        $filter1->combineWith(Filter::create());

        $this->assertEquals($filter1OriginalArray, $filter1->toArray());
    }

    public function testConditionFieldEmptyException()
    {
        try {
            /** @var Filter */
            $filter1 = FilterTreeBuilder::create()
                ->iContains('', '1')
                ->end()->createFilter();
        } catch (FilterException $exception) {
            $this->assertEquals(FilterException::CONDITION_FIELD_EMPTY, $exception->getCode());
        }
    }
}
