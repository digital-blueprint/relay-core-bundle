<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\Service;

use DBP\API\CoreBundle\API\UserSessionInterface;
use DBP\API\CoreBundle\Helpers\Tools;

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
        $record['context']['dbp-id'] = $this->userDataProvider->getSessionLoggingId();

        return $record;
    }
}
