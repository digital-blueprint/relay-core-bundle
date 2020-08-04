<?php


namespace DBP\API\CoreBundle\Service;


use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Security;

class PersonProviderFactory
{
    private static $LDAP = true;

    public static function createPersonProvider(TUGOnlineApi $tugapi, LoggerInterface $logger, Security $security)
    {
        if (self::$LDAP)
            return new LDAPApi($tugapi, $security, $logger);
        else
            return new KeycloakApi($logger, $security);
    }
}