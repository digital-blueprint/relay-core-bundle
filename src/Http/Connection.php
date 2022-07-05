<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Http;

use Dbp\Relay\CoreBundle\Exception\ApiError;
use Dbp\Relay\CoreBundle\Helpers\GuzzleTools;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\HandlerStack;
use Kevinrob\GuzzleCache\CacheMiddleware;
use Kevinrob\GuzzleCache\Storage\Psr6CacheStorage;
use Kevinrob\GuzzleCache\Strategy\GreedyCacheStrategy;
use League\Uri\Contracts\UriException;
use League\Uri\UriTemplate;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class Connection implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private $cachePool;
    private $cacheTTL;
    private $clientHandler;

    public function __construct()
    {
        $this->logger = null;
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
     * @param array $getParameters  Array of GET <param name>-<param value> pairs
     * @param array $requestOptions Array of RequestOptions to apply (see \GuzzleHttp\RequestOptions)
     *
     * @throws ApiError
     */
    public function get(string $uri, array $getParameters = [], array $requestOptions = []): ResponseInterface
    {
        try {
            $uri = $this->makeUri($uri, $getParameters);
        } catch (UriException $e) {
            throw ApiError::withDetails(500, 'invalid uri or parameters: '.$uri);
        }

        $client = $this->getClientInternal();
        try {
            $response = $client->get($uri, $requestOptions);
        } catch (GuzzleException $e) {
            throw new ApiError(500, $e->getMessage(), $e);
        }

        return $response;
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
            $cacheMiddleWare->setHttpMethods(['GET' => true, 'HEAD' => true]);
            $stack->push($cacheMiddleWare);
        }

        $client_options = [
            'handler' => $stack,
        ];

        return new Client($client_options);
    }

    /**
     * @throws UriException
     */
    private function makeUri(string $uri, array $parameters): string
    {
        foreach ($parameters as $param_key => $param_value) {
            $uri .= $param_key === array_key_first($parameters) ? '?' : '&';
            $uri .= $param_key.'={'.$param_key.'}';
        }

        $uriTemplate = new UriTemplate($uri);

        return (string) $uriTemplate->expand($parameters);
    }
}
