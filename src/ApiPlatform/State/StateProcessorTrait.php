<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\ApiPlatform\State;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;

trait StateProcessorTrait
{
    protected static $identifierName = 'identifier';

    public function process($data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        if ($operation instanceof Post) {
            return $this->post($data, $context);
        } elseif ($operation instanceof Put) {
            return $this->put($uriVariables[static::$identifierName], $data, $context);
        } elseif ($operation instanceof Patch) {
            return $this->patch($uriVariables[static::$identifierName], $data, $context);
        } elseif ($operation instanceof Delete) {
            $this->delete($uriVariables[static::$identifierName], $data, $context);
        }
    }
}
