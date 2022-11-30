<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Entity;

/**
 * Interface for entities with an identifier and a name.
 */
interface NamedEntityInterface
{
    public function getIdentifier();

    public function getName();
}
