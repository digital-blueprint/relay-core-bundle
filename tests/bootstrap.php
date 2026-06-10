<?php

declare(strict_types=1);

use Symfony\Component\ErrorHandler\ErrorHandler;

require dirname(__DIR__).'/vendor/autoload.php';

set_exception_handler([new ErrorHandler(), 'handleException']);

if (file_exists(dirname(__DIR__).'/config/bootstrap.php')) {
    require dirname(__DIR__).'/config/bootstrap.php';
}
