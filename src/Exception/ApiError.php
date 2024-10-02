<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Exception;

use ApiPlatform\Metadata\ErrorResource;
use ApiPlatform\State\ApiResource\Error;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Serializer;

#[ErrorResource(
    uriTemplate: '/api-errors/{status}',
    uriVariables: ['status'],
    normalizationContext: [
        'ignored_attributes' => ['trace', 'file', 'line', 'code', 'message', 'traceAsString', 'previous', 'description'],
        'skip_null_values' => true,
        'groups' => null,
    ],
    openapi: false
)]
class ApiError extends Error
{
    #[SerializedName('relay:errorId')]
    private ?string $errorId = null;

    #[SerializedName('relay:errorDetails')]
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
