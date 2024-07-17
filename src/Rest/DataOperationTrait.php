<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Rest;

use Dbp\Relay\CoreBundle\Exception\ApiError;
use Symfony\Component\HttpFoundation\Response;

trait DataOperationTrait
{
    /**
     * Override if you want to allow unauthenticated access to certain operations.
     * Returning false for the given operation will prevent a 401 unauthorized error to be thrown for unauthenticated users.
     * Defaults to true.
     */
    protected function requiresAuthentication(int $operation): bool
    {
        return true;
    }

    /**
     * Override if you want to restrict access to certain operations.
     * Returning false for the given operation will cause a 403 forbidden error to be thrown.
     * Defaults to true.
     */
    protected function isCurrentUserGrantedOperationAccess(int $operation): bool
    {
        return true;
    }

    /**
     * Override if you want to restrict access to certain items (entities) in certain operations.
     * Returning false for the given operation and item will cause a 403 forbidden error to be thrown.
     * Defaults to true.
     */
    protected function isCurrentUserAuthorizedToAccessItem(int $operation, $item, array $filters): bool
    {
        return true;
    }

    /**
     * @throws ApiError 401 Unauthorized, in case authentication is required and the current user is not authenticated
     *                  403 Forbidden, in the case the authenticated user has no access to the requested operation
     */
    private function denyOperationAccessUnlessGranted(int $operation): void
    {
        if (!$this->isAuthenticated() && $this->requiresAuthentication($operation)) {
            throw ApiError::withDetails(Response::HTTP_UNAUTHORIZED, 'unauthorized');
        }

        if (!$this->isCurrentUserGrantedOperationAccess($operation)) {
            throw ApiError::withDetails(Response::HTTP_FORBIDDEN, 'forbidden');
        }
    }

    /**
     * @throws ApiError 403 Forbidden, in case the current client has no access to the item
     *                  in the context of the requested operation
     */
    private function forbidCurrentUserToAccessItemUnlessAuthorized(int $operation, $item, array $filters): void
    {
        if (!$this->isCurrentUserAuthorizedToAccessItem($operation, $item, $filters)) {
            throw ApiError::withDetails(Response::HTTP_FORBIDDEN, 'forbidden');
        }
    }
}
