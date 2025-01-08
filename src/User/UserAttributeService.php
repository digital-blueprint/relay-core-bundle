<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\User;

use Dbp\Relay\CoreBundle\API\UserSessionInterface;

/**
 * @internal
 */
class UserAttributeService
{
    public function __construct(
        private readonly UserSessionInterface $currentUserSession,
        private readonly UserAttributeMuxer $userAttributeMuxer)
    {
    }

    public function getCurrentUserIdentifier(): ?string
    {
        if (!$this->currentUserSession->isAuthenticated()) {
            return null;
        }

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
        return $this->userAttributeMuxer->getAttribute($this->getCurrentUserIdentifier(), $userAttributeName, $defaultValue);
    }
}
