<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\DependencyInjection;

use Dbp\Relay\CoreBundle\Auth\ProxyAuthenticator;
use Dbp\Relay\CoreBundle\DB\MigrateCommand;
use Dbp\Relay\CoreBundle\Extension\ExtensionTrait;
use Dbp\Relay\CoreBundle\Logging\LoggingProcessor;
use Dbp\Relay\CoreBundle\Queue\TestMessage;
use Dbp\Relay\CoreBundle\Queue\Utils as QueueUtils;
use Psr\Log\LogLevel;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

class DbpRelayCoreExtension extends ConfigurableExtension implements PrependExtensionInterface
{
    use ExtensionTrait;

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

        $this->addResourceClassDirectory($container, __DIR__.'/../Exception');
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
                'stateless' => true,
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
        ]);

        if (interface_exists('ApiPlatform\Metadata\ErrorResourceInterface')) {
            $container->prependExtensionConfig('api_platform', [
                'use_symfony_listeners' => true, // 4 only
                'serializer' => [
                    'hydra_prefix' => true,      // 4 only
                ],
            ]);
        } else {
            $container->prependExtensionConfig('api_platform', [
                'event_listeners_backward_compatibility_layer' => false, // 3 only
                'keep_legacy_inflector' => false, // 3 only
            ]);
        }

        $container->prependExtensionConfig('api_platform', [
            'formats' => [
                'jsonld' => ['application/ld+json'],
            ],
            'error_formats' => [
                'jsonld' => ['application/ld+json'],
            ],
            'docs_formats' => [
                'jsonld' => ['application/ld+json'],
                'jsonopenapi' => ['application/vnd.openapi+json'],
                'html' => ['text/html'],
            ],
            'defaults' => [
                'extra_properties' => [
                    'standard_put' => true,
                    'rfc_7807_compliant_errors' => true,
                ],
                'normalization_context' => [
                    'skip_null_values' => false,
                ],
            ],
            'path_segment_name_generator' => 'api_platform.metadata.path_segment_name_generator.dash',
        ]);

        // Improved logging defaults:
        // Starting with Symfony 7.0 it is recommended to enable php_errors.logs also in production.
        // There is no good reason to split up logs in any case, so force enable it.
        // See https://github.com/symfony/symfony/pull/51325 for the upstream change.
        // Additionally, with 7.1 the defaults for which errors get which logging levels was cleaned
        // up to match what leads to an error in the debug env, so backport that too by copying the mapping.
        // See https://github.com/symfony/symfony/pull/54046 for the upstream change.
        $loggerMapping = [
            \E_DEPRECATED => LogLevel::INFO,
            \E_USER_DEPRECATED => LogLevel::INFO,
            \E_NOTICE => LogLevel::ERROR,
            \E_USER_NOTICE => LogLevel::ERROR,
            \E_WARNING => LogLevel::ERROR,
            \E_USER_WARNING => LogLevel::ERROR,
            \E_COMPILE_WARNING => LogLevel::ERROR,
            \E_CORE_WARNING => LogLevel::ERROR,
            \E_USER_ERROR => LogLevel::CRITICAL,
            \E_RECOVERABLE_ERROR => LogLevel::CRITICAL,
            \E_COMPILE_ERROR => LogLevel::CRITICAL,
            \E_PARSE => LogLevel::CRITICAL,
            \E_ERROR => LogLevel::CRITICAL,
            \E_CORE_ERROR => LogLevel::CRITICAL,
        ];
        if (\PHP_VERSION_ID < 80400) {
            $loggerMapping[\E_STRICT] = LogLevel::ERROR;
        }
        $container->loadFromExtension('framework', [
            'php_errors' => [
                'log' => $loggerMapping,
            ],
        ]);

        $container->loadFromExtension('framework', [
            'router' => [
                'utf8' => true,
            ],
        ]);

        $container->loadFromExtension('security', [
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

        // Collect all registered messages for the routing config
        // https://symfony.com/doc/4.4/messenger.html#transports-async-queued-messages
        $messengerTransportDsn = $config['queue_dsn'];
        $messengerRouting = [];
        if ($container->hasParameter('dbp_api.messenger_routing')) {
            $messengerRouting = $container->getParameter('dbp_api.messenger_routing');
        }

        // If no transport is configured, we set a dummy one, so at least everything works (except messages will
        // never be handled). In the health check we error out if this is the case but there are messages to be routed.
        $unusedMessages = [];
        if ($messengerTransportDsn === '') {
            $messengerTransportDsn = 'in-memory://dummy-queue-not-configured';
            if ($messengerRouting) {
                $unusedMessages = array_keys($messengerRouting);
            }
        }
        $container->setParameter('dbp_api.messenger_unused_messages', $unusedMessages);

        $container->loadFromExtension('framework', [
            'messenger' => [
                'transports' => [
                    QueueUtils::QUEUE_TRANSPORT_NAME => [
                        'dsn' => $messengerTransportDsn,
                        'failure_transport' => QueueUtils::QUEUE_TRANSPORT_FAILED_NAME,
                    ],
                    QueueUtils::QUEUE_TRANSPORT_FAILED_NAME => $messengerTransportDsn,
                ],
                'routing' => [
                    TestMessage::class => QueueUtils::QUEUE_TRANSPORT_NAME,
                    ...$messengerRouting,
                ],
            ],
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
