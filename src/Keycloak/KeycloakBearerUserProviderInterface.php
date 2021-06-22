<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\Keycloak;

use Symfony\Component\Security\Core\User\UserInterface;

interface KeycloakBearerUserProviderInterface
{
    public function loadUserByIdentifier(string $identifier): UserInterface;
}
