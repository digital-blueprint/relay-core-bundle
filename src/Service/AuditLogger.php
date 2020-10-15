<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\Service;

use DBP\API\CoreBundle\Keycloak\DBPUserInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Security;

class AuditLogger
{
    private $security;
    private $logger;

    public function __construct(Security $security, LoggerInterface $auditLogger)
    {
        $this->security = $security;
        $this->logger = $auditLogger;
    }

    public function log($service, $message, $data = null)
    {
        $user = $this->security->getUser();
        assert ($user instanceof DBPUserInterface);

        $dataString = $data !== null ? ': '.json_encode($data) : '';
        $this->logger->notice("[{$service}] [{$user->getLoggingID()}] {$message}{$dataString}");
    }
}
