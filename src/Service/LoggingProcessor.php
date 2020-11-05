<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\Service;

use DBP\API\CoreBundle\Helpers\Tools;
use DBP\API\CoreBundle\Keycloak\DBPUserInterface;
use Symfony\Component\Security\Core\Security;

class LoggingProcessor
{
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function __invoke(array $record)
    {
        // Try to avoid information leaks (users should still not log sensitive information though...)
        $record["message"] = Tools::filterErrorMessage($record["message"]);

        // Add some default context (session ID etc)
        $user = $this->security->getUser();
        if ($user !== null) {
            assert($user instanceof DBPUserInterface);
            $record['context']['dbp-id'] = $user->getLoggingID();
        }

        return $record;
    }

}