<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Authorization;

class AuthorizationDataProviderProvider
{
    /**
     * @var iterable<AuthorizationDataProviderInterface>
     */
    private $authorizationDataProviders;

    /**
     * @param iterable<AuthorizationDataProviderInterface> $authorizationDataProviders
     */
    public function __construct(iterable $authorizationDataProviders)
    {
        $this->authorizationDataProviders = $authorizationDataProviders;
    }

    /**
     * @return iterable<AuthorizationDataProviderInterface>
     */
    public function getAuthorizationDataProviders(): iterable
    {
        return $this->authorizationDataProviders;
    }
}
