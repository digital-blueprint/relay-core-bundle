<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Http;

use Dbp\Relay\CoreBundle\Helpers\GuzzleTools;
use Dbp\Relay\CoreBundle\Helpers\Tools;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\RequestOptions;
use Kevinrob\GuzzleCache\CacheMiddleware;
use Kevinrob\GuzzleCache\Storage\Psr6CacheStorage;
use Kevinrob\GuzzleCache\Strategy\GreedyCacheStrategy;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class Connection implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public const REQUEST_METHOD_GET = 'GET';
    public const REQUEST_METHOD_HEAD = 'HEAD';
    public const REQUEST_METHOD_POST = 'POST';

    public const REQUEST_OPTION_FORM_PARAMS = RequestOptions::FORM_PARAMS;
    public const REQUEST_OPTION_HEADERS = RequestOptions::HEADERS;
    public const REQUEST_OPTION_QUERY = RequestOptions::QUERY;

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
     * @throws ClientExceptionInterface
     */
    public function get(string $uri, array $query = [], array $requestOptions = []): ResponseInterface
    {
        if (!empty($query)) {
            $requestOptions[RequestOptions::QUERY] = array_merge($query,
                $requestOptions[RequestOptions::QUERY] ?? []);
        }

        return $this->request(self::REQUEST_METHOD_GET, $uri, $requestOptions);
    }

    /**
     * @param array $parameters     Associative array of (form) parameters to send
     * @param array $requestOptions Array of request options to apply
     *
     * @throws ClientExceptionInterface
     */
    public function post(string $uri, array $parameters = [], array $requestOptions = []): ResponseInterface
    {
        if (!empty($parameters)) {
            $requestOptions[RequestOptions::FORM_PARAMS] = array_merge($parameters,
                $requestOptions[RequestOptions::FORM_PARAMS] ?? []);
        }

        return $this->request(self::REQUEST_METHOD_POST, $uri, $requestOptions);
    }

    /**
     * @throws ClientExceptionInterface
     */
    public function request(string $method, string $uri, array $requestOptions = []): ResponseInterface
    {
        $client = $this->getClientInternal();

        return $client->request($method, $uri, $requestOptions);
    }

    public function getClient(): Client
    {
        return $this->getClientInternal();
    }

    private function getClientInternal(): Client
    {
        $stack = HandlerStack::create($this->clientHandler);
        if ($this->logger !== null) {
            $stack->push(GuzzleTools::createLoggerMiddleware($this->logger));
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
