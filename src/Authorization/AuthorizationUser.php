<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Authorization;

/**
 * Provides the user interface available within privilege expressions.
 */
class AuthorizationUser implements AuthorizationUserInterface
{
    /** @var UserAuthorizationChecker */
    private $authorizationChecker;

    public function __construct(UserAuthorizationChecker $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    public function getIdentifier(): string
    {
        return $this->authorizationChecker->getCurrentUserIdentifier();
    }

    public function hasRole(string $roleName): bool
    {
        return $this->authorizationChecker->hasRole($this, true, $roleName);
    }

    public function getAttribute(string $attributeName, $defaultValue = null)
    {
        return $this->authorizationChecker->getAttribute($this, true, $attributeName, $defaultValue);
    }

    /**
     * @throws AuthorizationException
     */
    public function hasPrivilege(string $privilegeName, $subject): bool
    {
        return $this->authorizationChecker->hasPrivilege($this, $privilegeName, $subject);
    }
}
