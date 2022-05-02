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
        $this->extendArrayParameter(
            $container, 'api_platform.resource_class_directories', [$directory]);
    }

    /**
     * Register a resource for routing, for example a config.yaml defining extra routes.
     * $resource is for example a path to a config.yaml and $type is "yaml", see LoaderInterface::load.
     */
    public function addRouteResource(ContainerBuilder $container, $resource, ?string $type = null): void
    {
        $this->extendArrayParameter(
            $container, 'dbp_api.route_resources', [[$resource, $type]]);
    }

    /**
     * Registers a specific API path to be hidden from the API documentation.
     */
    public function addPathToHide(ContainerBuilder $container, string $path): void
    {
        $this->extendArrayParameter($container, 'dbp_api.paths_to_hide', [$path]);
    }

    /**
     * Registers a specific message type to be routed via the global async queue.
     */
    public function addQueueMessageClass(ContainerBuilder $container, string $messageClass): void
    {
        $this->ensureInPrepend($container);
        $this->extendArrayParameter($container, 'dbp_api.messenger_routing', [
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
        $this->ensureInPrepend($container);
        $this->extendArrayParameter(
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
        $this->ensureInPrepend($container);
        $this->extendArrayParameter(
            $container, 'dbp_api.allow_headers', [$headerName]
        );
    }

    private function ensureInPrepend(ContainerBuilder $container)
    {
        // Some things can only be called in prepend, so that the core bundle can forward them
        // to other bundles in prepend() as well.
        if ($container->hasParameter('dbp_api._prepend_done')) {
            throw new \RuntimeException('This function can only be called in prepend(). See PrependExtensionInterface');
        }
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

    /**
     * @deprecated use addQueueMessageClass instead
     */
    public function addQueueMessage(ContainerBuilder $container, string $messageClass): void
    {
        $this->addQueueMessageClass($container, $messageClass);
    }
}
