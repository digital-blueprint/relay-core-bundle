<?php

namespace DBP\API\CoreBundle\Keycloak;

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
     * @var bool
     */
    private $isServiceAccount;

    /**
     * @var string[]
     */
    private $scopes;

    /**
     * @var string
     */
    private $accessToken;

    /**
     * @var string
     */
    private $username;

    public function __construct(string $username, string $accessToken, PersonProviderInterface $personProvider, bool $isServiceAccount, array $scopes) {
        $this->personProvider = $personProvider;
        $this->person = null;
        $this->isServiceAccount = $isServiceAccount;
        $this->scopes = $scopes;
        $this->accessToken = $accessToken;
        $this->username = $username;
    }

    /**
     * In case the user is a service account it isn't associated with a Person and getPerson() will fail.
     *
     * @return bool
     */
    public function isServiceAccount(): bool {
        return $this->isServiceAccount;
    }

    private function getPerson() {
        if ($this->isServiceAccount())
            throw new \RuntimeException("No person available for service accounts");

        if (!$this->person)
            $this->person = $this->personProvider->getPerson($this->getUsername(), false);
        return $this->person;
    }

    public function getRoles()
    {
        if ($this->isServiceAccount()) {
            $roles = [];
        } else {
            $roles = $this->getPerson()->getRoles();
        }

        foreach ($this->scopes as $scope) {
            $roles[] = 'ROLE_SCOPE_' . mb_strtoupper($scope);
        }

        return $roles;
    }

    public function getInstitutesForGroup(string $group) {
        if ($this->isServiceAccount()) {
            return [];
        }

        return $this->getPerson()->getInstitutesForGroup($group);
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
        return $this->username;
    }

    public function eraseCredentials()
    {
    }
}
