<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\Service;

use DBP\API\CoreBundle\Helpers\Tools;
use DBP\API\CoreBundle\Keycloak\DBPUserInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Symfony\Component\Security\Core\Security;

/**
 * The main logger class which adds some filtering and adds some default context based on the API request
 */
class DBPLogger implements LoggerInterface
{
    use LoggerTrait;

    private $logger;
    private $security;

    public function __construct(LoggerInterface $logger, ?Security $security = null)
    {
        $this->logger = $logger;
        $this->security = $security;
    }

    public function log($level, $message, array $context = [])
    {
        // Try to avoid information leaks (users should still not log sensitive information though...)
        $message = Tools::filterErrorMessage($message);

        // Add some default context (session ID etc)
        if ($this->security !== null) {
            $user = $this->security->getUser();
            // XXX: not the case during some tests
            if ($user instanceof DBPUserInterface) {
                assert($user instanceof DBPUserInterface);
                $context['dbp-id'] = $user->getLoggingID();
            }
        }

        $this->logger->log($level, $message, $context);
    }
}