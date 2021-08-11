<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * This is equal to a HttpException but the contained message will be serialized to
 * the client as jsonld/json, even for 500 errors. This is used to relay more detailed
 * error messages to the client for errors from third party systems for example.
 */
class ApiError extends HttpException
{
    /**
     * @var ?string
     */
    private $errorId;

    /**
     * @var ?array
     */
    private $errorDetails;

    public function __construct(int $statusCode, ?string $message = '', \Throwable $previous = null, array $headers = [], ?int $code = 0)
    {
        $message = json_decode($message, true);

        if ($message === null)
        {
            $message = [
                'message' => $message,
                'errorId' => '',
                'errorDetails' => null,
            ];
        }

        parent::__construct($statusCode, json_encode($message), $previous, $headers, $code);
    }

    public function setErrorDetails(string $errorId, array $errorDetails = [])
    {
        $this->errorId = $errorId;
        $this->errorDetails = $errorDetails;
    }

    public function getErrorDetails(): array
    {
        return [$this->errorId, $this->errorDetails];
    }

    public static function withDetails(int $statusCode, ?string $message = '', string $errorId = '', array $errorDetails = [])
    {
        $message = [
            'message' => $message,
            'errorId' => $errorId,
            'errorDetails' => $errorDetails,
        ];

        return new ApiError($statusCode, json_encode($message));
    }
}
