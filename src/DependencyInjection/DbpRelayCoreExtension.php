<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\DependencyInjection;

use Dbp\Relay\CoreBundle\Auth\ProxyAuthenticator;
use Dbp\Relay\CoreBundle\DB\MigrateCommand;
use Dbp\Relay\CoreBundle\Logging\LoggingProcessor;
use Dbp\Relay\CoreBundle\Queue\TestMessage;
use Dbp\Relay\CoreBundle\Queue\Utils as QueueUtils;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpFoundation\Session\SessionFactoryInterface;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

class DbpRelayCoreExtension extends ConfigurableExtension implements PrependExtensionInterface
{
    public function loadInternal(array $mergedConfig, ContainerBuilder $container): void
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

        $definition = $container->getDefinition(MigrateCommand::class);
        $entityManagers = [];
        if ($container->hasParameter('dbp_api.entity_managers')) {
            $entityManagers = $container->getParameter('dbp_api.entity_managers');
        }
        $definition->addMethodCall('setEntityManagers', [$entityManagers]);

        $definition = $container->getDefinition(LoggingProcessor::class);
        $definition->addMethodCall('setMaskConfig', [self::getLoggingChannels($container)]);
    }

    /**
     * Gives a mapping of all register logging channels.
     *
     * @return array<string,bool>
     */
    private static function getLoggingChannels(ContainerBuilder $container): array
    {
        $channels = [];
        if ($container->hasParameter('dbp_api.logging_channels')) {
            $data = $container->getParameter('dbp_api.logging_channels');

            foreach ($data as $entry) {
                $name = $entry[0];
                $mask = $entry[1];
                $channels[$name] = $mask;
            }
        }

        return $channels;
    }

    public function prepend(ContainerBuilder $container): void
    {
        foreach (['api_platform', 'nelmio_cors', 'twig', 'security', 'framework', 'monolog'] as $extKey) {
            if (!$container->hasExtension($extKey)) {
                throw new \Exception("'".$this->getAlias()."' requires the '$extKey' bundle to be loaded");
            }
        }

        $configs = $container->getExtensionConfig($this->getAlias());
        $config = $this->processConfiguration(new Configuration(), $configs);

        $container->prependExtensionConfig('api_platform', [
            'title' => $config['docs_title'],
            'collection' => [
                'pagination' => [
                    'items_per_page_parameter_name' => 'perPage',
                ],
            ],
            'description' => $config['docs_description'],
            'defaults' => [
                // This enables it for the doctrine integration, which we don't actually use.
                // But it also adds it to the open-api docs which need because we implement it manually
                // in the controllers and providers, so enable it anyway.
                'pagination_client_items_per_page' => true,
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
            'metadata_backward_compatibility_layer' => false,
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
                        ProxyAuthenticator::class,
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
                    // edit: this is fixed in Apache since 2.4.48, so we can drop this line once we require that version
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
                'logo_path' => $config['logo_path'] ?? 'bundles/dbprelaycore/logo.png',
                'favicon_path' => $config['favicon_path'] ?? 'bundles/dbprelaycore/apple-touch-icon.png',
                'app_env' => '%kernel.environment%',
                'app_debug' => '%kernel.debug%',
            ]),
        ]);

        // Register extra bundle logging channels
        $container->loadFromExtension('monolog', [
            'channels' => array_keys(self::getLoggingChannels($container)),
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
        $unusedMessages = [];
        if ($container->hasParameter('dbp_api.messenger_routing')) {
            $extraRouting = $container->getParameter('dbp_api.messenger_routing');
            $routing = array_merge($routing, $extraRouting);

            if ($messengerTransportDsn === '') {
                $unusedMessages = array_keys($extraRouting);
            }
        } else {
            // By always setting a transport, we ensure that the messenger commands work in all cases, even if they
            // are not stricly needed
            if ($messengerTransportDsn === '') {
                $messengerTransportDsn = 'in-memory://dummy-queue-not-configured';
            }
        }
        $container->setParameter('dbp_api.messenger_unused_messages', $unusedMessages);

        $messengerConfig = [
            'transports' => [
                QueueUtils::QUEUE_TRANSPORT_NAME => $messengerTransportDsn,
            ],
            'routing' => $routing,
        ];

        // Symfony 5.4+
        // https://symfony.com/blog/new-in-symfony-5-4-messenger-improvements
        if (interface_exists(SessionFactoryInterface::class)) {
            $messengerConfig['reset_on_message'] = true;
        }

        $container->loadFromExtension('framework', [
            'messenger' => $messengerConfig,
        ]);

        // https://symfony.com/doc/5.3/components/lock.html
        $lockDsn = $config['lock_dsn'];
        if ($lockDsn !== '') {
            $container->loadFromExtension('framework', [
                'lock' => $lockDsn,
            ]);
        }

        // Set the locale via Accept-Language, this also makes
        // Symfony add Accept-Language to the Vary header.
        $container->loadFromExtension('framework', [
            'default_locale' => 'en',
            // https://github.com/symfony/symfony/issues/47355
            'enabled_locales' => ['en', 'de'],
            'set_locale_from_accept_language' => true,
        ]);

        // Since the core bundle should always be called last we can use this to detect if
        // things are called after this by checking if this exist.
        $container->setParameter('dbp_api._prepend_done', true);
    }
}
