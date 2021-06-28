<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\DataProvider;

use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use DBP\API\CoreBundle\API\OrganizationProviderInterface;
use DBP\API\CoreBundle\Entity\Organization;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class OrganizationDataProvider extends AbstractController implements ItemDataProviderInterface, RestrictedDataProviderInterface
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
     * @throws \DBP\API\CoreBundle\Exception\ItemNotLoadedException
     */
    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = []): ?Organization
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        assert(is_string($id));
        $filters = $context['filters'] ?? [];
        $lang = $filters['lang'] ?? 'de';
        $organization = $this->api->getOrganizationById($id, $lang);

        return $organization;
    }
}
