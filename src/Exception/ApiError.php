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
    private const WITH_DETAILS_STATUS_CODE = -1;
    private const STATUS_CODE_KEY = 'statusCode';
    private const ERROR_MESSAGE_KEY = 'message';
    private const ERROR_ID_KEY = 'errorId';
    private const ERROR_DETAILS_KEY = 'errorDetails';

    public function __construct(int $statusCode, ?string $message = '', \Throwable $previous = null, array $headers = [], ?int $code = 0)
    {
        if ($statusCode === self::WITH_DETAILS_STATUS_CODE) {
            $messageDecoded = self::decodeMessage($message);
            $statusCode = $messageDecoded[self::STATUS_CODE_KEY];
            unset($messageDecoded[self::STATUS_CODE_KEY]);
        } else {
            $messageDecoded = [
                self::ERROR_MESSAGE_KEY => $message,
                self::ERROR_ID_KEY => '',
                self::ERROR_DETAILS_KEY => null,
            ];
        }

        parent::__construct($statusCode, json_encode($messageDecoded), $previous, $headers, $code);
    }

    /**
     * @param int         $statusCode   The HTTP status code
     * @param string|null $message      The error message
     * @param string      $errorId      The custom error id e.g. 'bundle:my-custom-error'
     * @param array       $errorDetails An array containing additional information, content depends on the errorId
     */
    public static function withDetails(int $statusCode, ?string $message = '', string $errorId = '', array $errorDetails = []): ApiError
    {
        $message = [
            self::STATUS_CODE_KEY => $statusCode,
            self::ERROR_MESSAGE_KEY => $message,
            self::ERROR_ID_KEY => $errorId,
            self::ERROR_DETAILS_KEY => $errorDetails,
        ];

        return new ApiError(self::WITH_DETAILS_STATUS_CODE, json_encode($message));
    }

    public function getErrorId(): string
    {
        return self::decodeMessage($this->getMessage())[self::ERROR_ID_KEY];
    }

    public function getErrorMessage(): string
    {
        return self::decodeMessage($this->getMessage())[self::ERROR_MESSAGE_KEY];
    }

    public function getErrorDetails(): array
    {
        return self::decodeMessage($this->getMessage())[self::ERROR_DETAILS_KEY];
    }

    private static function decodeMessage(string $message): array
    {
        try {
            return json_decode($message, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $jsonException) {
            throw new \RuntimeException('unexpected error on json_decode');
        }
    }
}
