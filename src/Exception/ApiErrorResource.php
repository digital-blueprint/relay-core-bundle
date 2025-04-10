<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Exception;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ErrorResource;
use ApiPlatform\Metadata\Exception\HttpExceptionInterface;
use ApiPlatform\State\ApiResource\Error;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface as SymfonyHttpExceptionInterface;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Serializer;

#[ErrorResource]
class ApiErrorResource extends Error
{
    private ?string $errorId = null;
    private ?\ArrayObject $errorDetails = null;

    public function __construct(int $statusCode, string $message, ?array $originalTrace = null)
    {
        parent::__construct(Response::$statusTexts[$statusCode] ?? 'An error occurred', $message, $statusCode,
            $originalTrace, type: "/errors/$statusCode");
    }

    public static function createFromException(\Exception|\Throwable $exception, int $status): ApiErrorResource
    {
        $apiError = new self($status, $exception->getMessage(), $exception->getTrace());
        $apiError->setHeaders(($exception instanceof SymfonyHttpExceptionInterface || $exception instanceof HttpExceptionInterface) ?
            $exception->getHeaders() : []);

        return $apiError;
    }

    #[SerializedName('relay:errorId')]
    #[Groups(['jsonld', 'jsonproblem'])]
    #[ApiProperty(description: 'A human-readable explanation specific to this occurrence of the problem.', writable: false, initializable: false)]
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
    #[ApiProperty(description: 'A human-readable explanation specific to this occurrence of the problem.', writable: false, initializable: false)]
    #[Context([Serializer::EMPTY_ARRAY_AS_OBJECT => true])]
    public function getErrorDetails(): ?\ArrayObject
    {
        return $this->errorDetails;
    }

    public function setErrorDetails(\ArrayObject|array|null $errorDetails): void
    {
        if (is_array($errorDetails)) {
            $errorDetails = new \ArrayObject($errorDetails);
        }
        $this->errorDetails = $errorDetails;
    }
}
