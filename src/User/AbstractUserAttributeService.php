<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\User;

abstract class AbstractUserAttributeService
{
    /** @var UserAttributeMuxer */
    private $userAttributeMuxer;

    /**
     * @required
     */
    public function __injectUserAttributeMuxer(UserAttributeMuxer $userAttributeMuxer)
    {
        $this->userAttributeMuxer = $userAttributeMuxer;
    }
}
