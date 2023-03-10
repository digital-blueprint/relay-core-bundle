<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Http;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class ConnectionException extends \RuntimeException
{
    public const REQUEST_EXCEPTION = 1;
    public const JSON_EXCEPTION = 2;
    public const INVALID_DATA_EXCEPTION = 3;

    /** @var RequestInterface|null */
    private $request;

    /** @var ResponseInterface|null */
    private $response;

    public function __construct(string $message, int $code, \Throwable $previous = null, RequestInterface $request = null, ResponseInterface $response = null)
    {
        parent::__construct($message, $code, $previous);

        $this->request = $request;
        $this->response = $response;
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
