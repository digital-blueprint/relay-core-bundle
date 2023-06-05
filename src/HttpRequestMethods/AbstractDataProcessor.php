<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\HttpRequestMethods;

use ApiPlatform\Core\DataPersister\ContextAwareDataPersisterInterface;
use ApiPlatform\Metadata\HttpOperation;
use Dbp\Relay\CoreBundle\Authorization\AbstractAuthorizationService;
use Dbp\Relay\CoreBundle\Exception\ApiError;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractDataProcessor extends AbstractAuthorizationService implements ContextAwareDataPersisterInterface
{
    use DataOperationTrait;

    protected const ADD_ITEM_OPERATION = 1;
    protected const REPLACE_ITEM_OPERATION = 2;
    protected const UPDATE_ITEM_OPERATION = 3;
    protected const REMOVE_ITEM_OPERATION = 4;

    public function supports($data, array $context = []): bool
    {
        return get_class($data) === $this->getResourceClass();
    }

    public function persist($data, array $context = [])
    {
        $httpRequestMethod = $context['operation']->getMethod();
        switch ($httpRequestMethod) {
            case HttpOperation::METHOD_POST:
                $operation = self::ADD_ITEM_OPERATION;
                break;
            case HttpOperation::METHOD_PUT:
                $operation = self::REPLACE_ITEM_OPERATION;
                break;
            case HttpOperation::METHOD_PATCH:
                $operation = self::UPDATE_ITEM_OPERATION;
                break;
            default:
                throw ApiError::withDetails(Response::HTTP_INTERNAL_SERVER_ERROR, 'unknown item operation name: '.$httpRequestMethod);
        }

        $this->denyOperationAccessUnlessGranted($operation);

        switch ($operation) {
            case self::ADD_ITEM_OPERATION:
                $data = $this->addItem($data);
                break;
            case self::REPLACE_ITEM_OPERATION:
                $previousData = $context['previous_data'] ?? null;
                $data = $this->replaceItem($data, $previousData);
                break;
            case self::UPDATE_ITEM_OPERATION:
                $previousData = $context['previous_data'] ?? null;
                $data = $this->updateItem($data, $previousData);
                break;
        }

        return $data;
    }

    public function remove($data, array $context = [])
    {
        $this->denyOperationAccessUnlessGranted(self::REMOVE_ITEM_OPERATION);
        $this->removeItem($data);
    }

    protected function addItem($data)
    {
    }

    protected function replaceItem($data, $previousData)
    {
    }

    protected function updateItem($data, $previousData)
    {
    }

    protected function removeItem($data)
    {
    }
}
