<?php

namespace DBP\API\CoreBundle\Service;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Security;

class PersonProviderFactory
{
    private static $LDAP = true;

    public static function createPersonProvider(ContainerInterface $container, TUGOnlineApi $tugapi, LoggerInterface $logger, Security $security)
    {
        if (self::$LDAP) {
            return new LDAPApi($container, $tugapi, $security, $logger);
        } else {
            return new KeycloakApi($container, $logger, $security);
        }
    }
}
