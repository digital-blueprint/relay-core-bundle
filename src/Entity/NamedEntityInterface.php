<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Entity;

/**
 * @deprecated Use  Dbp\Relay\CoreBundle\Rest\Entity\EntityInterface instead
 */
interface NamedEntityInterface extends \Dbp\Relay\CoreBundle\Rest\Entity\NamedEntityInterface
{
    public function getIdentifier();

    public function getName();
}
