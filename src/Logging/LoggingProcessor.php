<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Logging;

use Dbp\Relay\CoreBundle\API\UserSessionInterface;
use Dbp\Relay\CoreBundle\Helpers\Tools as CoreTools;

final class LoggingProcessor
{
    private $userDataProvider;

    public function __construct(UserSessionInterface $userDataProvider)
    {
        $this->userDataProvider = $userDataProvider;
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

        // Add some default context (session ID etc)
        $loggingId = $this->userDataProvider->getSessionLoggingId();
        if ($loggingId !== null) {
            $record['context']['dbp-id'] = $loggingId;
        }

        return $record;
    }
}
