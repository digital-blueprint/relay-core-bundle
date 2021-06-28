<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\DataProvider;

use ApiPlatform\Core\DataProvider\ContextAwareCollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use DBP\API\CoreBundle\API\OrganizationProviderInterface;
use DBP\API\CoreBundle\Entity\Organization;
use DBP\API\CoreBundle\Helpers\ArrayFullPaginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class OrganizationCollectionDataProvider extends AbstractController implements ContextAwareCollectionDataProviderInterface, RestrictedDataProviderInterface
{
    public const ITEMS_PER_PAGE = 250;
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
    public function getCollection(string $resourceClass, string $operationName = null, array $context = []): ArrayFullPaginator
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $filters = $context['filters'] ?? [];
        $lang = $filters['lang'] ?? 'de';

        $perPage = self::ITEMS_PER_PAGE;
        $page = 1;

        if (isset($context['filters']['page'])) {
            $page = (int) $context['filters']['page'];
        }

        if (isset($context['filters']['perPage'])) {
            $perPage = (int) $context['filters']['perPage'];
        }
        $orgs = $this->api->getOrganizations($lang);

        return new ArrayFullPaginator($orgs, $page, $perPage);
    }
}
