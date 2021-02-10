<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\Service\CampusOnline;

use Adldap\Models\User;
use DBP\API\CoreBundle\Helpers\GuzzleTools;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response;
use Kevinrob\GuzzleCache\CacheMiddleware;
use Kevinrob\GuzzleCache\Storage\Psr6CacheStorage;
use Kevinrob\GuzzleCache\Strategy\GreedyCacheStrategy;
use League\Uri\Contracts\UriException;
use League\Uri\UriTemplate;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class UserImageApi
{
    private $clientHandler;

    private $logger;

    private $config;

    private $container;

    private const CACHE_TTL = 3600;

    public function __construct(ContainerInterface $container, LoggerInterface $logger)
    {
        $this->container = $container;
        $this->config = $container->getParameter('dbp_api.core.co_config');
        $this->logger = $logger;
    }

    private function getClient(string $baseUrl): Client
    {
        $stack = HandlerStack::create($this->clientHandler);
        if (substr($baseUrl, -1) !== '/') {
            $baseUrl .= '/';
        }
        $client_options = [
            'base_uri' => $baseUrl,
            'handler' => $stack,
        ];

        $stack->push(GuzzleTools::createLoggerMiddleware($this->logger));

        $guzzleCachePool = $this->container->get('dbp_api.cache.core.campus_online');
        assert($guzzleCachePool instanceof CacheItemPoolInterface);

        $cacheMiddleWare = new CacheMiddleware(
            new GreedyCacheStrategy(
                new Psr6CacheStorage($guzzleCachePool),
                self::CACHE_TTL
            )
        );
        $cacheMiddleWare->setHttpMethods(['GET' => true, 'HEAD' => true]);
        $stack->push($cacheMiddleWare);

        $client = new Client($client_options);

        return $client;
    }

    /**
     * @throws UriException
     */
    public function getImageURLforUser(User $user): ?string
    {
        $uris = [];
        $accountTypes = $user->getAttribute('CO-ACCOUNTTYPE-STATUS-C') ?? [];

        $bdId = $user->getAttribute('CO-OBFUSCATED-C-BD');
        if (in_array('BEDIENSTETE:OK', $accountTypes, true) && !empty($bdId)) {
            $uriTemplate = new UriTemplate('visitenkarte.showImage?pPersonenGruppe={group}&pPersonenId={personId}');
            $uris[] = (string) $uriTemplate->expand([
                'group' => '3',
                'personId' => $bdId[0],
            ]);
        }

        $stId = $user->getAttribute('CO-OBFUSCATED-C-ST');
        if (in_array('STUDENTEN:OK', $accountTypes, true) && !empty($stId)) {
            $uriTemplate = new UriTemplate('visitenkarte.showImage?pPersonenGruppe={group}&pPersonenId={personId}');
            $uris[] = (string) $uriTemplate->expand([
                'group' => '5',
                'personId' => $stId[0],
            ]);
        }

        $extId = $user->getAttribute('CO-OBFUSCATED-C-EXT');
        if (in_array('ALUMNI:OK', $accountTypes, true) && !empty($extId)) {
            $uriTemplate = new UriTemplate('visitenkarte.showImage?pPersonenGruppe={group}&pPersonenId={personId}');
            $uris[] = (string) $uriTemplate->expand([
                'group' => '6',
                'personId' => $bdId[0],
            ]);
        }

        // Try out all URLs and return the first working one
        $webUrl = $this->config['web_url'];
        if (substr($webUrl, -1) !== '/') {
            $webUrl .= '/';
        }
        $client = $this->getClient($webUrl);
        $promises = [];
        foreach ($uris as $uri) {
            $promises[] = $client->headAsync($uri);
        }

        $results = Promise\settle($promises)->wait();

        foreach ($results as $i => $result) {
            if ($result['state'] !== PromiseInterface::FULFILLED) {
                continue;
            }
            /* @var Response $response */
            $response = $result['value'];
            // tugonline sends back empty images with status==200 sometimes, we can detect those by
            // checking if it includes a content-length header or not
            $hasContent = !empty($response->getHeader('content-length'));
            if ($response->getStatusCode() === 200 && $hasContent) {
                assert(isset($uris[$i]));

                return $webUrl.(string) $uris[$i];
            }
        }

        return null;
    }
}
