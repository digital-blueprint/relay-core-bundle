<?php


namespace DBP\API\CoreBundle\Service;

use ApiPlatform\Core\Exception\ItemNotFoundException;
use DBP\API\CoreBundle\Entity\Person;
use DBP\API\CoreBundle\Exception\ItemNotLoadedException;
use DBP\API\CoreBundle\Helpers\TUGTools;
use DBP\API\CoreBundle\Helpers\JsonException;
use DBP\API\CoreBundle\Helpers\Tools as CoreTools;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Security;
use function GuzzleHttp\uri_template;

class KeycloakApi implements PersonProviderInterface
{
    private $clientId;
    private $clientSecret;
    private $realm;
    private $logger;
    private $security;

    public function __construct(LoggerInterface $logger, Security $security)
    {
        $this->clientId = $_ENV['KEYCLOAK_CLIENT_ID'];
        $this->clientSecret = $_ENV['KEYCLOAK_CLIENT_SECRET'];
        $this->realm = $_ENV['KEYCLOAK_REALM'];
        $this->logger = $logger;
        $this->security = $security;
    }

    private function getClient() : Client
    {
        $base_uri = $_ENV['KEYCLOAK_SERVER_URL'];
        if (substr($base_uri, -1) !== '/')
            $base_uri .= '/';

        $client_options = ['base_uri' => $base_uri];
        $client = new Client($client_options);
        return $client;
    }

    private function getPersonForUser($user) {
        $attributes = $user['attributes'];

        $person = new Person();
        $person->setIdentifier($user['username']);
        $person->setGivenName($user['firstName']);
        $person->setFamilyName($user['lastName']);
        if(isset($user['email']))
            $person->setEmail($user['email']);

        $accountTypes = $attributes['accountTypes'] ?? [];
        $functions = $attributes['functions'] ?? [];

        TUGTools::injectSpecialPermissions($user['username'], $functions);

        $person->setFunctions($functions);
        $person->setAccountTypes($accountTypes);
        $roles = array_merge(TUGTools::functionsToRoles($functions), TUGTools::accountTypesToRoles($accountTypes));
        $person->setRoles($roles);

//        $almaID = LDAPApi::getAlmaPersonMappingHash($user['username'], true);
        // we have setup a mapper for LDAP CO-ALMA-PATRON-ID to almaId
        $person->setAlmaId("nope-42-FIXME");

        return $person;
    }

    private function getToken() {
        $client = $this->getClient();
        $uri =  uri_template('realms/{realm}/protocol/openid-connect/token', [
            'realm' => $this->realm,
        ]);
        $options = [
            'auth' => [$this->clientId, $this->clientSecret],
            'form_params' => ['grant_type' => 'client_credentials'],
        ];
        $response = $client->request('POST', $uri, $options);

        $body = (string)$response->getBody();
        $data = CoreTools::decodeJSON($body, true);
        return $data['access_token'];
    }

    /**
     * @param array $filters
     * @return array
     * @throws ItemNotLoadedException
     * @throws JsonException
     */
    public function getPersons(array $filters): array
    {
        $token = $this->getToken();
        $search = $filters['search'];

        $client = $this->getClient();
        $uri =  uri_template('admin/realms/{realm}/users', [
            'realm' => $this->realm,
        ]);
        $options = [
            'headers' => [
                'Accept'     => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ],
            'query' => [
                'username' => $search,
                // TODO: remove limit
                'max' => '50',
            ]
        ];

        $response = $client->request('GET', $uri, $options);
        $body = (string)$response->getBody();
        $data = CoreTools::decodeJSON($body, true);
        $persons = [];
        foreach ($data as $user) {
            $persons[] = $this->getPersonForUser($user);
        }
        return $persons;
    }

    /**
     * @param string $id
     * @param bool $full
     * @return Person
     * @throws ItemNotLoadedException
     * @throws JsonException
     */
    public function getPerson(string $id, bool $full): Person
    {
        $token = $this->getToken();
        $client = $this->getClient();
        $uri =  uri_template('admin/realms/{realm}/users', [
            'realm' => $this->realm,
        ]);
        $options = [
            'headers' => [
                'Accept'     => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ],
            'query' => [
                'username' => $id,
                'max' => '1',
            ]
        ];

        $response = $client->request('GET', $uri, $options);
        $body = (string)$response->getBody();
        $data = CoreTools::decodeJSON($body, true);
        if (!count($data))
            throw new ItemNotFoundException();
        $user = $data[0];

        return $this->getPersonForUser($user);
    }

    /**
     * @inheritDoc
     */
    public function getCurrentPerson(): Person
    {
        $user = $this->security->getUser();
        $username = $user->getUsername();
        return $this->getPerson($username, true);
    }

    /**
     * @inheritDoc
     */
    public function getPersonForExternalService(string $service, string $serviceID): Person
    {
        throw new ItemNotFoundException("No external mapping implemented");
    }
}
