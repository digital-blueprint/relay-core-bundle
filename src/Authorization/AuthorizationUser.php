<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Authorization;

/**
 * Provides the user interface available within privilege expressions.
 */
class AuthorizationUser
{
    /** @var UserAuthorizationChecker */
    private $authorizationChecker;

    public function __construct(UserAuthorizationChecker $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    public function getIdentifier(): ?string
    {
        return $this->authorizationChecker->getCurrentUserIdentifier();
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
        return $this->authorizationChecker->getAttribute($this, $attributeName, $defaultValue);
    }

    /**
     * @param mixed $subject
     *
     * @throws AuthorizationException
     */
    public function isGranted(string $rightName, $subject = null): bool
    {
        return $this->authorizationChecker->isGranted($this, $rightName, $subject);
    }

    /**
     * @param mixed|null $defaultValue
     *
     * @return mixed|null
     *
     * @throws AuthorizationException
     */
    public function get(string $attributeName, $defaultValue = null)
    {
        return $this->authorizationChecker->getCustomAttribute($this, $attributeName, $defaultValue);
    }
}
