<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Authorization;

/**
 * Implement this interface and register the service to expose a named variable
 * in all authz expression contexts (roles, resource permissions, attributes,
 * custom expressions).
 *
 * The variable name returned by getName() must be unique across all registered
 * providers and must not be 'user' or 'resource' (reserved names).
 *
 * Example: a provider returning getName() = 'foobar' makes the object returned
 * by getValue() available as `foobar` in expressions:
 *   'MAY_READ': 'foobar.getId() === resource.getFoobarId()'
 */
interface AuthorizationExpressionVariableProviderInterface
{
    /**
     * The variable name as it appears in authz expressions (e.g. 'foobar').
     * Must be unique across all registered providers.
     * Must not be 'user' or 'resource' (reserved names).
     */
    public function getName(): string;

    /**
     * Returns the value to expose under the variable name in every authz expression evaluation.
     * This method is called on every expression evaluation, so request-scoped values are supported.
     */
    public function getValue(): mixed;
}
