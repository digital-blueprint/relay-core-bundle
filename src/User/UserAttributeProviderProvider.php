<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\User;

/**
 * @internal
 */
class UserAttributeProviderProvider implements UserAttributeProviderProviderInterface
{
    /**
     * @param iterable<UserAttributeProviderInterface> $authorizationDataProviders
     */
    public function __construct(private readonly iterable $authorizationDataProviders)
    {
    }

    /**
     * @return iterable<UserAttributeProviderInterface>
     */
    public function getAuthorizationDataProviders(): iterable
    {
        return $this->authorizationDataProviders;
    }
}
