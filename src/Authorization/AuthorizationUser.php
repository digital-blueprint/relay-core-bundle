<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Authorization;

/**
 * Provides the user interface available within privilege expressions.
 */
class AuthorizationUser
{
    /** @var AuthorizationExpressionChecker */
    private $authorizationChecker;

    /**
     * @var string|null
     */
    private $identifier;

    public function __construct(?string $identifier, AuthorizationExpressionChecker $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
        $this->identifier = $identifier;
    }

    public function getIdentifier(): ?string
    {
        return $this->identifier;
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
