<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\User;

interface UserAttributePersisterInterface
{
    /**
     * @param mixed $attributeValue
     */
    public function setUserAttribute(string $userIdentifier, string $attributeName, $attributeValue): void;
}
