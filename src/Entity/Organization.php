<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use DBP\API\CoreBundle\Controller\GetOrganizationsByPerson;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 *     collectionOperations={
 *         "get" = {
 *             "path" = "/organizations",
 *             "openapi_context" = {
 *                 "tags" = {"Core"},
 *                 "parameters" = {
 *                     {"name" = "lang", "in" = "query", "description" = "Language of result", "type" = "string", "enum" = {"de", "en"}, "example" = "de"}
 *                 }
 *             }
 *         },
 *         "get_orgs" = {
 *             "method" = "GET",
 *             "path" = "/people/{identifier}/organizations",
 *             "controller" = GetOrganizationsByPerson::class,
 *             "read" = false,
 *             "openapi_context" = {
 *                 "tags" = {"Core"},
 *                 "summary" = "Get the organizations related to a person.",
 *                 "parameters" = {
 *                     {"name" = "identifier", "in" = "path", "description" = "Id of person", "required" = true, "type" = "string", "example" = "vlts01"},
 *                     {"name" = "context", "in" = "query", "description" = "type of relation", "required" = false, "type" = "string", "example" = "library-manager"},
 *                     {"name" = "lang", "in" = "query", "description" = "language", "type" = "string", "example" = "en"},
 *                 }
 *             },
 *         }
 *     },
 *     itemOperations={
 *         "get" = {
 *             "path" = "/organizations/{identifier}",
 *             "openapi_context" = {
 *                 "tags" = {"Core"},
 *                 "parameters" = {
 *                     {"name" = "identifier", "in" = "path", "description" = "orgUnitID of organization", "required" = true, "type" = "string", "example" = "1190-F2050"},
 *                     {"name" = "lang", "in" = "query", "description" = "Language of result", "type" = "string", "enum" = {"de", "en"}, "example" = "de"}
 *                 }
 *             }
 *         },
 *     },
 *     iri="http://schema.org/Organization",
 *     description="An organization",
 *     normalizationContext={
 *         "jsonld_embed_context" = true,
 *         "groups" = {"Organization:output"}
 *     }
 * )
 */
class Organization
{
    /**
     * @Groups({"Organization:output"})
     * @ApiProperty(identifier=true)
     *
     * @var string
     */
    private $identifier;

    /**
     * @Groups({"Organization:output"})
     * @ApiProperty(iri="https://schema.org/name")
     *
     * @var string
     */
    private $name;

    /**
     * @Groups({"Organization:output"})
     * @ApiProperty(iri="https://schema.org/url")
     *
     * @var string
     */
    private $url;

    /**
     * @Groups({"Organization:output"})
     * @ApiProperty(iri="https://schema.org/alternateName")
     *
     * @var string
     */
    private $alternateName;

    public function setIdentifier(string $identifier): self
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getAlternateName(): ?string
    {
        return $this->alternateName;
    }

    public function setAlternateName(string $alternateName): self
    {
        $this->alternateName = $alternateName;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }
}
