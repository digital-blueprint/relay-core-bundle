<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\ProxyApi;

use Dbp\Relay\CoreBundle\Exception\ApiError;
use Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractProxyDataEventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            ProxyDataEvent::class.'.'.static::getSubscribedNamespace() => 'onProxyDataEvent',
        ];
    }

    /**
     * @throws BadRequestException
     */
    public function onProxyDataEvent(ProxyDataEvent $event): void
    {
        $event->acknowledge();
        $proxyData = $event->getProxyData();
        $functionName = $proxyData->getFunctionName();
        $arguments = $proxyData->getArguments();
        $returnValue = null;

        $requiredFunctionArguments = static::getAvailableFunctionSignatures()[$functionName] ?? null;
        if ($requiredFunctionArguments === null) {
            throw new BadRequestException(sprintf('unknown function "%s" under namespace "%s"', $functionName, static::getSubscribedNamespace()));
        } elseif ($this->areAllRequiredArgumentsDefined($requiredFunctionArguments, array_keys($arguments)) === false) {
            throw new BadRequestException(sprintf('incomplete argument list for function "%s" under namespace "%s"', $functionName, static::getSubscribedNamespace()));
        }

        try {
            $returnValue = $this->callFunction($functionName, $arguments);
        } catch (Exception $exception) {
            $proxyData->setErrorsFromException($exception);
        }

        $proxyData->setData($returnValue);
    }

    /**
     * Must be overridden by deriving classes to indicate, which namespace they subscribe for.
     */
    protected static function getSubscribedNamespace(): string
    {
        throw ApiError::withDetails(Response::HTTP_INTERNAL_SERVER_ERROR, 'proxy data event subscribers must subscribe for a namespace');
    }

    /**
     * Must be overridden by deriving classes to indicate, which functions are available and which mandatory arguments they have. The format is a follows:
     * ['func1' => ['arg1', 'arg2'], 'func2' => []].
     */
    protected static function getAvailableFunctionSignatures(): array
    {
        throw ApiError::withDetails(Response::HTTP_INTERNAL_SERVER_ERROR, 'proxy data event subscribers must provide a list of available function signatures');
    }

    abstract protected function callFunction(string $functionName, array $arguments);

    private function areAllRequiredArgumentsDefined(array $requiredFunctionArguments, array $arguments): bool
    {
        return count(array_intersect($requiredFunctionArguments, $arguments)) === count($requiredFunctionArguments);
    }
}
