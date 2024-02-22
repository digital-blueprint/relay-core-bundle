<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\ApiPlatform\State;

use ApiPlatform\State\ProviderInterface;

/**
 * @template T of object
 *
 * @extends ProviderInterface<T>
 */
interface StateProviderInterface extends ProviderInterface
{
}
