<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Authorization;

use Dbp\Relay\CoreBundle\API\UserSessionInterface;
use Dbp\Relay\CoreBundle\Exception\ApiError;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractAuthorizationService
{
    public const RIGHTS_CONFIG_ATTRIBUTE = AuthorizationExpressionChecker::RIGHTS_CONFIG_ATTRIBUTE;
    public const ATTRIBUTES_CONFIG_ATTRIBUTE = AuthorizationExpressionChecker::ATTRIBUTES_CONFIG_ATTRIBUTE;

    /** @var AuthorizationExpressionChecker */
    private $userAuthorizationChecker;

    /** @var AuthorizationUser */
    private $currentAuthorizationUser;

    private $config;

    /**
     * @required
     */
    public function _injectServices(UserSessionInterface $userSession, AuthorizationDataMuxer $mux)
    {
        $this->userAuthorizationChecker = new AuthorizationExpressionChecker($mux);
        $this->currentAuthorizationUser = new AuthorizationUser($userSession->getUserIdentifier(), $this->userAuthorizationChecker);
        $this->updateConfig();
    }

    public function setConfig(array $config)
    {
        $this->config = $config;
        $this->updateConfig();
    }

    private function updateConfig()
    {
        if ($this->userAuthorizationChecker !== null && $this->config !== null) {
            $this->userAuthorizationChecker->setConfig($this->config);
        }
    }

    /**
     * @param mixed $subject
     *
     * @throws ApiError
     */
    public function denyAccessUnlessIsGranted(string $rightName, $subject = null): void
    {
        if ($this->isGrantedInternal($rightName, $subject) === false) {
            throw new ApiError(Response::HTTP_FORBIDDEN, 'access denied. missing right '.$rightName);
        }
    }

    /**
     * @param mixed $subject
     */
    public function isGranted(string $expressionName, $subject = null): bool
    {
        return $this->isGrantedInternal($expressionName, $subject);
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

    private function getAttributeInternal(string $attributeName, $defaultValue = null)
    {
        $this->userAuthorizationChecker->init();

        return $this->userAuthorizationChecker->getAttribute($this->currentAuthorizationUser, $attributeName, $defaultValue);
    }

    /**
     * @throws AuthorizationException
     */
    private function isGrantedInternal(string $rightName, $subject = null): bool
    {
        $this->userAuthorizationChecker->init();

        return $this->userAuthorizationChecker->isGranted($this->currentAuthorizationUser, $rightName, $subject);
    }
}
