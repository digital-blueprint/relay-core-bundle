<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\TestUtils;

use ApiPlatform\Core\Exception\ItemNotFoundException;
use DBP\API\CoreBundle\Entity\Person;
use DBP\API\CoreBundle\Exception\ItemNotLoadedException;
use DBP\API\CoreBundle\Service\PersonProviderInterface;

class DummyPersonProvider implements PersonProviderInterface
{
    /* @var Person */
    private $person;

    public function __construct($person)
    {
        $this->person = $person;
    }

    public function getPersons(array $filters): array
    {
        return [$this->person];
    }

    public function getPerson(string $id, bool $full): Person
    {
        if ($id !== $this->person->getIdentifier()) {
            throw new ItemNotLoadedException();
        }

        return $this->person;
    }

    public function getCurrentPerson(): Person
    {
        return $this->person;
    }

    public function getPersonForExternalService(string $service, string $serviceID): Person
    {
        throw new ItemNotFoundException();
    }
}
