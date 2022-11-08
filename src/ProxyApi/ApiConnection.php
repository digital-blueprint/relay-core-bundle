<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\ProxyApi;

use Dbp\Relay\CoreBundle\Helpers\Tools;
use Dbp\Relay\CoreBundle\Http\ApiConnection as BaseApiConnection;
use Dbp\Relay\CoreBundle\Http\ConnectionException as BaseConnectionException;

class ApiConnection extends BaseApiConnection
{
    private const NAMESPACE_PARAMETER_NAME = 'namespace';
    private const FUNCTION_NAME_PARAMETER_NAME = 'functionName';
    private const ARGUMENTS_PARAMETER_NAME = 'arguments';

    private const PROXY_DATA_URI = 'proxy/proxydata';

    /**
     * @throws ConnectionException
     */
    public function callFunction(string $namespace, string $functionName, array $arguments = [])
    {
        $parameters = [
            self::NAMESPACE_PARAMETER_NAME => $namespace,
            self::FUNCTION_NAME_PARAMETER_NAME => $functionName,
            self::ARGUMENTS_PARAMETER_NAME => $arguments,
        ];

        $responseBody = (string) $this->postJSON(self::PROXY_DATA_URI, $parameters)->getBody();

        try {
            $proxyData = Tools::decodeJSON($responseBody, true);
        } catch (\JsonException $exception) {
            throw new ConnectionException('failed to JSON decode API response: '.$exception->getMessage(), BaseConnectionException::JSON_EXCEPTION);
        }

        try {
            $errors = $proxyData[ProxyApi::PROXY_DATA_ERRORS_KEY];
            $returnValue = $proxyData[ProxyApi::PROXY_DATA_RETURN_VALUE_KEY];
        } catch (\Exception $exception) {
            throw new ConnectionException('API returned invalid ProxyData object', BaseConnectionException::INVALID_DATA_EXCEPTION);
        }

        if (!empty($errors)) {
            $topLevelError = $errors[0];
            throw new ConnectionException(sprintf('call to API function "%s" under namespace "%s" resulted in an error: %s (code: %s)', $functionName, $namespace, $topLevelError['message'] ?? 'message not available', $topLevelError['code'] ?? 'code not available'), ConnectionException::API_ERROR);
        }

        return $returnValue;
    }
}
