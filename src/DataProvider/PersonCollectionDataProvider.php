<?php

namespace DBP\API\CoreBundle\DataProvider;

use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use DBP\API\CoreBundle\Helpers\ArrayFullPaginator;
use DBP\API\CoreBundle\Entity\Person;
use DBP\API\CoreBundle\Service\PersonProviderInterface;

final class PersonCollectionDataProvider implements CollectionDataProviderInterface, RestrictedDataProviderInterface
{
    const ITEMS_PER_PAGE = 250;

    private $api;

    public function __construct(PersonProviderInterface $api)
    {
        $this->api = $api;
    }

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return Person::class === $resourceClass;
    }

    /**
     * @param string $resourceClass
     * @param string|null $operationName
     * @param array $context
     * @return ArrayFullPaginator
     * @throws \DBP\API\CoreBundle\Exception\ItemNotLoadedException
     */
    public function getCollection(string $resourceClass, string $operationName = null, array $context = []): ArrayFullPaginator
    {
        $perPage = self::ITEMS_PER_PAGE;
        $page = 1;
        $api = $this->api;
        $filters = isset($context['filters']) ? $context['filters'] : [];

        if (isset($context['filters']['page']))
        {
            $page = (int) $context['filters']['page'];
        }

        if (isset($context['filters']['perPage']))
        {
            $perPage = (int) $context['filters']['perPage'];
        }

        $persons = $api->getPersons($filters);

        // TODO: do pagination via API
        $pagination = new ArrayFullPaginator($persons, $page, $perPage);

        return $pagination;
    }
}
