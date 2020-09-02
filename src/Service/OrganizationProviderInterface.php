<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\Service;

use DBP\API\CoreBundle\Entity\Organization;

interface OrganizationProviderInterface
{
    public function getOrganizationById(string $identifier, string $lang): Organization;
}
