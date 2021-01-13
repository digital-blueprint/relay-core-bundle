<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\DataProvider;

use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use DBP\API\CoreBundle\Entity\Organization;
use DBP\API\CoreBundle\Service\OrganizationProviderInterface;

final class OrganizationDataProvider implements ItemDataProviderInterface, RestrictedDataProviderInterface
{
    private $api;

    public function __construct(OrganizationProviderInterface $api)
    {
        $this->api = $api;
    }

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return Organization::class === $resourceClass;
    }

    /**
     * @param array|int|string $id
     *
     * @throws \DBP\API\CoreBundle\Exception\ItemNotLoadedException
     */
    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = []): ?Organization
    {
        assert(is_string($id));
        $filters = $context['filters'] ?? [];
        $lang = $filters['lang'] ?? 'de';
        $organization = $this->api->getOrganizationById($id, $lang);

        return $organization;
    }
}
