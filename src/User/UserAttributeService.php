<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\User;

use Dbp\Relay\CoreBundle\API\UserSessionInterface;

class UserAttributeService
{
    private UserAttributeMuxer $userAttributeMuxer;
    private UserSessionInterface $currentUserSession;

    public function __construct(UserSessionInterface $currentUserSession, UserAttributeMuxer $userAttributeMuxer)
    {
        $this->userAttributeMuxer = $userAttributeMuxer;
        $this->currentUserSession = $currentUserSession;
    }

    public function getCurrentUserIdentifier(): ?string
    {
        return $this->currentUserSession->getUserIdentifier();
    }

    public function isCurrentUserAuthenticated(): bool
    {
        return $this->currentUserSession->isAuthenticated();
    }

    /**
     * Gets a user attribute for the currently logged-in user.
     *
     * @param mixed|null $defaultValue The value to return if the user attribute is declared but not specified for the current user
     *
     * @throws UserAttributeException If the user attribute is undeclared
     */
    public function getCurrentUserAttribute(string $userAttributeName, mixed $defaultValue = null): mixed
    {
        return $this->getUserAttribute($this->getCurrentUserIdentifier(), $userAttributeName, $defaultValue);
    }

    /**
     * Gets a user attribute for a user.
     *
     * @param mixed|null $defaultValue The value to return if the user attribute is declared but not specified for the current user
     *
     * @throws UserAttributeException If the user attribute is undeclared
     */
    public function getUserAttribute(?string $userIdentifier, string $userAttributeName, mixed $defaultValue = null): mixed
    {
        return $this->userAttributeMuxer->getAttribute($userIdentifier, $userAttributeName, $defaultValue);
    }
}
