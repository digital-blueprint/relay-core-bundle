<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\Helpers;

use DBP\API\CoreBundle\Service\LDAPApi;

class TUGTools
{
    /**
     * Generates role names from account types.
     */
    public static function accountTypesToRoles(array $accountTypes): array
    {
        $roles = [];

        if (in_array('BEDIENSTETE:OK', $accountTypes, true)) {
            $roles[] = LDAPApi::ROLE_STAFF;
        }

        if (in_array('STUDENTEN:OK', $accountTypes, true)) {
            $roles[] = LDAPApi::ROLE_STUDENT;
        }

        if (in_array('ALUMNI:OK', $accountTypes, true)) {
            $roles[] = LDAPApi::ROLE_ALUMNI;
        }

        return $roles;
    }

    /**
     * Generates role names from functions
     * Function "F_BIB:F:1490:681" will be role "ROLE_LIBRARY_MANAGER".
     *
     * @param string[] $functions
     *
     * @return string[]
     */
    public static function functionsToRoles(array $functions): array
    {
        $roles = [];
        foreach ($functions as $function) {
            $match = 'F_BIB:F:';
            if (substr($function, 0, strlen($match)) === $match) {
                $roles[] = 'ROLE_LIBRARY_MANAGER';
                // Backwards compat only, remove
                $roles[] = 'ROLE_F_BIB_F';
            }
        }
        $roles = array_unique($roles);

        return $roles;
    }

    /**
     * Injects special permissions.
     */
    public static function injectSpecialPermissions(string $userId, array &$functions, array &$roles)
    {
        $DEVELOPERS = ['christoph_reiter', 'jfink', 'pbeke', 'eneuber', 'koeseoglu', 'tsteinwen13', 'riina'];
        $DUMMY_USERS = ['woody007', 'koarl', 'muma', 'waldi08'];
        $IBIB_TEST_USERS = ['wrussm', 'finkst', 'salzburg'];
        $ESIGN_TEST_USERS = ['fipsi1505', 'joebch', 'dobnik', 'sascha_rossmann'];
        $ESIGN_DEVELOPERS = ['hurli', 'mschrei'];

        // give special access to developers and test accounts
        if (in_array($userId, $DEVELOPERS, true) || in_array($userId, $DUMMY_USERS, true) || in_array($userId, $IBIB_TEST_USERS, true)) {
            $functions[] = 'F_BIB:F:2190:1263';
            $functions[] = 'F_BIB:F:2050:1190';
            $functions[] = 'F_BIB:F:1490:681';
            $functions[] = 'F_BIB:F:2150:1231';
            $functions[] = 'F_BIB:F:4370:2322';
            $functions[] = 'F_BIB:F:5070:2374';
            $functions[] = 'F_BIB:F:3730:11072';
        }

        if (in_array($userId, $DEVELOPERS, true) || in_array($userId, $ESIGN_TEST_USERS, true)) {
            // Until we get those scopes set up in auth-test.tugraz.at and auth.tugraz.at
            $roles[] = 'ROLE_SCOPE_VERIFY-SIGNATURE';
        }

        if (in_array($userId, $DEVELOPERS, true) || in_array($userId, $ESIGN_DEVELOPERS, true)) {
            // Until we get those scopes set up in auth-test.tugraz.at and auth.tugraz.at
            $roles[] = 'ROLE_SCOPE_OFFICIAL-SIGNATURE';
        }

        // special handling for F2130 (Institut für Wasserbau und Wasserwirtschaft, id 1226) and
        // F2150 (Institut für Siedlungswasserwirtschaft und Landschaftswasserbau, id 1231)
        if (in_array('F_BIB:F:2130:1226', $functions, true) || in_array('F_BIB:F:2150:1231', $functions, true)) {
            // add function for F2135 (Zentralbibliothek Wasser) which has no real id
            $functions[] = 'F_BIB:F:2135:1226_1231';
        }

        sort($functions);
    }
}
