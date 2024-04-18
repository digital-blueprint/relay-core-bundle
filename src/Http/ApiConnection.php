<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Http;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class ApiConnection
{
    public const API_URL_CONFIG_PARAMETER = 'api_url';

    public const OIDC_SERVER_URL_CONFIG_PARAMETER = 'oidc_server_url';
    public const OIDC_REALM_CONFIG_PARAMETER = 'oidc_realm';
    public const CLIENT_ID_CONFIG_PARAMETER = 'client_id';
    public const CLIENT_SECRET_CONFIG_PARAMETER = 'client_secret';

    /** @var Connection */
    private $connection;

    /** @var array */
    private $config;

    /** @var object|null */
    private $clientHandler;

    /** @var CacheItemPoolInterface|null */
    private $cachePool;

    /** @var int */
    private $cacheTTL;

    /** @var LoggerInterface|null */
    private $logger;

    /** @var string */
    private $accessToken;

    public function __construct()
    {
        $this->config = [];
    }

    public function setCache(?CacheItemPoolInterface $cachePool, int $ttl): void
    {
        $this->cachePool = $cachePool;
        $this->cacheTTL = $ttl;
        if ($this->connection !== null) {
            $this->connection->setCache($cachePool, $ttl);
        }
    }

    public function setConfig(array $config): void
    {
        $this->config = $config;
    }

    /*
     * Used to mock up server for unit testing.
     */
    public function setClientHandler(?object $handler): void
    {
        $this->clientHandler = $handler;
        if ($this->connection !== null) {
            $this->connection->setClientHandler($handler);
        }
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
        if ($this->connection !== null) {
            $this->connection->setLogger($logger);
        }
    }

    /**
     * @throws ConnectionException
     */
    public function get(string $uri, array $query): ResponseInterface
    {
        $requestOptions = [
            Connection::REQUEST_OPTION_HEADERS => [
                'Authorization' => 'Bearer '.$this->getAccessToken(),
            ],
        ];

        return $this->getConnection()->get($uri, $query, $requestOptions);
    }

    /**
     * @throws ConnectionException
     */
    public function postJSON(string $uri, array $parameters): ResponseInterface
    {
        $requestOptions = [
            Connection::REQUEST_OPTION_HEADERS => [
                'Authorization' => 'Bearer '.$this->getAccessToken(),
            ],
        ];

        return $this->getConnection()->postJSON($uri, $parameters, $requestOptions);
    }

    /**
     * @throws ConnectionException
     */
    private function getAccessToken(): string
    {
        if ($this->accessToken === null) {
            $idServerUrl = $this->config[self::OIDC_SERVER_URL_CONFIG_PARAMETER];
            $idServerRealm = $this->config[self::OIDC_REALM_CONFIG_PARAMETER];
            $clientId = $this->config[self::CLIENT_ID_CONFIG_PARAMETER];
            $clientSecret = $this->config[self::CLIENT_SECRET_CONFIG_PARAMETER];

            $tokenUrl = $idServerUrl.'/realms/'.$idServerRealm.'/protocol/openid-connect/token';
            $response = $this->getIdServerConnection()->postForm($tokenUrl, [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'grant_type' => 'client_credentials',
            ]);

            $responseData = $response->getBody()->getContents();

            try {
                $token = json_decode($responseData, true, flags: JSON_THROW_ON_ERROR);
            } catch (\JsonException $exception) {
                throw new ConnectionException(ConnectionException::JSON_EXCEPTION, sprintf('Failed to JSON decode access token: '.$exception->getMessage()), $exception->getCode(), $exception);
            }

            $this->accessToken = $token['access_token'];
        }

        return $this->accessToken;
    }

    private function getConnection(): Connection
    {
        if ($this->connection === null) {
            $connection = new Connection($this->config[self::API_URL_CONFIG_PARAMETER]);

            if ($this->cachePool !== null) {
                $connection->setCache($this->cachePool, $this->cacheTTL);
            }
            if ($this->clientHandler !== null) {
                $connection->setClientHandler($this->clientHandler);
            }
            if ($this->logger !== null) {
                $connection->setLogger($this->logger);
            }

            $this->connection = $connection;
        }

        return $this->connection;
    }

    private function getIdServerConnection(): Connection
    {
        $idServerConnection = new Connection();

        if ($this->clientHandler !== null) {
            $idServerConnection->setClientHandler($this->clientHandler);
        }

        return $idServerConnection;
    }
}
