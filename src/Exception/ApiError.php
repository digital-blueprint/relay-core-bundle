<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * This is equal to a HttpException but the contained message will be serialized to
 * the client as jsonld/json, even for 500 errors. This is used to relay more detailed
 * error messages to the client for errors from third party systems for example.
 */
class ApiError extends HttpException
{
}
