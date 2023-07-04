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
    public function process($data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        if ($operation instanceof Post) {
            return $this->post($data);
        } elseif ($operation instanceof Put) {
            return $this->put($data, $context['previous_data'] ?? null);
        } elseif ($operation instanceof Patch) {
            return $this->patch($data, $context['previous_data'] ?? null);
        } elseif ($operation instanceof Delete) {
            $this->delete($data);
        }
    }
}
