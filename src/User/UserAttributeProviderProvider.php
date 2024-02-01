<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\User;

/**
 * @internal
 */
class UserAttributeProviderProvider
{
    /**
     * @var iterable<UserAttributeProviderInterface>
     */
    private $authorizationDataProviders;

    /**
     * @param iterable<UserAttributeProviderInterface> $authorizationDataProviders
     */
    public function __construct(iterable $authorizationDataProviders)
    {
        $this->authorizationDataProviders = $authorizationDataProviders;
    }

    /**
     * @return iterable<UserAttributeProviderInterface>
     */
    public function getAuthorizationDataProviders(): iterable
    {
        return $this->authorizationDataProviders;
    }
}
