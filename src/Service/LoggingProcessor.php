<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Service;

use Dbp\Relay\CoreBundle\API\UserSessionInterface;
use Dbp\Relay\CoreBundle\Helpers\Tools;

class LoggingProcessor
{
    private $userDataProvider;

    public function __construct(UserSessionInterface $userDataProvider)
    {
        $this->userDataProvider = $userDataProvider;
    }

    public function __invoke(array $record)
    {
        // Try to avoid information leaks (users should still not log sensitive information though...)
        $record['message'] = Tools::filterErrorMessage($record['message']);

        // Add some default context (session ID etc)
        $loggingId = $this->userDataProvider->getSessionLoggingId();
        if ($loggingId !== null) {
            $record['context']['dbp-id'] = $loggingId;
        }

        return $record;
    }
}
