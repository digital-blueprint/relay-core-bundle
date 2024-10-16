<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Exception;

use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\State\ProviderInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * @internal
 *
 * @implements ProviderInterface<object>
 */
#[AsAlias('api_platform.state.error_provider')]
#[AsTaggedItem('api_platform.state.error_provider')]
final class ErrorProvider implements ProviderInterface
{
    public function __construct(
        private readonly bool $debug,
        private readonly ?ResourceClassResolverInterface $resourceClassResolver = null)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object
    {
        if (!($request = $context['request'] ?? null)
            || !$operation instanceof HttpOperation
            || null === ($exception = $request->attributes->get('exception'))) {
            throw new \RuntimeException('Not an HTTP request');
        }

        if ($this->resourceClassResolver?->isResourceClass($exception::class)) {
            return $exception;
        }

        if ($exception instanceof ApiError) {
            $status = $exception->getStatusCode();
            $apiError = ApiErrorResource::createFromException($exception, $status);
            $apiError->setErrorId($exception->getErrorId());
            $apiError->setErrorDetails($exception->getErrorDetails());
        } else {
            $status = $operation->getStatus() ?? 500;
            $apiError = ApiErrorResource::createFromException($exception, $status);
            if (!$this->debug && $status >= 500) {
                $apiError->setDetail('Internal Server Error'); // don't leak original exception message
            }
        }

        return $apiError;
    }
}
