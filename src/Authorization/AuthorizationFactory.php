<?php

namespace Dbp\Relay\CoreBundle\Authorization;

use Dbp\Relay\CoreBundle\API\UserSessionInterface;

class AuthorizationFactory
{
    /**
     * @var UserSessionInterface
     */
    private $userSession;

    /**
     * @var AuthorizationDataMuxer
     */
    private $mux;

    public function __construct(UserSessionInterface $userSession, AuthorizationDataMuxer $mux)
    {
        $this->userSession = $userSession;
        $this->mux = $mux;
    }

    public function createFromConfig()
    {

    }
}