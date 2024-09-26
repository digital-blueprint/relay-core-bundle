<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Rest;

use Dbp\Relay\CoreBundle\API\UserSessionInterface;
use Dbp\Relay\CoreBundle\Exception\ApiError;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Service\Attribute\Required;

trait CustomControllerTrait
{
    private UserSessionInterface $userSession;

    #[Required]
    public function __injectUserSession(UserSessionInterface $userSession): void
    {
        $this->userSession = $userSession;
    }

    /**
     * Call this method to assure only authenticated users have access.
     *
     * @throws ApiError 401 Unauthorized, in case the current user is not authenticated
     */
    protected function requireAuthentication(): void
    {
        if (!$this->userSession->isAuthenticated()) {
            throw ApiError::withDetails(Response::HTTP_UNAUTHORIZED, 'unauthorized');
        }
    }
}
