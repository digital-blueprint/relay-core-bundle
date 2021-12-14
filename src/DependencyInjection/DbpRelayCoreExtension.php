<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\DependencyInjection;

use Dbp\Relay\CoreBundle\Queue\TestMessage;
use Dbp\Relay\CoreBundle\Queue\Utils as QueueUtils;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;

class DbpRelayCoreExtension extends ConfigurableExtension implements PrependExtensionInterface
{
    public function loadInternal(array $mergedConfig, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );
        $loader->load('services.yaml');

        // Pass the collected paths that need to be hidden to the OpenApiDecorator
        $definition = $container->getDefinition('Dbp\Relay\CoreBundle\Swagger\OpenApiDecorator');
        if ($container->hasParameter('dbp_api.paths_to_hide')) {
            $definition->addMethodCall('setPathsToHide', [$container->getParameter('dbp_api.paths_to_hide')]);
        }

        $cronCacheDef = $container->register('dbp.relay.cache.core.cron', FilesystemAdapter::class);
        $cronCacheDef->setArguments(['core-cron', 0, '%kernel.cache_dir%/dbp/relay/core-cron']);
        $cronCacheDef->addTag('cache.pool');

        $definition = $container->getDefinition('Dbp\Relay\CoreBundle\Cron\CronCommand');
        $definition->addMethodCall('setCache', [$cronCacheDef]);
    }

    public function prepend(ContainerBuilder $container)
    {
        foreach (['api_platform', 'nelmio_cors', 'twig', 'security', 'framework'] as $extKey) {
            if (!$container->hasExtension($extKey)) {
                throw new \Exception("'".$this->getAlias()."' requires the '$extKey' bundle to be loaded");
            }
        }

        $configs = $container->getExtensionConfig($this->getAlias());
        $config = $this->processConfiguration(new Configuration(), $configs);

        $container->prependExtensionConfig('api_platform', [
            'title' => $config['docs_title'],
            'description' => $config['docs_description'],
            'defaults' => [
                'cache_headers' => [
                    'etag' => true,
                    'vary' => [
                        'Accept',
                        // Accept is default, Origin/ACRH/ACRM are for CORS requests
                        'Origin',
                        'Access-Control-Request-Headers',
                        'Access-Control-Request-Method',
                    ],
                ],
            ],
            'show_webby' => false,
            'doctrine' => false,
            'swagger' => [
                'versions' => [3],
                'api_keys' => [
                    'apiKey' => [
                        'name' => 'Authorization',
                        'type' => 'header',
                    ],
                ],
            ],
            'path_segment_name_generator' => 'api_platform.path_segment_name_generator.dash',
        ]);

        $container->loadFromExtension('framework', [
            'router' => [
                'utf8' => true,
            ],
        ]);

        $container->loadFromExtension('security', [
            'enable_authenticator_manager' => true,
            'firewalls' => [
                'dev' => [
                    'pattern' => '^/(_(profiler|wdt)|css|images|js)/',
                    'security' => false,
                ],
                'swagger_documentation' => [
                    'pattern' => '^/($|index.(json|jsonld|html)$)',
                ],
                'docs_jsonld' => [
                    'pattern' => '^/docs.(json|jsonld)$',
                ],
                'api' => [
                    'pattern' => '^/',
                    'lazy' => true,
                    'custom_authenticators' => [
                        AuthenticatorInterface::class,
                    ],
                ],
            ],
        ]);

        $exposeHeaders = ['Link'];
        $exposeHeadersKey = 'dbp_api.expose_headers';
        // Allow other bundles to add more exposed headers
        if ($container->hasParameter($exposeHeadersKey)) {
            $exposeHeaders = array_merge($exposeHeaders, $container->getParameter($exposeHeadersKey));
        }

        $allowHeaders = ['Content-Type', 'Authorization'];
        $allowHeadersKey = 'dbp_api.allow_headers';
        // Allow other bundles to add more allowed headers
        if ($container->hasParameter($allowHeadersKey)) {
            $allowHeaders = array_merge($allowHeaders, $container->getParameter($allowHeadersKey));
        }

        $container->loadFromExtension('nelmio_cors', [
            'paths' => [
                '^/' => [
                    'origin_regex' => true,
                    'allow_origin' => ['^.+$'],
                    'allow_methods' => ['GET', 'OPTIONS', 'POST', 'PUT', 'PATCH', 'DELETE'],
                    'allow_headers' => $allowHeaders,
                    'expose_headers' => $exposeHeaders,
                    'max_age' => 3600,
                    // XXX: Try to work around a bug in Apache, which strips CORS headers from 304 responses:
                    // https://bz.apache.org/bugzilla/show_bug.cgi?id=51223
                    // In case the browser has a response cached from another origin, it will send the same etag,
                    // Apache returns with a 304 without cors headers, the browser serves the cached request with
                    // wrong 'access-control-allow-origin' and the fetch will fail with a CORS error.
                    // By always sending '*' the cached response still happens to be valid in that case.
                    'forced_allow_origin_value' => '*',
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

        // In case another bundle wants to inject twig globals
        $twigGlobals = [];
        if ($container->hasParameter('dbp_api.twig_globals')) {
            $twigGlobals = $container->getParameter('dbp_api.twig_globals');
        }

        $container->loadFromExtension('twig', [
            'globals' => array_merge($twigGlobals, [
                'app_buildinfo' => $config['build_info'] ?? '',
                'app_buildinfo_url' => $config['build_info_url'] ?? '',
                'app_env' => '%kernel.environment%',
                'app_debug' => '%kernel.debug%',
            ]),
        ]);

        $routing = [
            TestMessage::class => QueueUtils::QUEUE_TRANSPORT_NAME,
        ];

        // https://symfony.com/doc/4.4/messenger.html#transports-async-queued-messages
        $messengerTransportDsn = $config['queue_dsn'];
        if ($messengerTransportDsn === '') {
            // backward compatibility
            $messengerTransportDsn = $config['messenger_transport_dsn'];
        }
        if ($container->hasParameter('dbp_api.messenger_routing')) {
            $routing = array_merge($routing, $container->getParameter('dbp_api.messenger_routing'));

            if ($messengerTransportDsn === '') {
                throw new \RuntimeException('A bundle requires a worker queue: set "queue_dsn" in the "dbp_relay_core" bundle config');
            }
        } else {
            // By always setting a transport, we ensure that the messenger commands work in all cases, even if they
            // are not stricly needed
            if ($messengerTransportDsn === '') {
                $messengerTransportDsn = 'in-memory://dummy-queue-not-configured';
            }
        }

        $container->loadFromExtension('framework', [
            'messenger' => [
                'transports' => [
                    QueueUtils::QUEUE_TRANSPORT_NAME => $messengerTransportDsn,
                ],
                'routing' => $routing,
            ],
        ]);

        // https://symfony.com/doc/5.3/components/lock.html
        $lockDsn = $config['lock_dsn'];
        if ($lockDsn !== '') {
            $container->loadFromExtension('framework', [
                'lock' => $lockDsn,
            ]);
        }
    }
}
