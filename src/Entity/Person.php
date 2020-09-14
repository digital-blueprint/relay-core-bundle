<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeInterface;
use DBP\API\AlmaBundle\Controller\GetLibraryBookLoansByPerson;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 *   collectionOperations={
 *     "get"={"openapi_context"={
 *       "parameters"={{"name"="search", "in"="query", "description"="Search for a person name", "type"="string", "example"="woody007"}
 *     }}},
 *   },
 *   itemOperations={
 *      "get",
 *      "get_loans"={
 *         "method"="GET",
 *         "path"="/people/{id}/library-book-loans",
 *         "controller"=GetLibraryBookLoansByPerson::class,
 *         "normalization_context"={"jsonld_embed_context"=true, "groups"={"LibraryBookLoan:output", "Person:output", "LibraryBookOffer:output", "LibraryBook:output"}},
 *         "openapi_context"=
 *           {"summary"="Get the library book loans of a person.",
 *            "parameters"={{"name"="id", "in"="path", "description"="Id of person", "required"="true", "type"="string", "example"="vlts01"}}},
 *      }
 *   },
 *   iri="http://schema.org/Person",
 *   description="A person of the LDAP system",
 *   normalizationContext={"groups"={"Person:output"}, "jsonld_embed_context"=true}
 * )
 */
class Person
{
    /**
     * @ApiProperty(identifier=true)
     * @Groups({"Person:output"})
     *
     * @var string
     */
    private $identifier;

    /**
     * @ApiProperty(iri="http://schema.org/givenName")
     * @Groups({"Person:output"})
     *
     * @var string
     */
    private $givenName;

    /**
     * @var string
     * @ApiProperty(iri="http://schema.org/familyName")
     * @Groups({"Person:output"})
     *
     * @var string
     */
    private $familyName;

    /**
     * @ApiProperty(iri="http://schema.org/honorificSuffix")
     * @Groups({"Person:output"})
     *
     * @var string
     */
    private $honorificSuffix;

    /**
     * @ApiProperty(iri="http://schema.org/telephone")
     * @Groups({"Person:output"})
     *
     * @var string
     */
    private $telephone;

    /**
     * @var string
     * @Groups({"Person:output"})
     *
     * @var string
     */
    private $phoneExtension;

    /**
     * @ApiProperty(iri="http://schema.org/email")
     * @Groups({"Person:output"})
     *
     * @var string
     */
    private $email;

    /**
     * @var string|null
     * @ApiProperty(iri="http://schema.org/image", required=false)
     * @Groups({"Person:current-user"})
     */
    private $image;

    /**
     * @var string
     * @Groups({"Person:current-user"})
     *
     * @var string
     */
    private $almaId;

    /**
     * @var array
     * @Groups({"Person:current-user"})
     */
    private $functions;

    /**
     * @var array
     * @Groups({"Person:current-user"})
     */
    private $roles;

    /**
     * @var DateTimeInterface
     * @ApiProperty(iri="http://schema.org/Date")
     * @Groups({"Person:current-user", "Person:birthdate-access"})
     */
    private $birthDate;

    /**
     * @var array
     * @Groups({"Person:current-user"})
     */
    private $accountTypes;

    public function setIdentifier(string $identifier): self
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function getGivenName(): ?string
    {
        return $this->givenName;
    }

    public function setGivenName(?string $givenName): self
    {
        $this->givenName = $givenName;

        return $this;
    }

    public function getFamilyName(): ?string
    {
        return $this->familyName;
    }

    public function setFamilyName(?string $familyName): self
    {
        $this->familyName = $familyName;

        return $this;
    }

    public function getHonorificSuffix(): ?string
    {
        return $this->honorificSuffix;
    }

    public function setHonorificSuffix(?string $honorificSuffix): self
    {
        $this->honorificSuffix = $honorificSuffix;

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): self
    {
        $this->telephone = $telephone;

        return $this;
    }

    public function getPhoneExtension(): ?string
    {
        return $this->phoneExtension;
    }

    public function setPhoneExtension(?string $phoneExtension): self
    {
        $this->phoneExtension = $phoneExtension;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getAlmaId(): ?string
    {
        return $this->almaId;
    }

    public function setAlmaId(?string $almaId): self
    {
        $this->almaId = $almaId;

        return $this;
    }

    /**
     * @ApiProperty(iri="http://schema.org/name")
     * @Groups({"Person:output"})
     */
    public function getName(): ?string
    {
        return $this->givenName.' '.$this->familyName;
    }

    public function getFunctions(): array
    {
        return $this->functions;
    }

    /**
     * @return Person
     */
    public function setFunctions(array $functions): self
    {
        $this->functions = $functions;

        return $this;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * @return Person
     */
    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function getAccountTypes(): array
    {
        return $this->accountTypes;
    }

    /**
     * @return Person
     */
    public function setAccountTypes(array $accountTypes): self
    {
        $this->accountTypes = $accountTypes;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): void
    {
        $this->image = $image;
    }

    public function getBirthDate(): ?DateTimeInterface
    {
        return $this->birthDate;
    }

    public function setBirthDate(DateTimeInterface $birthDate): self
    {
        $this->birthDate = $birthDate;

        return $this;
    }

    /**
     * Returns the institutes for a group (e.g. "F_BIB").
     *
     * @param string $group
     *
     * @return array
     */
    public function getInstitutesForGroup($group)
    {
        $group = preg_quote($group);
        $results = [];
        $re = "/^$group:F:(\d+):[\d_]+$/i";

        $functions = $this->getFunctions();

        foreach ($functions as $function) {
            if (preg_match($re, $function, $matches)) {
                $results[] = 'F'.$matches[1];
            }
        }

        return $results;
    }
}
