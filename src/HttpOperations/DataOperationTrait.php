<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\HttpOperations;

use Dbp\Relay\CoreBundle\Exception\ApiError;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

trait DataOperationTrait
{
    abstract protected function isUserGrantedOperationAccess(int $operation): bool;

    /**
     * @throws AccessDeniedException
     */
    private function denyOperationAccessUnlessGranted(int $operation)
    {
        if (!$this->isUserGrantedOperationAccess($operation)) {
            throw ApiError::withDetails(Response::HTTP_UNAUTHORIZED, 'access denied');
        }
    }
}
