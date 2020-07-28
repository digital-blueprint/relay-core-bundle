<?php

namespace DBP\API\CoreBundle\Service;

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
        $dataString = $data != null ? ": " . json_encode($data) : "";
        $this->logger->notice("[{$service}] [{$user->getUsername()}] {$message}{$dataString}");
    }
}
