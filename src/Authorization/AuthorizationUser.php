<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Authorization;

use Dbp\Relay\CoreBundle\User\UserAttributeException;

/**
 * @internal
 *
 * Provides the user interface available within privilege expressions
 */
class AuthorizationUser
{
    /** @var AbstractAuthorizationService */
    private $authorizationService;

    public function __construct(AbstractAuthorizationService $authorizationService)
    {
        $this->authorizationService = $authorizationService;
    }

    /**
     * @return string|null The current authenticated user's identifier or null if the user is not authenticated or is a system
     */
    public function getIdentifier(): ?string
    {
        return $this->authorizationService->getUserIdentifier();
    }

    /**
     * @return bool Indicates whether the current user is authenticated
     */
    public function isAuthenticated(): bool
    {
        return $this->authorizationService->isAuthenticated();
    }

    /**
     * @param mixed $defaultValue
     *
     * @return mixed|null
     *
     * @throws AuthorizationException
     */
    public function getAttribute(string $attributeName, $defaultValue = null)
    {
        return $this->authorizationService->getAttribute($attributeName, $defaultValue);
    }

    /**
     * @param mixed $resource
     *
     * @throws AuthorizationException
     */
    public function isGranted(string $policyName, $resource = null): bool
    {
        return $this->authorizationService->isGranted($policyName, $resource);
    }

    /**
     * @param mixed|null $defaultValue
     *
     * @return mixed|null
     *
     * @throws AuthorizationException
     * @throws UserAttributeException
     */
    public function get(string $attributeName, $defaultValue = null)
    {
        return $this->authorizationService->getUserAttribute($attributeName, $defaultValue);
    }
}
