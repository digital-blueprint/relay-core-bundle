<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Authorization;

use Dbp\Relay\CoreBundle\API\UserSessionInterface;

/**
 * Provides the user interface available within privilege expressions.
 */
class AuthorizationUser
{
    /** @var AuthorizationExpressionChecker */
    private $authorizationChecker;

    /** @var UserSessionInterface */
    private $userSession;

    public function __construct(UserSessionInterface $userSession, AuthorizationExpressionChecker $authorizationChecker)
    {
        $this->userSession = $userSession;
        $this->authorizationChecker = $authorizationChecker;
    }

    public function getIdentifier(): ?string
    {
        return $this->userSession->getUserIdentifier();
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
        return $this->authorizationChecker->evalAttributeExpression($this, $attributeName, $defaultValue);
    }

    /**
     * @param mixed $resource
     *
     * @throws AuthorizationException
     */
    public function isGranted(string $policyName, $resource = null): bool
    {
        return $this->authorizationChecker->isGranted($this, $policyName, $resource);
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
        return $this->authorizationChecker->getUserAttribute($this, $attributeName, $defaultValue);
    }
}
