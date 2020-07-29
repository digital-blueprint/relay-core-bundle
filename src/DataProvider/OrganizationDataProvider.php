<?php

namespace DBP\API\CoreBundle\DataProvider;

use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use DBP\API\CoreBundle\Entity\Organization;
use App\Service\TUGOnlineApi;

final class OrganizationDataProvider implements ItemDataProviderInterface, RestrictedDataProviderInterface
{
    private $api;

    public function __construct(TUGOnlineApi $api)
    {
        $this->api = $api;
    }

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return Organization::class === $resourceClass;
    }

    /**
     * @param string $resourceClass
     * @param array|int|string $id
     * @param string|null $operationName
     * @param array $context
     * @return Organization|null
     * @throws \DBP\API\CoreBundle\Exception\ItemNotLoadedException
     */
    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = []): ?Organization
    {
        $filters = isset($context['filters']) ? $context['filters'] : [];
        $lang = isset($filters['lang']) ? $filters['lang'] : 'de';

        // special handling for F2135 (Zentralbibliothek Wasser)
        if ($id == "1226_1231-F2135") {
            $organization = new Organization();
            $organization->setIdentifier($id);
            $organization->setName($lang == "de" ? "Zentralbibliothek Wasser" : "Central library Water");
            $organization->setAlternateName("F2135");

            return $organization;
        }

        $organization = $this->api->getOrganizationById($id, $lang);

        return $organization;
    }
}
