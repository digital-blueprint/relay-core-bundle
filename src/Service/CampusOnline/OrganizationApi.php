<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\Service\CampusOnline;

use ApiPlatform\Core\Exception\ItemNotFoundException;
use DBP\API\CoreBundle\Entity\Organization;
use DBP\API\CoreBundle\Entity\Person;
use DBP\API\CoreBundle\Exception\ItemNotLoadedException;
use DBP\API\CoreBundle\Helpers\GuzzleTools;
use DBP\API\CoreBundle\Helpers\Tools;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use Kevinrob\GuzzleCache\CacheMiddleware;
use Kevinrob\GuzzleCache\Storage\Psr6CacheStorage;
use Kevinrob\GuzzleCache\Strategy\GreedyCacheStrategy;
use League\Uri\Contracts\UriException;
use League\Uri\UriTemplate;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;
use Symfony\Component\DependencyInjection\ContainerInterface;

class OrganizationApi
{
    private $clientHandler;

    private $token;

    private $container;

    private $logger;

    private $config;

    private const CACHE_TTL = 3600;

    private $cache;

    public function __construct(ContainerInterface $container, LoggerInterface $logger)
    {
        $this->config = $container->getParameter('dbp_api.core.co_config');
        $this->token = $this->config['api_token'] ?? '';
        $this->container = $container;
        $this->logger = $logger;
        $this->cache = true;
    }

    public function setApiKey(string $key)
    {
        $this->token = $key;
    }

    public function setClientHandler(?object $handler)
    {
        $this->clientHandler = $handler;
    }

    public function enableCache(bool $enable)
    {
        $this->cache = $enable;
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
        if ($this->cache) {
            $stack->push($cacheMiddleWare);
        }

        $client = new Client($client_options);

        return $client;
    }

    /**
     * @throws UriException
     */
    public function getOrganizationUrlParameterString(string $identifier, string $lang = 'de'): string
    {
        $orgUnitId = $this->extractOrganizationID($identifier);

        // token is a mandatory url parameter, token via header doesn't work
        $uriTemplate = new UriTemplate('ws/webservice_v1.0/cdm/organization/xml?token={token}&orgUnitID={orgUnitID}&language={lang}');

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
        $client = $this->getClient($this->config['api_url'] ?? '');
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

    private function getOrganizationIDsByPerson(Person $person, string $context): array
    {
        if ($context === 'library-manager') {
            $group = 'F_BIB';
        } else {
            return [];
        }

        $group = preg_quote($group);
        $results = [];
        $re = "/^$group:F:(\d+):([\d_]+)$/i";

        $functions = $person->getExtraData('tug-functions');

        foreach ($functions as $function) {
            if (preg_match($re, $function, $matches)) {
                $results[] = $matches[2].'-F'.$matches[1];
            }
        }

        return $results;
    }

    public function getOrganizationsByPerson(Person $person, string $context, string $lang): array
    {
        $orgs = [];
        $ids = $this->getOrganizationIDsByPerson($person, $context);
        foreach ($ids as $id) {
            $orgs[] = $this->getOrganizationById($id, $lang);
        }

        return $orgs;
    }
}
