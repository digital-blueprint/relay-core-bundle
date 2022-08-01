<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Http;

use Dbp\Relay\CoreBundle\Helpers\GuzzleTools;
use Dbp\Relay\CoreBundle\Helpers\Tools;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\HandlerStack;
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
     * @param array $queryParameters Associative array of query parameters
     * @param array $requestOptions  Array of RequestOptions to apply (see \GuzzleHttp\RequestOptions)
     *
     * @throws GuzzleException
     */
    public function get(string $uri, array $queryParameters = [], array $requestOptions = []): ResponseInterface
    {
        if (!empty($queryParameters)) {
            $requestOptions[RequestOptions::QUERY] = array_merge($queryParameters,
                $requestOptions[RequestOptions::QUERY] ?? []);
        }

        return $this->request('GET', $uri, $requestOptions);
    }

    /**
     * @param array $requestOptions Array of RequestOptions to apply (see \GuzzleHttp\RequestOptions)
     *
     * @throws GuzzleException
     */
    public function post(string $uri, array $requestOptions = []): ResponseInterface
    {
        return $this->request('POST', $uri, $requestOptions);
    }

    public function getClient(): Client
    {
        return $this->getClientInternal();
    }

    /**
     * @throws GuzzleException
     */
    private function request(string $method, string $uri, array $requestOptions): ResponseInterface
    {
        $client = $this->getClientInternal();

        return $client->request($method, $uri, $requestOptions);
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
            $cacheMiddleWare->setHttpMethods(['GET' => true, 'HEAD' => true]);
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
