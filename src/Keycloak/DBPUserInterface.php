<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\Keycloak;

use Symfony\Component\Security\Core\User\UserInterface;

interface DBPUserInterface extends UserInterface
{
    /**
     * Returns an ID represents a "session" of a user which can be used for logging. It should not be possible to
     * figure out which user is behind the ID based on the ID itself and the ID should change regularly.
     * This is useful for connecting various requests together for logging while not exposing details about the user.
     */
    public function getLoggingID(): string;
}
