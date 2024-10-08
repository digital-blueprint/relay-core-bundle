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
    public function __construct(private readonly bool $debug = false, private ?ResourceClassResolverInterface $resourceClassResolver = null)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object
    {
        if (!($request = $context['request'] ?? null)
            || !$operation instanceof HttpOperation
            || null === ($exception = $request->attributes->get('exception'))) {
            throw new \RuntimeException('Not an HTTP request');
        }

        if ($exception instanceof ApiError) {
            $apiError = $exception;
            $apiError->originalTrace = $exception->getTrace();
        } else {
            $status = $operation->getStatus() ?? 500;
            $apiError = ApiError::createFromException($exception, $status);
            if (!$this->debug && $status >= 500) {
                $apiError->setDetail('Internal Server Error'); // don't leak original exception message
            }
        }

        return $apiError;
    }
}
