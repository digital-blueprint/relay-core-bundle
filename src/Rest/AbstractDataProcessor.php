<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Rest;

use Dbp\Relay\CoreBundle\ApiPlatform\State\StateProcessorInterface;
use Dbp\Relay\CoreBundle\ApiPlatform\State\StateProcessorTrait;
use Dbp\Relay\CoreBundle\Authorization\AbstractAuthorizationService;

abstract class AbstractDataProcessor extends AbstractAuthorizationService implements StateProcessorInterface
{
    use DataOperationTrait;
    use StateProcessorTrait;

    private const FILTERS_CONTEXT_KEY = 'filters';

    protected const ADD_ITEM_OPERATION = 1;
    protected const REPLACE_ITEM_OPERATION = 2;
    protected const UPDATE_ITEM_OPERATION = 3;
    protected const REMOVE_ITEM_OPERATION = 4;

    protected function post($data, array $context)
    {
        $this->denyOperationAccessUnlessGranted(self::ADD_ITEM_OPERATION);

        return $this->addItem($data, $context[self::FILTERS_CONTEXT_KEY] ?? []);
    }

    protected function put($identifier, $data, $context)
    {
        $this->denyOperationAccessUnlessGranted(self::REPLACE_ITEM_OPERATION);

        return $this->replaceItem($identifier, $data, $context['previous_data'] ?? null,
            $context[self::FILTERS_CONTEXT_KEY] ?? []);
    }

    protected function patch($identifier, $data, $context)
    {
        $this->denyOperationAccessUnlessGranted(self::UPDATE_ITEM_OPERATION);

        return $this->updateItem($identifier, $data, $context['previous_data'] ?? null,
            $context[self::FILTERS_CONTEXT_KEY] ?? []);
    }

    protected function delete($identifier, $data, array $context)
    {
        $this->denyOperationAccessUnlessGranted(self::REMOVE_ITEM_OPERATION);

        $this->removeItem($identifier, $data, $context[self::FILTERS_CONTEXT_KEY] ?? []);
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
}
