<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\DependencyInjection;

use ApiPlatform\Core\Exception\FilterValidationException;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Exception\ItemNotFoundException;
use DBP\API\CoreBundle\Exception\ItemNotLoadedException;
use DBP\API\CoreBundle\Exception\ItemNotStoredException;
use DBP\API\CoreBundle\Exception\ItemNotUsableException;
use DBP\API\CoreBundle\Keycloak\KeycloakBearerAuthenticator;
use DBP\API\CoreBundle\Keycloak\KeycloakBearerUserProvider;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

class DbpCoreExtension extends ConfigurableExtension implements PrependExtensionInterface
{
    public function loadInternal(array $configs, ContainerBuilder $container)
    {
        $this->extendArrayParameter(
            $container, 'api_platform.resource_class_directories', [__DIR__.'/../Entity']);

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );
        $loader->load('services.yaml');

        $def = $container->register('dbp_api.cache.core.campus_online', FilesystemAdapter::class);
        $def->setArguments(['core-campus-online', 60, '%kernel.cache_dir%/dbp/core-campus-online']);
        $def->setPublic(true);
        $def->addTag('cache.pool');

        $def = $container->register('dbp_api.cache.core.keycloak_cert', FilesystemAdapter::class);
        $def->setArguments(['core-keycloak-cert', 60, '%kernel.cache_dir%/dbp/core-keycloak-cert']);
        $def->setPublic(true);
        $def->addTag('cache.pool');

        $def = $container->register('dbp_api.cache.core.auth_person', FilesystemAdapter::class);
        $def->setArguments(['core-auth-person', 60, '%kernel.cache_dir%/dbp/core-auth-person']);
        $def->setPublic(true);
        $def->addTag('cache.pool');

        $container->setParameter('dbp_api.core.keycloak_config', $configs['keycloak']);
        $container->setParameter('dbp_api.core.ldap_config', $configs['ldap']);
        $container->setParameter('dbp_api.core.co_config', $configs['campus_online']);
    }

    private function extendArrayParameter(ContainerBuilder $container, string $parameter, array $values)
    {
        if (!$container->hasParameter($parameter)) {
            $container->setParameter($parameter, []);
        }
        $oldValues = $container->getParameter($parameter);
        assert(is_array($oldValues));
        $container->setParameter($parameter, array_merge($oldValues, $values));
    }

    public function prepend(ContainerBuilder $container)
    {
        foreach (['api_platform', 'nelmio_cors', 'twig', 'security'] as $extKey) {
            if (!$container->hasExtension($extKey)) {
                throw new \Exception("'".$this->getAlias()."' requires the '$extKey' bundle to be loaded");
            }
        }

        $packageVersion = json_decode(
            file_get_contents(__DIR__.'/../../composer.json'), true)['version'];

        // FIXME: We need to get rid of our custom exceptions here and throw them manually in the controllers/providers
        $exceptionToStatus = [
            ItemNotFoundException::class => Response::HTTP_NOT_FOUND,

            // The 4 following handlers are registered by default, keep those lines to prevent unexpected side effects
            // TODO: can we get them programmatically somehow?
            ExceptionInterface::class => Response::HTTP_BAD_REQUEST,
            InvalidArgumentException::class => Response::HTTP_BAD_REQUEST,
            FilterValidationException::class => Response::HTTP_BAD_REQUEST,
            'Doctrine\ORM\OptimisticLockException' => Response::HTTP_CONFLICT,

            // These should be 5xx, but https://github.com/api-platform/core/issues/3659
            ItemNotStoredException::class => Response::HTTP_FAILED_DEPENDENCY,
            ItemNotLoadedException::class => Response::HTTP_FAILED_DEPENDENCY,
            ItemNotUsableException::class => Response::HTTP_FAILED_DEPENDENCY,
        ];

        $container->prependExtensionConfig('api_platform', [
            'version' => $packageVersion,
            'title' => 'DBP API Gateway',
            'http_cache' => [
                'etag' => true,
                'vary' => [
                    'Accept',
                    // Accept is default, Origin/ACRH/ACRM are for CORS requests
                    'Origin',
                    'Access-Control-Request-Headers',
                    'Access-Control-Request-Method',
                ],
            ],
            'show_webby' => false,
            'doctrine' => false, // TODO: should we change the default?,
            'swagger' => [
                'versions' => [3],
                'api_keys' => [
                    'apiKey' => [
                        'name' => 'Authorization',
                        'type' => 'header',
                    ],
                ],
            ],
            'exception_to_status' => $exceptionToStatus,
        ]);

        $container->loadFromExtension('security', [
            'providers' => [
                'keycloak_bearer_security_provider' => ['id' => KeycloakBearerUserProvider::class],
            ],
            'firewalls' => [
                'dev' => [
                    'pattern' => '^/(_(profiler|wdt)|css|images|js)/',
                    'security' => false,
                ],
                'swagger_documentation' => [
                    'pattern' => '^/$',
                    'anonymous' => true,
                ],
                'docs_jsonld' => [
                    'pattern' => '^/docs.jsonld',
                    'anonymous' => true,
                ],
                'api' => [
                    'pattern' => '^/',
                    'guard' => [
                        'provider' => 'keycloak_bearer_security_provider',
                        'authenticator' => KeycloakBearerAuthenticator::class,
                    ],
                ],
            ],
        ]);

        $container->loadFromExtension('nelmio_cors', [
            'paths' => [
                '^/' => [
                    'origin_regex' => true,
                    'allow_origin' => ['^.+$'],
                    'allow_methods' => ['GET', 'OPTIONS', 'POST', 'PUT', 'PATCH', 'DELETE'],
                    'allow_headers' => ['Content-Type', 'Authorization'],
                    // FIXME: Get rid of 'X-Analytics-Update-Date'
                    'expose_headers' => ['Link', 'X-Analytics-Update-Date'],
                    'max_age' => 3600,
                ],
            ],
        ]);

        $container->loadFromExtension('twig', [
            'paths' => [
                __DIR__.'/../Resources/ApiPlatformBundle' => 'ApiPlatform',
            ],
            'debug' => '%kernel.debug%',
            'strict_variables' => '%kernel.debug%',
            'exception_controller' => null,
        ]);

        $configs = $container->getExtensionConfig($this->getAlias());
        $keycloak = $configs[0]['keycloak'];
        $api_docs = $configs[0]['api_docs'];

        $container->loadFromExtension('twig', [
            'globals' => [
                'keycloak_server_url' => $keycloak['server_url'],
                'keycloak_realm' => $keycloak['realm'],
                'keycloak_frontend_client_id' => $api_docs['keycloak_client_id'],
                'app_buildinfo' => $api_docs['build_info'],
                'app_buildinfo_url' => $api_docs['build_info_url'],
                'app_env' => '%kernel.environment%',
                'app_debug' => '%kernel.debug%',
            ],
        ]);
    }
}
