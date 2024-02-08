<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Rest;

use Dbp\Relay\CoreBundle\Exception\ApiError;
use Symfony\Component\HttpFoundation\Response;

trait DataOperationTrait
{
    abstract protected function isUserGrantedOperationAccess(int $operation): bool;

    protected function isCurrentUserAuthorizedToAccessItem(int $operation, $item, array $filters): bool
    {
        return true;
    }

    /**
     * @throws apiError 401 Unauthorized, in case the current client has no access to the requested operation
     */
    private function denyOperationAccessUnlessGranted(int $operation)
    {
        if (!$this->isUserGrantedOperationAccess($operation)) {
            throw ApiError::withDetails(Response::HTTP_UNAUTHORIZED, 'access denied');
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
