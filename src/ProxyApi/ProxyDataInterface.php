<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\ProxyApi;

interface ProxyDataInterface
{
    public function getArguments(): array;

    public function getFunctionName(): ?string;

    public function setData($data);

    public function setErrorsFromException(\Exception $exception): void;
}
