<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Rest\Query\Filter;

use Dbp\Relay\CoreBundle\Authorization\AbstractAuthorizationService;
use Dbp\Relay\CoreBundle\Exception\ApiError;
use Dbp\Relay\CoreBundle\Rest\ErrorIds;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @internal
 */
class PreparedFilters
{
    private const ROOT_CONFIG_NODE = 'prepared_filters';
    private const IDENTIFIER_CONFIG_NODE = 'id';
    private const FILTER_CONFIG_NODE = 'filter';
    private const USE_POLICY_CONFIG_NODE = 'use_policy';
    private const FORCE_USE_POLICY_CONFIG_NODE = 'force_use_policy';
    private const FORCE_USE_FOR_USERS_NODE = 'force_use_for_users';

    private const FILTER_CONFIG_KEY = 'filter';

    /** @var array<int, array<string, mixed>> */
    private array $config = [];

    public static function getConfigNodeDefinition(): NodeDefinition
    {
        $treeBuilder = new TreeBuilder(self::ROOT_CONFIG_NODE);

        return $treeBuilder->getRootNode()
            ->arrayPrototype()
                ->children()
                    ->scalarNode(self::IDENTIFIER_CONFIG_NODE)
                        ->isRequired()
                        ->cannotBeEmpty()
                        ->info('The identifier of the prepared filter.')
                    ->end()
                    ->scalarNode(self::USE_POLICY_CONFIG_NODE)
                        ->defaultNull()
                        ->info('A boolean policy expression that determines whether the current user may apply the prepared filter. If not set, the filter is backend-only. Available parameters: user.')
                    ->end()
                    ->scalarNode(self::FORCE_USE_POLICY_CONFIG_NODE)
                        ->defaultNull()
                        ->info('A boolean policy expression that determines whether the filter is force-used for the current user. If not set, it is never force-used. Available parameters: user.')
                    ->end()
                    ->arrayNode(self::FORCE_USE_FOR_USERS_NODE)
                        ->beforeNormalization()->ifString()->then(static fn ($v) => [$v])->end()
                        ->scalarPrototype()->end()
                        ->info('A list of user identifiers for which the filter is force-used.')
                    ->end()
                    ->scalarNode(self::FILTER_CONFIG_NODE)
                    ->defaultValue('')
                    ->info('The filter in URL query string format.')
                    ->end()
                ->end()
                ->validate()
                ->ifTrue(function (array $filterConfig) {
                    return false === empty($filterConfig[self::FORCE_USE_FOR_USERS_NODE]) && isset($filterConfig[self::FORCE_USE_POLICY_CONFIG_NODE]);
                })
                ->thenInvalid('Only one of "'.self::FORCE_USE_FOR_USERS_NODE.'" and "'.self::FORCE_USE_POLICY_CONFIG_NODE.'" can be set for a prepared filter.')
                ->end()
            ->end()
        ;
    }

    public function loadConfig(array $config): void
    {
        $this->config = [];
        foreach ($config[self::ROOT_CONFIG_NODE] ?? [] as $configEntry) {
            $filterIdentifier = $configEntry[self::IDENTIFIER_CONFIG_NODE];
            if (isset($this->config[$filterIdentifier])) {
                throw new \RuntimeException(sprintf('multiple config entries for prepared filter \'%s\'', $filterIdentifier));
            }
            $this->config[$filterIdentifier] = $configEntry;
        }
    }

    public function assertCurrentUserMayUseFilter(
        string $filterIdentifier, AbstractAuthorizationService $authorizationService): void
    {
        $usePolicy = $this->config[$filterIdentifier][self::USE_POLICY_CONFIG_NODE] ?? null;
        if (null === $usePolicy) {
            throw ApiError::withDetails(Response::HTTP_BAD_REQUEST,
                'Prepared filter undefined', ErrorIds::PREPARED_FILTER_UNDEFINED);
        }
        if (false === $authorizationService->evaluateCustomExpression($usePolicy)) {
            throw new AccessDeniedHttpException();
        }
    }

    public function getFiltersToForceUseForCurrentUser(AbstractAuthorizationService $authorizationService): array
    {
        $filtersToForce = [];
        foreach ($this->config as $filterIdentifier => $filterConfig) {
            if ($forceUseForUsers = $filterConfig[self::FORCE_USE_FOR_USERS_NODE] ?? null) {
                if (in_array($authorizationService->getUserIdentifier(), $forceUseForUsers, true)) {
                    $filtersToForce[] = $filterIdentifier;
                }
            } elseif (($forceUsePolicy = $filterConfig[self::FORCE_USE_POLICY_CONFIG_NODE] ?? null)
                && $authorizationService->evaluateCustomExpression($forceUsePolicy)) {
                $filtersToForce[] = $filterIdentifier;
            }
        }

        return $filtersToForce;
    }

    public function getPreparedFilterQueryString(string $filterIdentifier): string
    {
        $configEntry = $this->config[$filterIdentifier] ?? null;
        if (null === $configEntry) {
            throw new \RuntimeException(sprintf('prepared filter with identifier \'%s\' not found', $filterIdentifier));
        }

        return $configEntry[self::FILTER_CONFIG_KEY] ?? '';
    }
}
