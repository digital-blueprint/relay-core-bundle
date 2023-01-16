<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Authorization;

use Dbp\Relay\CoreBundle\API\UserSessionInterface;
use Dbp\Relay\CoreBundle\Exception\ApiError;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractAuthorizationService
{
    private const AUTHORIZATION_ROOT_CONFIG_NODE = 'authorization';

    /** @var AuthorizationExpressionChecker */
    private $userAuthorizationChecker;

    /** @var AuthorizationUser */
    private $currentAuthorizationUser;

    /** @var array|null */
    private $config;

    /**
     * @required
     */
    public function _injectServices(UserSessionInterface $userSession, AuthorizationDataMuxer $mux)
    {
        $this->userAuthorizationChecker = new AuthorizationExpressionChecker($mux);
        $this->currentAuthorizationUser = new AuthorizationUser($userSession, $this->userAuthorizationChecker);
        $this->updateConfig();
    }

    public function setConfig(array $config)
    {
        $this->config = $config[self::AUTHORIZATION_ROOT_CONFIG_NODE] ?? [];
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
        return $this->userAuthorizationChecker->evalAttributeExpression($this->currentAuthorizationUser, $attributeName, $defaultValue);
    }

    /**
     * @throws AuthorizationException
     */
    private function isGrantedInternal(string $rightName, $subject = null): bool
    {
        return $this->userAuthorizationChecker->isGranted($this->currentAuthorizationUser, $rightName, $subject);
    }

    /**
     * Create the 'authorization' config node definition with the given right and attribute definitions.
     * A definition is an array of the following form:
     * [0 => <nameString>, 1 => <defaultExpressionString> (optional, default: 'false'), 2 => <infoString> (optional, default: 'null')].
     *
     * @param array[] $rights     the list of right definitions
     * @param array[] $attributes the list of attribute definitions
     */
    public static function getAuthorizationConfigNodeDefinition(array $rights = [], array $attributes = []): NodeDefinition
    {
        $treeBuilder = new TreeBuilder(self::AUTHORIZATION_ROOT_CONFIG_NODE);

        $rightsNodeChildBuilder = $treeBuilder->getRootNode()->children()->arrayNode(AuthorizationExpressionChecker::RIGHTS_CONFIG_NODE)
            ->addDefaultsIfNotSet()
            ->children();
        foreach ($rights as $right) {
            $rightsNodeChildBuilder->scalarNode($right[0])
                ->defaultValue($right[1] ?? 'false')
                ->info($right[2] ?? '')
                ->end();
        }

        $attributesNodeChildBuilder = $treeBuilder->getRootNode()->children()->arrayNode(AuthorizationExpressionChecker::ATTRIBUTES_CONFIG_NODE)
            ->addDefaultsIfNotSet()
            ->children();
        foreach ($attributes as $attribute) {
            $attributesNodeChildBuilder->scalarNode($attribute[0])
                ->defaultValue($attribute[1] ?? 'null')
                ->info($attribute[2] ?? '')
                ->end();
        }

        return $treeBuilder->getRootNode();
    }

    public static function createConfig(array $rightExpressions = [], array $attributeExpressions = []): array
    {
        return [
            AbstractAuthorizationService::AUTHORIZATION_ROOT_CONFIG_NODE => [
                AuthorizationExpressionChecker::RIGHTS_CONFIG_NODE => $rightExpressions,
                AuthorizationExpressionChecker::ATTRIBUTES_CONFIG_NODE => $attributeExpressions,
            ],
        ];
    }
}
