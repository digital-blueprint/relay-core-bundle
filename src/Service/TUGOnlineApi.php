<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\Service;

use Adldap\Models\User;
use ApiPlatform\Core\Exception\ItemNotFoundException;
use DBP\API\CoreBundle\Entity\Organization;
use DBP\API\CoreBundle\Exception\ItemNotLoadedException;
use DBP\API\CoreBundle\Helpers\Tools;
use DBP\API\CoreBundle\Keycloak\KeycloakBearerUser;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response;
use function GuzzleHttp\uri_template;
use Kevinrob\GuzzleCache\CacheMiddleware;
use Kevinrob\GuzzleCache\Storage\Psr6CacheStorage;
use Kevinrob\GuzzleCache\Strategy\GreedyCacheStrategy;
use Psr\Cache\CacheItemPoolInterface;
use SimpleXMLElement;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Security;

class TUGOnlineApi
{
    /**
     * @var Security
     */
    private $security;

    private $clientHandler;

    private $token;

    private $container;

    private $guzzleLogger;

    private $config;

    private const CACHE_TTL = 3600;

    public function __construct(ContainerInterface $container, Security $security, GuzzleLogger $guzzleLogger)
    {
        $this->config = $container->getParameter('dbp_api.core.co_config');
        $this->security = $security;
        $this->token = $this->config['api_token'];
        $this->container = $container;
        $this->guzzleLogger = $guzzleLogger;
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

        $stack->push($this->guzzleLogger->getClientHandler());

        $guzzeCachePool = $this->container->get('dbp_api.cache.core.campus_online');
        assert($guzzeCachePool instanceof CacheItemPoolInterface);

        $cacheMiddleWare = new CacheMiddleware(
            new GreedyCacheStrategy(
                new Psr6CacheStorage($guzzeCachePool),
                self::CACHE_TTL
            )
        );
        $cacheMiddleWare->setHttpMethods(['GET' => true, 'HEAD' => true]);
        $stack->push($cacheMiddleWare);

        $client = new Client($client_options);

        return $client;
    }

    public function getImageURLforUser(User $user): ?string
    {
        $uris = [];
        $accountTypes = $user->getAttribute('CO-ACCOUNTTYPE-STATUS-C') ?? [];

        $bdId = $user->getAttribute('CO-OBFUSCATED-C-BD');
        if (in_array('BEDIENSTETE:OK', $accountTypes, true) && !empty($bdId)) {
            $uris[] = uri_template('https://online.tugraz.at/tug_online/visitenkarte.showImage?pPersonenGruppe={group}&pPersonenId={personId}', [
                'group' => '3',
                'personId' => $bdId[0],
            ]);
        }

        $stId = $user->getAttribute('CO-OBFUSCATED-C-ST');
        if (in_array('STUDENTEN:OK', $accountTypes, true) && !empty($stId)) {
            $uris[] = uri_template('https://online.tugraz.at/tug_online/visitenkarte.showImage?pPersonenGruppe={group}&pPersonenId={personId}', [
                'group' => '5',
                'personId' => $stId[0],
            ]);
        }

        $extId = $user->getAttribute('CO-OBFUSCATED-C-EXT');
        if (in_array('ALUMNI:OK', $accountTypes, true) && !empty($extId)) {
            $uris[] = uri_template('https://online.tugraz.at/tug_online/visitenkarte.showImage?pPersonenGruppe={group}&pPersonenId={personId}', [
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

                return $uris[$i];
            }
        }

        return null;
    }

    public function getOrganizationUrlParameterString(string $identifier, string $lang = 'de'): string
    {
        $orgUnitId = $this->extractOrganizationID($identifier);

        // token is a mandatory url parameter, token via header doesn't work
        return uri_template('?token={token}&orgUnitID={orgUnitID}&language={lang}', [
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
     * @return Organization
     *
     * @throws ItemNotLoadedException
     */
    public function getOrganizationById(string $identifier, string $lang = 'de')
    {
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
                        break;
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

    /**
     * Checks if the current user has permissions to an organization.
     *
     * @param bool $throwException
     *
     * @throws AccessDeniedHttpException
     */
    public function checkOrganizationPermissions(Organization &$organization, $throwException = true): bool
    {
        /** @var KeycloakBearerUser $user */
        $user = $this->security->getUser();
        $institutes = $user->getInstitutesForGroup('F_BIB');
        $institute = $organization->getAlternateName();

        // check if current user has F_BIB permissions to the institute of the book offer
        if (!in_array($institute, $institutes, true)) {
            // throw an exception if we want to
            if ($throwException) {
                throw new AccessDeniedHttpException(sprintf("Person '%s' is not allowed to work with library '%s'!", $user->getUsername(), $institute));
            }
        } else {
            // return true if we are not throwing an exception
            if (!$throwException) {
                return true;
            }
        }

        // return false if we are not throwing an exception, otherwise true
        return $throwException;
    }
}
