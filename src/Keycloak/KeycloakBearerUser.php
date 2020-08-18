<?php

declare(strict_types=1);

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

    public function __construct(?string $username, string $accessToken, PersonProviderInterface $personProvider, array $scopes)
    {
        $this->personProvider = $personProvider;
        $this->person = null;
        $this->scopes = $scopes;
        $this->accessToken = $accessToken;
        $this->username = $username;
    }

    /**
     * In case the user is a service account it isn't associated with a real User/Person and getPerson() will fail.
     */
    private function hasRealUser(): bool
    {
        return $this->username !== null;
    }

    private function getPerson()
    {
        if (!$this->hasRealUser()) {
            throw new \RuntimeException('No person available for service accounts');
        }
        if (!$this->person) {
            $this->person = $this->personProvider->getPerson($this->getUsername(), false);
        }

        return $this->person;
    }

    public function getRoles()
    {
        if (!$this->hasRealUser()) {
            $roles = [];
        } else {
            $roles = $this->getPerson()->getRoles();
        }

        foreach ($this->scopes as $scope) {
            $roles[] = 'ROLE_SCOPE_'.mb_strtoupper($scope);
        }

        return $roles;
    }

    public function getInstitutesForGroup(string $group)
    {
        if (!$this->hasRealUser()) {
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
        return $this->username ?? '';
    }

    public function eraseCredentials()
    {
    }
}
