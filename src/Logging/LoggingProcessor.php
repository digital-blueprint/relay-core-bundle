<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Logging;

use Dbp\Relay\CoreBundle\API\UserSessionInterface;
use Dbp\Relay\CoreBundle\Helpers\Tools as CoreTools;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Uid\Uuid;

final class LoggingProcessor
{
    private $userDataProvider;
    private $requestStack;

    public function __construct(UserSessionInterface $userDataProvider, RequestStack $requestStack)
    {
        $this->userDataProvider = $userDataProvider;
        $this->requestStack = $requestStack;
    }

    private function maskUserId(array &$record)
    {
        try {
            $userId = $this->userDataProvider->getUserIdentifier();
        } catch (\Throwable $error) {
            // pre-auth
            $userId = null;
        }

        if ($userId !== null) {
            Tools::maskValues($record, [$userId], '*****');
        }
    }

    public function __invoke(array $record)
    {
        // Try to avoid information leaks (users should still not log sensitive information though...)
        $record['message'] = CoreTools::filterErrorMessage($record['message']);

        // Mask the user identifier
        $this->maskUserId($record);

        // Add a session ID (the same during multiple requests for the same user session)
        $record['context']['relay-session-id'] = $this->userDataProvider->getSessionLoggingId();

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
}
