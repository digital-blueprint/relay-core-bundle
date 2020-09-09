<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\Keycloak;

use ApiPlatform\Core\Exception\ItemNotFoundException;
use DBP\API\CoreBundle\Entity\Person;
use DBP\API\CoreBundle\Service\PersonProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class KeycloakBearerUser implements UserInterface
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

    public function __construct(?string $username, string $accessToken, PersonProviderInterface $personProvider, array $scopes)
    {
        $this->personProvider = $personProvider;
        $this->person = null;
        $this->scopes = $scopes;
        $this->accessToken = $accessToken;
        $this->username = $username;
        $this->isRealUser = ($this->username !== null);
    }

    private function ensurePerson()
    {
        if (!$this->person && $this->isRealUser) {
            try {
                $this->person = $this->personProvider->getPerson($this->getUsername(), false);
            } catch (ItemNotFoundException $e) {
                // XXX: In case of EID we have no good way right now to see if we should have to user in LDAP
                $this->isRealUser = false;
            }
        }
    }

    public function getRoles()
    {
        $this->ensurePerson();

        if (!$this->isRealUser) {
            $roles = [];
        } else {
            $roles = $this->person->getRoles();
        }

        foreach ($this->scopes as $scope) {
            $roles[] = 'ROLE_SCOPE_'.mb_strtoupper($scope);
        }

        return $roles;
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
}
