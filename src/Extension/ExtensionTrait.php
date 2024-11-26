<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Extension;

use Dbp\Relay\CoreBundle\Queue\Utils;
use Symfony\Component\DependencyInjection\ContainerBuilder;

trait ExtensionTrait
{
    /**
     * Registers a directory to be searched for api-platform resources.
     */
    public function addResourceClassDirectory(ContainerBuilder $container, string $directory): void
    {
        self::extendArrayParameter(
            $container, 'api_platform.resource_class_directories', [$directory]);
    }

    /**
     * Register a resource for routing, for example a config.yaml defining extra routes.
     * $resource is for example a path to a config.yaml and $type is "yaml", see LoaderInterface::load.
     */
    public function addRouteResource(ContainerBuilder $container, $resource, ?string $type = null): void
    {
        self::extendArrayParameter(
            $container, 'dbp_api.route_resources', [[$resource, $type]]);
    }

    /**
     * Registers a specific operation for an API path to be hidden from the API documentation.
     * Hides GET by default, $method can be one of GET, POST, DELETE, POST, PUT.
     */
    public function addPathToHide(ContainerBuilder $container, string $path, string $method = 'GET'): void
    {
        $allowed = ['GET', 'POST', 'DELETE', 'POST', 'PUT'];
        if (!in_array($method, $allowed, true)) {
            throw new \RuntimeException('Method can only be one of: '.implode(', ', $allowed));
        }
        self::extendArrayParameter($container, 'dbp_api.paths_to_hide', [[$path, $method]]);
    }

    /**
     * Registers a specific message type to be routed via the global async queue.
     */
    public function addQueueMessageClass(ContainerBuilder $container, string $messageClass): void
    {
        self::ensureInPrepend($container);
        self::extendArrayParameter($container, 'dbp_api.messenger_routing', [
            $messageClass => Utils::QUEUE_TRANSPORT_NAME,
        ]);
    }

    /**
     * Adds a header to Access-Control-Expose-Headers, so that scripts in browsers can access them.
     *
     * See https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Access-Control-Expose-Headers
     */
    public function addExposeHeader(ContainerBuilder $container, string $headerName)
    {
        self::ensureInPrepend($container);
        self::extendArrayParameter(
            $container, 'dbp_api.expose_headers', [$headerName]
        );
    }

    /**
     * Adds a header to Access-Control-Allow-Headers, so that scripts in browsers can send those headers.
     *
     * See https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Access-Control-Allow-Headers
     */
    public function addAllowHeader(ContainerBuilder $container, string $headerName)
    {
        self::ensureInPrepend($container);
        self::extendArrayParameter(
            $container, 'dbp_api.allow_headers', [$headerName]
        );
    }

    /**
     * Registers a Doctrine entity manager name. This can be used for managing database migrations etc.
     */
    public function registerEntityManager(ContainerBuilder $container, string $entityManagerName): void
    {
        self::ensureInPrepend($container);
        self::extendArrayParameter(
            $container, 'dbp_api.entity_managers', [$entityManagerName]
        );
    }

    private static function ensureInPrepend(ContainerBuilder $container): void
    {
        // Some things can only be called in prepend, so that the core bundle can forward them
        // to other bundles in prepend() as well.
        if ($container->hasParameter('dbp_api._prepend_done')) {
            throw new \RuntimeException('This function can only be called in prepend(). See PrependExtensionInterface');
        }
    }

    private static function extendArrayParameter(ContainerBuilder $container, string $parameter, array $values): void
    {
        if (!$container->hasParameter($parameter)) {
            $container->setParameter($parameter, []);
        }
        $oldValues = $container->getParameter($parameter);
        assert(is_array($oldValues));
        $container->setParameter($parameter, array_merge($oldValues, $values));
    }

    /**
     * @deprecated use addQueueMessageClass instead
     */
    public function addQueueMessage(ContainerBuilder $container, string $messageClass): void
    {
        $this->addQueueMessageClass($container, $messageClass);
    }

    /**
     * Registers a new channel with monolog.
     *
     * @param $mask - If false potential secrets and PII won't be masked from the logs. For example for audit logs.
     */
    public function registerLoggingChannel(ContainerBuilder $container, string $channelName, bool $mask = true): void
    {
        self::ensureInPrepend($container);
        self::extendArrayParameter(
            $container, 'dbp_api.logging_channels', [[$channelName, $mask]]
        );
    }
}
