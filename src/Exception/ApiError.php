<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class ApiError extends HttpException
{
    private ?string $errorId = null;
    private ?array $errorDetails = null;

    public static function withDetails(int $statusCode, ?string $message = '', string $errorId = '', array $errorDetails = []): ApiError
    {
        $apiError = new ApiError($statusCode, $message);
        $apiError->setErrorId($errorId);
        $apiError->setErrorDetails($errorDetails);

        return $apiError;
    }

    public function setErrorId(?string $errorId): void
    {
        $this->errorId = $errorId;
    }

    public function setErrorDetails(?array $errorDetails): void
    {
        $this->errorDetails = $errorDetails;
    }

    public function getErrorId(): ?string
    {
        return $this->errorId;
    }

    public function getErrorDetails(): ?array
    {
        return $this->errorDetails;
    }

    /**
     * @deprecated use getMessage() instead
     */
    public function getDetail(): string
    {
        return $this->getMessage();
    }
}
