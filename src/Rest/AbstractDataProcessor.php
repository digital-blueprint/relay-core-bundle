<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Rest;

use Dbp\Relay\CoreBundle\ApiPlatform\State\StateProcessorInterface;
use Dbp\Relay\CoreBundle\ApiPlatform\State\StateProcessorTrait;
use Dbp\Relay\CoreBundle\Authorization\AbstractAuthorizationService;
use Dbp\Relay\CoreBundle\Exception\ApiError;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractDataProcessor extends AbstractAuthorizationService implements StateProcessorInterface
{
    use DataOperationTrait;
    use StateProcessorTrait;

    private const FILTERS_CONTEXT_KEY = 'filters';

    protected const ADD_ITEM_OPERATION = 1;
    protected const REPLACE_ITEM_OPERATION = 2;
    protected const UPDATE_ITEM_OPERATION = 3;
    protected const REMOVE_ITEM_OPERATION = 4;

    public function __construct()
    {
        parent::__construct();
    }

    protected function post($data, array $context)
    {
        $this->denyOperationAccessUnlessGranted(self::ADD_ITEM_OPERATION);

        $filters = $context[self::FILTERS_CONTEXT_KEY] ?? [];
        $this->forbidCurrentUserToAddItemUnlessAuthorized($data, $filters);

        return $this->addItem($data, $filters);
    }

    protected function put($identifier, $data, $context)
    {
        $this->denyOperationAccessUnlessGranted(self::REPLACE_ITEM_OPERATION);

        $currentItem = $context['previous_data'] ?? null;
        $filters = $context[self::FILTERS_CONTEXT_KEY] ?? [];
        $this->forbidCurrentUserToAccessItemUnlessAuthorized(self::REPLACE_ITEM_OPERATION, $currentItem, $filters);

        return $this->replaceItem($identifier, $data, $currentItem, $filters);
    }

    protected function patch($identifier, $data, $context)
    {
        $this->denyOperationAccessUnlessGranted(self::UPDATE_ITEM_OPERATION);

        $currentItem = $context['previous_data'] ?? null;
        $filters = $context[self::FILTERS_CONTEXT_KEY] ?? [];
        $this->forbidCurrentUserToAccessItemUnlessAuthorized(self::UPDATE_ITEM_OPERATION, $currentItem, $filters);

        return $this->updateItem($identifier, $data, $currentItem,
            $context[self::FILTERS_CONTEXT_KEY] ?? []);
    }

    protected function delete($identifier, $data, array $context)
    {
        $this->denyOperationAccessUnlessGranted(self::REMOVE_ITEM_OPERATION);

        $filters = $context[self::FILTERS_CONTEXT_KEY] ?? [];
        $this->forbidCurrentUserToAccessItemUnlessAuthorized(self::REMOVE_ITEM_OPERATION, $data, $filters);

        $this->removeItem($identifier, $data, $filters);
    }

    protected function addItem($data, array $filters)
    {
        return $data;
    }

    protected function replaceItem($identifier, $data, $previousData, array $filters)
    {
        return $data;
    }

    protected function updateItem($identifier, $data, $previousData, array $filters)
    {
        return $data;
    }

    protected function removeItem($identifier, $data, array $filters): void
    {
    }

    protected function forbidCurrentUserToAddItemUnlessAuthorized($data, array $filters): void
    {
        if (!$this->isCurrentUserAuthorizedToAddItem($data, $filters)) {
            throw ApiError::withDetails(Response::HTTP_FORBIDDEN, 'forbidden');
        }
    }

    /**
     * Override if you want to restrict access to the add item operation.
     * Returning false for the given item will cause a 403 forbidden error to be thrown.
     * Defaults to true.
     */
    protected function isCurrentUserAuthorizedToAddItem($item, array $filters): bool
    {
        return true;
    }
}
