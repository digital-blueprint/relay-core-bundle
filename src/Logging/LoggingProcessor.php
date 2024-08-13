<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Logging;

use Dbp\Relay\CoreBundle\API\UserSessionInterface;
use Dbp\Relay\CoreBundle\Helpers\Tools as CoreTools;
use Monolog\Logger;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Uid\Uuid;

final class LoggingProcessor implements ProcessorInterface
{
    private $userDataProvider;
    private $requestStack;

    /**
     * @var array<string,bool>
     */
    private $maskConfig;

    private bool $processing;

    public function __construct(UserSessionInterface $userDataProvider, RequestStack $requestStack)
    {
        $this->userDataProvider = $userDataProvider;
        $this->requestStack = $requestStack;
        $this->processing = false;
    }

    /**
     * @param array<string,bool> $maskConfig
     */
    public function setMaskConfig(array $maskConfig): void
    {
        $this->maskConfig = $maskConfig;
    }

    private function maskUserId(array &$record)
    {
        $userId = $this->userDataProvider->getUserIdentifier();

        if ($userId !== null) {
            Tools::maskValues($record, [$userId], '*****');
        }
    }

    private function invokeLogArray(array $record): array
    {
        $isAuth = $this->userDataProvider->isAuthenticated();

        if ($this->maskConfig[$record['channel']] ?? true) {
            // Try to avoid information leaks (users should still not log sensitive information though...)
            $record['message'] = CoreTools::filterErrorMessage($record['message']);

            if ($isAuth) {
                // Mask the user identifier
                $this->maskUserId($record);
            }
        }

        if ($isAuth) {
            // Add a session ID (the same during multiple requests for the same user session)
            $record['context']['relay-session-id'] = $this->userDataProvider->getSessionLoggingId();
        }

        // Add a request ID (the same during the same client request)
        $request = $this->requestStack->getMainRequest();
        if ($request !== null) {
            $requestAttributeKey = 'relay-request-id';
            $requestId = $request->attributes->get($requestAttributeKey);
            if ($requestId === null) {
                $requestId = Uuid::v4()->toRfc4122();
                $request->attributes->set($requestAttributeKey, $requestId);
            }
            $record['context']['relay-request-id'] = $requestId;

            $route = $request->attributes->get('_route');
            if ($route !== null) {
                $record['context']['relay-route'] = $route;
            }
        }

        return $record;
    }

    /**
     * @return array|LogRecord
     */
    public function __invoke(array|LogRecord $record)
    {
        // Compat code to deal with Monolog v2 which passed an array and Monolog v2 which uses the LogRecord class
        // We convert to an array in all cases, then adjust the content, and convert back if needed.
        if (Logger::API !== 2) {
            assert($record instanceof LogRecord);
            $arrayRecord = $record->toArray();
        } else {
            $arrayRecord = $record;
        }

        if (!$this->processing) {
            // Ignore for any logs that are produced while we are in here,
            // otherwise we could end up with an infinite recursion
            $this->processing = true;
            try {
                $arrayRecord = $this->invokeLogArray($arrayRecord);
            } finally {
                $this->processing = false;
            }
        }

        if (Logger::API === 2) {
            return $arrayRecord;
        } else {
            /** @psalm-suppress InterfaceInstantiation */
            return new LogRecord(
                $arrayRecord['datetime'], $arrayRecord['channel'], Logger::toMonologLevel($arrayRecord['level']),
                $arrayRecord['message'], $arrayRecord['context'], $arrayRecord['extra'],
                $record->formatted);
        }
    }
}
