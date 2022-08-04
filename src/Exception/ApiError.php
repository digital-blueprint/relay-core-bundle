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
    private const WITHDETAILSSTATUS = -1;

    public function __construct(int $statusCode, ?string $message = '', \Throwable $previous = null, array $headers = [], ?int $code = 0)
    {
        if ($statusCode === self::WITHDETAILSSTATUS) {
            $decoded = json_decode($message, true, 512, JSON_THROW_ON_ERROR);
            $statusCode = $decoded['statusCode'];
            unset($decoded['statusCode']);
        } else {
            $decoded = [
                'message' => $message,
                'errorId' => '',
                'errorDetails' => null,
            ];
        }

        parent::__construct($statusCode, json_encode($decoded), $previous, $headers, $code);
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
            'statusCode' => $statusCode,
            'message' => $message,
            'errorId' => $errorId,
            'errorDetails' => $errorDetails,
        ];

        return new ApiError(self::WITHDETAILSSTATUS, json_encode($message));
    }
}
