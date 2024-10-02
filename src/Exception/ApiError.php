<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Exception;

use ApiPlatform\Metadata\Error as Operation;
use ApiPlatform\Metadata\ErrorResource;
use ApiPlatform\State\ApiResource\Error;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\WebLink\Link;

#[ErrorResource(
    uriTemplate: '/errors/{status}',
    types: ['hydra:Error'],
    operations: [
        new Operation(
            outputFormats: ['json' => ['application/problem+json']],
            routeName: 'api_errors',
            normalizationContext: [
                'groups' => ['jsonproblem'],
                'skip_null_values' => true,
                'rfc_7807_compliant_errors' => true,
            ],
            name: '_api_errors_problem',
        ),
        new Operation(
            outputFormats: ['jsonld' => ['application/problem+json']],
            routeName: 'api_errors',
            links: [new Link(rel: 'http://www.w3.org/ns/json-ld#error', href: 'http://www.w3.org/ns/hydra/error')],
            normalizationContext: [
                'groups' => ['jsonld'],
                'skip_null_values' => true,
                'rfc_7807_compliant_errors' => true,
            ],
            name: '_api_errors_hydra',
        ),
    ],
    uriVariables: ['status'],
    openapi: false,
    graphQlOperations: [],
    provider: 'api_platform.state.error_provider'
)]
class ApiError extends Error
{
    #[SerializedName('relay:errorId')]
    #[Groups(['jsonld', 'jsonproblem'])]
    private ?string $errorId = null;

    #[SerializedName('relay:errorDetails')]
    #[Groups(['jsonld', 'jsonproblem'])]
    #[Context([Serializer::EMPTY_ARRAY_AS_OBJECT => true])]
    private ?\ArrayObject $errorDetails = null;

    public function __construct(int $statusCode, ?string $message = '')
    {
        parent::__construct(Response::$statusTexts[$statusCode] ?? 'undefined status code', $message, $statusCode);
    }

    public static function withDetails(int $statusCode, ?string $message = '', string $errorId = '', array $errorDetails = []): ApiError
    {
        $apiError = new ApiError($statusCode, $message);
        $apiError->setErrorId($errorId);
        $apiError->setErrorDetails(new \ArrayObject($errorDetails));

        return $apiError;
    }

    public function getErrorId(): ?string
    {
        return $this->errorId;
    }

    public function setErrorId(?string $errorId): void
    {
        $this->errorId = $errorId;
    }

    public function getErrorDetails(): ?\ArrayObject
    {
        return $this->errorDetails;
    }

    public function setErrorDetails(?\ArrayObject $errorDetails): void
    {
        $this->errorDetails = $errorDetails;
    }
}
