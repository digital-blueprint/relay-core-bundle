<?php
/**
 * LDAP wrapper service.
 *
 * @see https://github.com/Adldap2/Adldap2
 */

namespace DBP\API\CoreBundle\Service;

use Adldap\Adldap;
use Adldap\Models\User;
use Adldap\Query\Builder;
use ApiPlatform\Core\Exception\ItemNotFoundException;
use DBP\API\CoreBundle\Entity\Person;
use DBP\API\CoreBundle\Exception\ItemNotLoadedException;
use DBP\API\CoreBundle\Helpers\Tools as CoreTools;
use DBP\API\CoreBundle\Helpers\TUGTools;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Security;

class LDAPApi implements PersonProviderInterface
{
    public const ROLE_STAFF = 'ROLE_STAFF';
    public const ROLE_STUDENT = 'ROLE_STUDENT';
    public const ROLE_ALUMNI = 'ROLE_ALUMNI';
    public const ROLE_BIB = 'ROLE_F_BIB_F';

    // singleton to cache fetched users by alma user id
    public static $USERS_BY_ALMA_USER_ID = [];

    private $PAGESIZE = 50;

    /**
     * @var Adldap
     */
    private $ad;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var TUGOnlineApi
     */
    private $tugapi;

    private $security;

    public function __construct(ContainerInterface $container, TUGOnlineApi $tugapi, Security $security, LoggerInterface $logger)
    {
        $config = $container->getParameter('dbp_api.core.ldap_config');
        $this->ad = new Adldap();
        $this->logger = $logger;
        $this->security = $security;

        $config = [
            'hosts' => [$config['host']],
            'base_dn' => $config['base_dn'],
            'username' => $config['username'],
            'password' => $config['password'],
            'use_tls' => true,
        ];

        $this->ad->addProvider($config);
        $this->tugapi = $tugapi;
    }

    /**
     * @throws ItemNotLoadedException
     */
    private function getPeopleUserItems(array $filters): array
    {
        try {
            // If a successful connection is made to your server, the provider will be returned.
            $provider = $this->ad->connect();

            $builder = $provider->search();
            assert($builder instanceof Builder);
            $search = $builder
                ->where('objectClass', '=', $provider->getSchema()->person());

            if (isset($filters['search'])) {
                $items = explode(' ', $filters['search']);

                // search for all substrings
                foreach ($items as $item) {
                    $search->whereContains('fullName', $item);
                }
            }

            return $search->sortBy('sn', 'asc')->paginate($this->PAGESIZE)->getResults();
        } catch (\Adldap\Auth\BindException $e) {
            // There was an issue binding / connecting to the server.
            throw new ItemNotLoadedException(sprintf('People could not be loaded! Message: %s', CoreTools::filterErrorMessage($e->getMessage())));
        }
    }

    /**
     * @throws ItemNotLoadedException
     * @throws \Exception
     */
    public function getPersons(array $filters): array
    {
        $persons = [];
        $items = $this->getPeopleUserItems($filters);
        foreach ($items as $item) {
            $person = $this->personFromUserItem($item, false);
            $persons[] = $person;
        }

        return $persons;
    }

    /**
     * @throws ItemNotFoundException
     * @throws ItemNotLoadedException
     */
    public function getPersonUserItem(string $identifier): ?User
    {
        // replaced once or double encoded $ character at the start like "%2524F1234" or "%24F1234"
        $identifier = preg_replace('/^%(25)?24/', '$', $identifier);

        try {
            // If a successful connection is made to your server, the provider will be returned.
            $provider = $this->ad->connect();

            $builder = $provider->search();
            assert($builder instanceof Builder);

            /** @var User $user */
            $user = $builder
                ->where('objectClass', '=', $provider->getSchema()->person())
                ->whereEquals('cn', $identifier)
                ->first();

            if ($user === null) {
                throw new ItemNotFoundException(sprintf("Person with id '%s' could not be found!", $identifier));
            }

            return $user;
        } catch (\Adldap\Auth\BindException $e) {
            // There was an issue binding / connecting to the server.
            throw new ItemNotLoadedException(sprintf("Person with id '%s' could not be loaded! Message: %s", $identifier, CoreTools::filterErrorMessage($e->getMessage())));
        }
    }

    /**
     * @throws ItemNotFoundException
     * @throws ItemNotLoadedException
     */
    private function getPersonUserItemByAlmaUserId(string $almaUserId): ?User
    {
        try {
            // If a successful connection is made to your server, the provider will be returned.
            $provider = $this->ad->connect();

            $builder = $provider->search();
            assert($builder instanceof Builder);

            // if we already have fetched the user by alma user id in this request we will use the cached version
            if (array_key_exists($almaUserId, self::$USERS_BY_ALMA_USER_ID)) {
                $user = self::$USERS_BY_ALMA_USER_ID[$almaUserId];
            } else {
                /** @var User $user */
                $user = $builder
                    ->where('objectClass', '=', $provider->getSchema()->person())
                    ->whereEquals('CO-ALMA-PATRON-ID', $almaUserId)
                    ->first();

                self::$USERS_BY_ALMA_USER_ID[$almaUserId] = $user;
            }

            if ($user === null) {
                throw new ItemNotFoundException(sprintf("Person with alma user id '%s' could not be found!", $almaUserId));
            }

            return $user;
        } catch (\Adldap\Auth\BindException $e) {
            // There was an issue binding / connecting to the server.
            throw new ItemNotLoadedException(sprintf("Person with alma user id '%s' could not be loaded! Message: %s", $almaUserId, CoreTools::filterErrorMessage($e->getMessage())));
        }
    }

    /**
     * @throws \Exception
     */
    public function personFromUserItem(User $user, bool $full = true): Person
    {
        $identifier = $user->getFirstAttribute('cn');

        $person = new Person();
        $person->setIdentifier($identifier);
        $person->setGivenName($user->getFirstAttribute('givenName'));
        $person->setFamilyName($user->getFirstAttribute('sn'));
        $person->setHonorificSuffix($user->getFirstAttribute('title'));
        $person->setTelephone($user->getFirstAttribute('telephoneNumber'));
        $person->setPhoneExtension($user->getFirstAttribute('phoneExtension'));
        $person->setEmail($user->getFirstAttribute('mail'));

        $birthDateString = trim($user->getFirstAttribute('DateOfBirth'));

        if ($birthDateString !== '') {
            $matches = [];

            // get birthday from LDAP DateOfBirth (e.g. 19810718)
            if (preg_match('/^(\d{4})(\d{2})(\d{2})$/', $birthDateString, $matches)) {
                $person->setBirthDate(new \DateTime("{$matches[1]}-{$matches[2]}-{$matches[3]}"));
            // sometimes also "1994-06-14 00:00:00"
            } elseif (preg_match('/^(\d{4})-(\d{2})-(\d{2}) .*$/', $birthDateString, $matches)) {
                $person->setBirthDate(new \DateTime("{$matches[1]}-{$matches[2]}-{$matches[3]}"));
            }
        }

        // FIXME: How to make properties optional?
        if ($full) {
            $imageURL = $this->tugapi->getImageURLforUser($user);
        } else {
            $imageURL = '';
        }
        if (!empty($imageURL)) {
            $person->setImage($imageURL);
        }

        $accountTypes = $user->getAttribute('CO-ACCOUNTTYPE-STATUS-C') ?? [];

        $functions = $user->getAttribute('CO-FUNKDE-C');
        $functions = is_array($functions) ? $functions : [];

        TUGTools::injectSpecialPermissions($identifier, $functions);

        $person->setAccountTypes($accountTypes);
        $person->setFunctions($functions);

        $currentUser = $this->security->getUser();
        if ($full && $currentUser !== null && $currentUser->getUsername() === $identifier) {
            $roles = $currentUser->getRoles();
        } else {
            $roles = array_merge(TUGTools::functionsToRoles($functions), TUGTools::accountTypesToRoles($accountTypes));
        }

        $roles = array_unique($roles);
        sort($roles, SORT_STRING);

        $person->setRoles($roles);

        $person->setAlmaId($user->getFirstAttribute('CO-ALMA-PATRON-ID'));

        return $person;
    }

    /**
     * @throws ItemNotLoadedException
     */
    public function getPerson(string $id, bool $full = true): Person
    {
        $id = str_replace('/people/', '', $id);
        $user = $this->getPersonUserItem($id);
        $person = $this->personFromUserItem($user, $full);

        return $person;
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentPerson(): Person
    {
        $user = $this->security->getUser();
        $username = $user->getUsername();

        return $this->getPerson($username, true);
    }

    /**
     * {@inheritdoc}
     */
    public function getPersonForExternalService(string $service, string $serviceID): Person
    {
        if ($service === 'ALMA') {
            $user = $this->getPersonUserItemByAlmaUserId($serviceID);

            return $this->personFromUserItem($user);
        } else {
            throw new ItemNotFoundException("Unknown service: $service");
        }
    }
}
