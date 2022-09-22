<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Http;

use Dbp\Relay\CoreBundle\Helpers\Tools;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use GuzzleHttp\RequestOptions;
use Kevinrob\GuzzleCache\CacheMiddleware;
use Kevinrob\GuzzleCache\Storage\Psr6CacheStorage;
use Kevinrob\GuzzleCache\Strategy\GreedyCacheStrategy;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class Connection implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public const REQUEST_METHOD_GET = 'GET';
    public const REQUEST_METHOD_HEAD = 'HEAD';
    public const REQUEST_METHOD_POST = 'POST';

    public const REQUEST_OPTION_HEADERS = RequestOptions::HEADERS;

    private $baseUri;
    private $cachePool;
    private $cacheTTL;
    private $clientHandler;

    public function __construct(string $baseUri = null)
    {
        $this->logger = null;

        if (!Tools::isNullOrEmpty($baseUri) && substr($baseUri, -1) !== '/') {
            $baseUri .= '/';
        }
        $this->baseUri = $baseUri;

        $this->cachePool = null;
        $this->cacheTTL = 0;
        $this->clientHandler = null;
    }

    public function setCache(?CacheItemPoolInterface $cachePool, int $ttl)
    {
        $this->cachePool = $cachePool;
        $this->cacheTTL = $ttl;
    }

    public function setClientHandler(?object $handler)
    {
        $this->clientHandler = $handler;
    }

    /**
     * @param array $query          Associative array of query parameters
     * @param array $requestOptions Array of request options to apply
     *
     * @throws ConnectionException
     */
    public function get(string $uri, array $query = [], array $requestOptions = []): ResponseInterface
    {
        $requestOptions[RequestOptions::QUERY] = $query;

        return $this->request(self::REQUEST_METHOD_GET, $uri, $requestOptions);
    }

    /**
     * Post web form parameters (automatically adds appropriate 'Content-Type' header).
     *
     * @param array $parameters     Associative array of web form parameters to send
     * @param array $requestOptions Array of request options to apply
     *
     * @throws ConnectionException
     */
    public function postForm(string $uri, array $parameters = [], array $requestOptions = []): ResponseInterface
    {
        $requestOptions[RequestOptions::FORM_PARAMS] = $parameters;

        return $this->request(self::REQUEST_METHOD_POST, $uri, $requestOptions);
    }

    /**
     * Post data encoded in JSON format (automatically adds appropriate 'Content-Type' header).
     *
     * @param mixed $data           Data to send (must be JSON encode-able)
     * @param array $requestOptions Array of request options to apply
     *
     * @throws ConnectionException
     */
    public function postJSON(string $uri, $data, array $requestOptions = []): ResponseInterface
    {
        $requestOptions[RequestOptions::JSON] = $data;

        return $this->request(self::REQUEST_METHOD_POST, $uri, $requestOptions);
    }

    /**
     * @throws ConnectionException
     */
    public function request(string $method, string $uri, array $requestOptions = []): ResponseInterface
    {
        try {
            $client = $this->getClientInternal();

            return $client->request($method, $uri, $requestOptions);
        } catch (GuzzleException $exception) {
            $request = null;
            $response = null;
            if ($exception instanceof RequestException) {
                $request = $exception->getRequest();
                $response = $exception->getResponse();
            }
            throw new ConnectionException(sprintf('HTTP %s request to %s failed. Message: \'%s\', Code: %s', $method, $this->baseUri.$uri, $exception->getMessage(), $exception->getCode()), ConnectionException::REQUEST_EXCEPTION, $exception, $request, $response);
        }
    }

    public function getClient(): Client
    {
        return $this->getClientInternal();
    }

    private function getClientInternal(): Client
    {
        $stack = HandlerStack::create($this->clientHandler);
        if ($this->logger !== null) {
            $stack->push(Middleware::log(
                $this->logger,
                new MessageFormatter('[{method}] {uri}: CODE={code}, ERROR={error}, CACHE={res_header_X-Kevinrob-Cache}')));
        }

        if ($this->cachePool !== null) {
            $cacheMiddleWare = new CacheMiddleware(
                new GreedyCacheStrategy(
                    new Psr6CacheStorage($this->cachePool),
                    $this->cacheTTL
                )
            );
            $cacheMiddleWare->setHttpMethods([self::REQUEST_METHOD_GET => true, self::REQUEST_METHOD_HEAD => true]);
            $stack->push($cacheMiddleWare);
        }

        $client_options = [
            'handler' => $stack,
        ];

        if (!Tools::isNullOrEmpty($this->baseUri)) {
            $client_options['base_uri'] = $this->baseUri;
        }

        return new Client($client_options);
    }
}
