<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Authorization;

use Dbp\Relay\CoreBundle\API\UserSessionInterface;
use Dbp\Relay\CoreBundle\Exception\ApiError;
use Symfony\Component\HttpFoundation\Response;

final class Authorization
{
    /** @var AuthorizationExpressionChecker */
    private $userAuthorizationChecker;

    /** @var AuthorizationUser */
    private $currentAuthorizationUser;

    public function __construct(UserSessionInterface $userSession, AuthorizationDataMuxer $mux, array $roleExpressions, array $attributeExpressions)
    {
        $this->userAuthorizationChecker = new AuthorizationExpressionChecker($mux);
        $this->currentAuthorizationUser = new AuthorizationUser($userSession, $this->userAuthorizationChecker);
        $this->userAuthorizationChecker->setExpressions($roleExpressions, $attributeExpressions);
    }

    /**
     * @param mixed $resource
     *
     * @throws ApiError
     */
    public function denyAccessUnlessIsGranted(string $policyName, $resource = null, string $resourceAlias = null): void
    {
        if ($this->isGrantedInternal($policyName, $resource, $resourceAlias) === false) {
            throw new ApiError(Response::HTTP_FORBIDDEN, 'access denied. policy failed: '.$policyName);
        }
    }

    /**
     * @param mixed $resource
     */
    public function isGranted(string $expressionName, $resource = null, string $resourceAlias = null): bool
    {
        return $this->isGrantedInternal($expressionName, $resource, $resourceAlias);
    }

    /**
     * @param mixed|null $defaultValue
     *
     * @return mixed|null
     */
    public function getAttribute(string $attributeName, $defaultValue = null)
    {
        return $this->getAttributeInternal($attributeName, $defaultValue);
    }

    /**
     * @throws AuthorizationException
     */
    private function getAttributeInternal(string $attributeName, $defaultValue = null)
    {
        return $this->userAuthorizationChecker->evalAttributeExpression($this->currentAuthorizationUser, $attributeName, $defaultValue);
    }

    /**
     * @throws AuthorizationException
     */
    private function isGrantedInternal(string $policyName, $resource, string $resourceAlias = null): bool
    {
        return $this->userAuthorizationChecker->isGranted($this->currentAuthorizationUser, $policyName, $resource, $resourceAlias);
    }
}
