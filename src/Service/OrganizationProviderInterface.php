<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\Service;

use DBP\API\CoreBundle\Entity\Organization;
use DBP\API\CoreBundle\Entity\Person;

interface OrganizationProviderInterface
{
    public function getOrganizationById(string $identifier, string $lang): Organization;

    /**
     * @return Organization[]
     */
    public function getOrganizationsByPerson(Person $person, string $context, string $lang): array;

    /**
     * @return Organization[]
     */
    public function getOrganizations(string $lang): array;
}
