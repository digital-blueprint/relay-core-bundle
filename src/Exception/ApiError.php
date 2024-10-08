<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Exception;

use ApiPlatform\Metadata\Error as Operation;
use ApiPlatform\Metadata\ErrorResource;
use ApiPlatform\Metadata\Exception\HttpExceptionInterface;
use ApiPlatform\State\ApiResource\Error;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface as SymfonyHttpExceptionInterface;
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
        new Operation(
            outputFormats: ['jsonapi' => ['application/vnd.api+json']],
            routeName: 'api_errors',
            normalizationContext: [
                'groups' => ['jsonapi'],
                'skip_null_values' => true,
                'rfc_7807_compliant_errors' => true,
            ],
            name: '_api_errors_jsonapi',
        ),
        new Operation(
            routeName: 'api_errors',
            name: '_api_errors'
        ),
    ],
    uriVariables: ['status'],
    openapi: false,
    graphQlOperations: [],
    provider: 'api_platform.state.error_provider'
)]
class ApiError extends Error
{
    private ?string $errorId = null;
    private ?\ArrayObject $errorDetails = null;

    public function __construct(int $statusCode, string $message = '')
    {
        parent::__construct(Response::$statusTexts[$statusCode] ?? 'An error occurred', $message, $statusCode,
            type: "/errors/$statusCode");
    }

    public static function withDetails(int $statusCode, ?string $message = '', string $errorId = '', array $errorDetails = []): ApiError
    {
        $apiError = new ApiError($statusCode, $message);
        $apiError->setErrorId($errorId);
        $apiError->setErrorDetails(new \ArrayObject($errorDetails));

        return $apiError;
    }

    public static function createFromException(\Exception|\Throwable $exception, int $status): ApiError
    {
        $apiError = new self($status, $exception->getMessage());

        $headers = ($exception instanceof SymfonyHttpExceptionInterface || $exception instanceof HttpExceptionInterface) ?
            $exception->getHeaders() : [];
        $apiError->setHeaders($headers);

        $apiError->originalTrace = $exception->getTrace();

        return $apiError;
    }

    #[SerializedName('relay:errorId')]
    #[Groups(['jsonld', 'jsonproblem'])]
    public function getErrorId(): ?string
    {
        return $this->errorId;
    }

    public function setErrorId(?string $errorId): void
    {
        $this->errorId = $errorId;
    }

    #[SerializedName('relay:errorDetails')]
    #[Groups(['jsonld', 'jsonproblem'])]
    #[Context([Serializer::EMPTY_ARRAY_AS_OBJECT => true])]
    public function getErrorDetails(): ?\ArrayObject
    {
        return $this->errorDetails;
    }

    public function setErrorDetails(?\ArrayObject $errorDetails): void
    {
        $this->errorDetails = $errorDetails;
    }

    #[SerializedName('hydra:title')]
    #[Groups(['jsonld'])]
    public function getHydraTitle(): ?string
    {
        return $this->getTitle();
    }

    #[SerializedName('hydra:description')]
    #[Groups(['jsonld'])]
    public function getHydraDescription(): ?string
    {
        return $this->getDetail();
    }
}
