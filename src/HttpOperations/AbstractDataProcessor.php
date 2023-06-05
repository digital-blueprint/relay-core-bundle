<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\HttpOperations;

use Dbp\Relay\CoreBundle\Authorization\AbstractAuthorizationService;

abstract class AbstractDataProcessor extends AbstractAuthorizationService
{
    use DataOperationTrait;

    protected const ADD_ITEM_OPERATION = 1;
    protected const REPLACE_ITEM_OPERATION = 2;
    protected const UPDATE_ITEM_OPERATION = 3;
    protected const REMOVE_ITEM_OPERATION = 4;

    public function post($data)
    {
        $this->denyOperationAccessUnlessGranted(self::ADD_ITEM_OPERATION);

        return $this->addItem($data);
    }

    public function put($data, $previousData)
    {
        $this->denyOperationAccessUnlessGranted(self::REPLACE_ITEM_OPERATION);

        return $this->replaceItem($data, $previousData);
    }

    public function patch($data, $previousData)
    {
        $this->denyOperationAccessUnlessGranted(self::UPDATE_ITEM_OPERATION);

        return $this->updateItem($data, $previousData);
    }

    public function delete($data)
    {
        $this->denyOperationAccessUnlessGranted(self::REMOVE_ITEM_OPERATION);
        $this->removeItem($data);
    }

    protected function addItem($data)
    {
        return $data;
    }

    protected function replaceItem($data, $previousData)
    {
        return $data;
    }

    protected function updateItem($data, $previousData)
    {
        return $data;
    }

    protected function removeItem($data)
    {
    }
}
