<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\Service;

use Adldap\Models\User;
use ApiPlatform\Core\Exception\ItemNotFoundException;
use DBP\API\CoreBundle\Entity\Organization;
use DBP\API\CoreBundle\Exception\ItemNotLoadedException;
use DBP\API\CoreBundle\Helpers\GuzzleTools;
use DBP\API\CoreBundle\Helpers\Tools;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
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
use SimpleXMLElement;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TUGOnlineApi implements OrganizationProviderInterface
{
    private $clientHandler;

    private $token;

    private $container;

    private $logger;

    private $config;

    private const CACHE_TTL = 3600;

    public function __construct(ContainerInterface $container, LoggerInterface $logger)
    {
        $this->config = $container->getParameter('dbp_api.core.co_config');
        $this->token = $this->config['api_token'] ?? '';
        $this->container = $container;
        $this->logger = $logger;
    }

    public function setApiKey(string $key)
    {
        $this->token = $key;
    }

    private function getClient(): Client
    {
        $stack = HandlerStack::create($this->clientHandler);
        $base_uri = $this->config['api_url_organization'];
        $client_options = [
            'base_uri' => $base_uri,
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
            $uriTemplate = new UriTemplate('https://online.tugraz.at/tug_online/visitenkarte.showImage?pPersonenGruppe={group}&pPersonenId={personId}');
            $uris[] = (string) $uriTemplate->expand([
                'group' => '3',
                'personId' => $bdId[0],
            ]);
        }

        $stId = $user->getAttribute('CO-OBFUSCATED-C-ST');
        if (in_array('STUDENTEN:OK', $accountTypes, true) && !empty($stId)) {
            $uriTemplate = new UriTemplate('https://online.tugraz.at/tug_online/visitenkarte.showImage?pPersonenGruppe={group}&pPersonenId={personId}');
            $uris[] = (string) $uriTemplate->expand([
                'group' => '5',
                'personId' => $stId[0],
            ]);
        }

        $extId = $user->getAttribute('CO-OBFUSCATED-C-EXT');
        if (in_array('ALUMNI:OK', $accountTypes, true) && !empty($extId)) {
            $uriTemplate = new UriTemplate('https://online.tugraz.at/tug_online/visitenkarte.showImage?pPersonenGruppe={group}&pPersonenId={personId}');
            $uris[] = (string) $uriTemplate->expand([
                'group' => '6',
                'personId' => $bdId[0],
            ]);
        }

        // Try out all URLs and return the first working one
        $client = $this->getClient();
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

                return (string) $uris[$i];
            }
        }

        return null;
    }

    /**
     * @throws UriException
     */
    public function getOrganizationUrlParameterString(string $identifier, string $lang = 'de'): string
    {
        $orgUnitId = $this->extractOrganizationID($identifier);

        // token is a mandatory url parameter, token via header doesn't work
        $uriTemplate = new UriTemplate('?token={token}&orgUnitID={orgUnitID}&language={lang}');

        return (string) $uriTemplate->expand([
            'token' => $this->token,
            'orgUnitID' => $orgUnitId,
            'lang' => $lang === 'en' ? 'en' : 'de',
        ]);
    }

    /**
     * Returns orgUnitId.
     */
    private function extractOrganizationID(string $identifier): string
    {
        $list = explode('-', $identifier);

        return $list[0];
    }

    /**
     * @throws ItemNotLoadedException
     */
    public function getOrganizationById(string $identifier, string $lang): Organization
    {
        // special handling for F2135 (Zentralbibliothek Wasser)
        if ($identifier === '1226_1231-F2135') {
            $organization = new Organization();
            $organization->setIdentifier($identifier);
            $organization->setName($lang === 'de' ? 'Zentralbibliothek Wasser' : 'Central library Water');
            $organization->setAlternateName('F2135');

            return $organization;
        }

        $xmlElement = $this->getOrganizationXMLData($identifier, $lang);
        $organization = $this->organizationFromXMLElement($identifier, $xmlElement);

        return $organization;
    }

    /**
     * @return SimpleXMLElement
     *
     * @throws ItemNotLoadedException
     */
    public function getOrganizationXMLData(string $identifier, string $lang = 'de'): ?SimpleXMLElement
    {
        $client = $this->getClient();
        $urlPath = $this->getOrganizationUrlParameterString($identifier, $lang);

        try {
            // http://docs.guzzlephp.org/en/stable/quickstart.html?highlight=get#making-a-request
            $response = $client->request('GET', $urlPath);

            $body = (string) $response->getBody();

            // try to handle xml errors
            if (strpos($body, '<?xml') === 0) {
                try {
                    $xml = new SimpleXMLElement($body);

                    // TODO: error handling for 404 and other errors

                    return $xml;
                } catch (\Exception $e) {
                    throw new ItemNotLoadedException(sprintf("Organization with id '%s' could not be loaded because of XML error! Message: %s", $identifier, Tools::filterErrorMessage($e->getMessage())));
                }
            } else {
                throw new ItemNotLoadedException(sprintf("Organization with id '%s' could not be loaded because result was no XML!", $identifier));
            }
        } catch (RequestException $e) {
            if ($e->getCode() === 401) {
                $message = $this->getOrganizationRequestExceptionMessage($e);

                switch ($message) {
                    case 'unauthorized resource access':
                        throw new ItemNotFoundException(sprintf("Organization with id '%s' could not be found!", $identifier));
                }
            }

            throw new ItemNotLoadedException(sprintf("Organization with id '%s' could not be loaded! Message: %s", $identifier, Tools::filterErrorMessage($e->getMessage())));
        } catch (GuzzleException $e) {
            throw new ItemNotLoadedException(sprintf("Organization with id '%s' could not be loaded! Message: %s", $identifier, Tools::filterErrorMessage($e->getMessage())));
        }
    }

    public function organizationFromXMLElement(string $identifier, SimpleXMLElement $xmlElement): Organization
    {
        $organization = new Organization();
        $organization->setIdentifier($identifier);

        $organization->setName(trim((string) ($xmlElement->xpath('/CDM/orgUnit/orgUnitName/text')[0] ?? '')));
        $organization->setAlternateName('F'.trim((string) ($xmlElement->xpath('/CDM/orgUnit/orgUnitCode')[0] ?? '')));
        $organization->setUrl(trim((string) ($xmlElement->xpath('/CDM/orgUnit/infoBlock/webLink/href')[0] ?? '')));

        return $organization;
    }

    /**
     * Handle json and xml Alma errors.
     */
    private function getOrganizationRequestExceptionMessage(RequestException $e): string
    {
        $body = $e->getResponse()->getBody();
        $content = $body->getContents();

        // try to handle xml errors
        if (strpos($content, '<?xml') === 0) {
            try {
                $xml = new \SimpleXMLElement($content);

                return Tools::filterErrorMessage((string) $xml->Message);
            } catch (\Exception $xmlException) {
                return Tools::filterErrorMessage($content);
            }
        }

        return Tools::filterErrorMessage($e->getMessage());
    }
}
