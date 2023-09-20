<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Http;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * HTTP Connection Exception: For HTTP type exceptions getCode() provides the HTTP response status code.
 */
class ConnectionException extends \RuntimeException
{
    public const REDIRECTION_EXCEPTION = 'redirection'; /* HTTP status codes 300 - 399 */
    public const CLIENT_EXCEPTION = 'client';           /* HTTP status codes 400 - 499 */
    public const SERVER_EXCEPTION = 'server';           /* HTTP status codes 500 - 599 */
    public const NETWORK_EXCEPTION = 'network';
    public const JSON_EXCEPTION = 'json';

    /** @var RequestInterface|null */
    private $request;

    /** @var ResponseInterface|null */
    private $response;

    /** @var string */
    private $type;

    public function __construct(string $type, string $message, int $code, \Throwable $previous = null, RequestInterface $request = null, ResponseInterface $response = null)
    {
        parent::__construct($message, $code, $previous);

        $this->type = $type;
        $this->request = $request;
        $this->response = $response;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getRequest(): ?RequestInterface
    {
        return $this->request;
    }

    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }
}
