<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\Keycloak;

use ApiPlatform\Core\Exception\ItemNotFoundException;
use DBP\API\CoreBundle\Entity\Person;
use DBP\API\CoreBundle\Service\PersonProviderInterface;

class KeycloakBearerUser implements DBPUserInterface
{
    /**
     * @var PersonProviderInterface
     */
    private $personProvider;

    /**
     * @var Person|null
     */
    private $person;

    /**
     * @var string[]
     */
    private $scopes;

    /**
     * @var string
     */
    private $accessToken;

    /**
     * @var string|null
     */
    private $username;

    /**
     * @var bool
     */
    private $isRealUser;

    private $loggingID;

    public function __construct(?string $username, string $accessToken, PersonProviderInterface $personProvider, array $scopes)
    {
        $this->personProvider = $personProvider;
        $this->person = null;
        $this->scopes = $scopes;
        $this->accessToken = $accessToken;
        $this->username = $username;
        $this->isRealUser = ($this->username !== null);
        $this->loggingID = null;
    }

    public function setLoggingID(string $loggingID) {
        $this->loggingID = $loggingID;
    }

    private function ensurePerson()
    {
        if (!$this->person && $this->isRealUser) {
            try {
                $this->person = $this->personProvider->getPerson($this->getUsername());
            } catch (ItemNotFoundException $e) {
                // XXX: In case of EID we have no good way right now to see if we should have to user in LDAP
                $this->isRealUser = false;
            }

            // Inject the roles coming from the access token
            if ($this->isRealUser) {
                $roles = $this->person->getRoles();
                $roles = array_merge($roles, $this->getScopeRoles());
                $roles = array_unique($roles);
                sort($roles, SORT_STRING);
                $this->person->setRoles($roles);
            }
        }
    }

    public function getPerson(): ?Person
    {
        $this->ensurePerson();
        if (!$this->isRealUser) {
            return null;
        }

        return $this->person;
    }

    private function getScopeRoles()
    {
        $roles = [];
        foreach ($this->scopes as $scope) {
            $roles[] = 'ROLE_SCOPE_'.mb_strtoupper($scope);
        }
        sort($roles, SORT_STRING);

        return $roles;
    }

    public function getRoles()
    {
        $this->ensurePerson();

        if (!$this->isRealUser) {
            return $this->getScopeRoles();
        } else {
            return $this->person->getRoles();
        }
    }

    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }

    public function getPassword()
    {
        return null;
    }

    public function getSalt()
    {
        return null;
    }

    public function getUsername()
    {
        return $this->username ?? '';
    }

    public function eraseCredentials()
    {
    }

    public function getLoggingID(): string
    {
        return $this->loggingID ?? "unknown";
    }
}
