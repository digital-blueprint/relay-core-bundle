<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\Service\CampusOnline;

use DBP\API\CoreBundle\Entity\Organization;
use DBP\API\CoreBundle\Entity\Person;
use DBP\API\CoreBundle\Service\OrganizationProviderInterface;

class CampusOnlineOrgProvider implements OrganizationProviderInterface
{
    private $orgApi;

    public function __construct(OrganizationApi $orgApi)
    {
        $this->orgApi = $orgApi;
    }

    public function getOrganizationById(string $identifier, string $lang): Organization
    {
        return $this->orgApi->getOrganizationById($identifier, $lang);
    }

    public function getOrganizationsByPerson(Person $person, string $context, string $lang): array
    {
        return $this->orgApi->getOrganizationsByPerson($person, $context, $lang);
    }
}
