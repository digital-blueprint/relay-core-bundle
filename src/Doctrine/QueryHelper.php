<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Doctrine;

use Dbp\Relay\CoreBundle\Rest\Query\Filter\Filter;
use Dbp\Relay\CoreBundle\Rest\Query\Filter\Nodes\ConditionNode as ConditionFilterNode;
use Dbp\Relay\CoreBundle\Rest\Query\Filter\Nodes\LogicalNode as LogicalFilterNode;
use Dbp\Relay\CoreBundle\Rest\Query\Filter\Nodes\Node as FilterNode;
use Dbp\Relay\CoreBundle\Rest\Query\Filter\Nodes\NodeType as FilterNodeType;
use Dbp\Relay\CoreBundle\Rest\Query\Filter\Nodes\OperatorType as FilterOperatorType;
use Dbp\Relay\CoreBundle\Rest\Query\Pagination\Pagination;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\QueryBuilder;

class QueryHelper
{
    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public static function saveEntity(object $entity, EntityManager $entityManager): void
    {
        $entityManager->persist($entity);
        $entityManager->flush();
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public static function removeEntity(object $entity, EntityManager $entityManager): void
    {
        $entityManager->remove($entity);
        $entityManager->flush();
    }

    public static function tryGetEntityById(string $identifier, string $entityClassName, EntityManager $entityManager): ?object
    {
        return $entityManager->getRepository($entityClassName)
            ->findOneBy(['identifier' => $identifier]);
    }

    /**
     * @throws \Exception
     */
    public static function getEntities(string $entityClassName, EntityManager $entityManager,
        int $currentPageNumber = 1, int $maxNumItemsPerPage = 30, ?Filter $filter = null): array
    {
        $ENTITY_ALIAS = 'e';
        $queryBuilder = $entityManager->createQueryBuilder()
            ->select($ENTITY_ALIAS)
            ->from($entityClassName, $ENTITY_ALIAS);

        if ($filter !== null && !$filter->isEmpty()) {
            self::addFilter($queryBuilder, $ENTITY_ALIAS, $filter);
        }

        return $queryBuilder
            ->getQuery()
            ->setFirstResult(Pagination::getFirstItemIndex($currentPageNumber, $maxNumItemsPerPage))
            ->setMaxResults($maxNumItemsPerPage)
            ->getResult();
    }

    /**
     * @throws \Exception
     */
    public static function addFilter(QueryBuilder $queryBuilder, string $entityAlias, Filter $filter): QueryBuilder
    {
        return $queryBuilder->where(self::createExpression($queryBuilder, $entityAlias, $filter->getRootNode()));
    }

    /**
     * @return \Stringable|string
     *
     * @throws \Exception
     */
    private static function createExpression(QueryBuilder $queryBuilder, string $entityAlias, FilterNode $filterNode): mixed
    {
        if ($filterNode instanceof LogicalFilterNode) {
            switch ($filterNode->getNodeType()) {
                case FilterNodeType::AND:
                case FilterNodeType::OR:
                    if (count($filterNode->getChildren()) === 1) {
                        return self::createExpression($queryBuilder, $entityAlias, $filterNode->getChildren()[0]);
                    }
                    $logicalClause = $filterNode->getNodeType() === FilterNodeType::AND ?
                        $queryBuilder->expr()->andX() : $queryBuilder->expr()->orX();
                    foreach ($filterNode->getChildren() as $childNodeDefinition) {
                        $logicalClause->add(self::createExpression($queryBuilder, $entityAlias, $childNodeDefinition));
                    }

                    return $logicalClause;

                case FilterNodeType::NOT:
                    return $queryBuilder->expr()->not(
                        self::createExpression($queryBuilder, $entityAlias, $filterNode->getChildren()[0]));

                default:
                    throw new \Exception('invalid filter node type: '.$filterNode->getNodeType());
            }
        } elseif ($filterNode instanceof ConditionFilterNode) {
            $field = $filterNode->getField();
            $value = $filterNode->getValue();
            switch ($filterNode->getOperator()) {
                case FilterOperatorType::I_CONTAINS_OPERATOR:
                    return $queryBuilder->expr()->like($entityAlias.'.'.$field,
                        $queryBuilder->expr()->literal('%'.$value.'%'));
                case FilterOperatorType::EQUALS_OPERATOR: // TODO: case-sensitivity post-precessing required
                    return $queryBuilder->expr()->eq($entityAlias.'.'.$field,
                        $queryBuilder->expr()->literal($value));
                case FilterOperatorType::I_STARTS_WITH_OPERATOR:
                    return $queryBuilder->expr()->like($entityAlias.'.'.$field,
                        $queryBuilder->expr()->literal($value.'%'));
                case FilterOperatorType::I_ENDS_WITH_OPERATOR:
                    return $queryBuilder->expr()->like($entityAlias.'.'.$field,
                        $queryBuilder->expr()->literal('%'.$value));
                case FilterOperatorType::GREATER_THAN_OR_EQUAL_OPERATOR:
                    return $queryBuilder->expr()->gte($entityAlias.'.'.$field,
                        $queryBuilder->expr()->literal($value));
                case FilterOperatorType::LESS_THAN_OR_EQUAL_OPERATOR:
                    return $queryBuilder->expr()->lte($entityAlias.'.'.$field,
                        $queryBuilder->expr()->literal($value));
                case FilterOperatorType::IN_ARRAY_OPERATOR:
                    if (!is_array($value) || empty($value)) {
                        throw new \Exception('filter condition operator "'.FilterOperatorType::IN_ARRAY_OPERATOR.
                            '" requires non-empty array value');
                    }

                    return $queryBuilder->expr()->in($entityAlias.'.'.$field, $value);
                case FilterOperatorType::IS_NULL_OPERATOR:
                    return $queryBuilder->expr()->isNull($entityAlias.'.'.$field);
                default:
                    throw new \Exception('unsupported filter condition operator: '.$filterNode->getOperator());
            }
        }

        throw new \Exception('invalid filter node instance: '.get_class($filterNode));
    }
}
