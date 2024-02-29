<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\User;

interface UserAttributeProviderProviderInterface
{
    /**
     * @return iterable<UserAttributeProviderInterface>
     */
    public function getAuthorizationDataProviders(): iterable;
}
