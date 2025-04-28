<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Routing;

use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\RouteCollection;

class RoutingLoader extends Loader
{
    /**
     * @var ?ParameterBagInterface
     */
    private $params;

    public function __construct(?string $env = null, ?ParameterBagInterface $params = null)
    {
        parent::__construct($env);

        $this->params = $params;
    }

    public function load($resource, ?string $type = null): mixed
    {
        $routes = new RouteCollection();

        $routeResources = [];
        if ($this->params !== null && $this->params->has('dbp_api.route_resources')) {
            $routeResources = $this->params->get('dbp_api.route_resources');
            assert(is_array($routeResources));
        }

        foreach ($routeResources as [$resource, $type]) {
            $importedRoutes = $this->import($resource, $type);
            $routes->addCollection($importedRoutes);
        }

        return $routes;
    }

    public function supports($resource, ?string $type = null): bool
    {
        return 'dbp_relay' === $type;
    }
}
