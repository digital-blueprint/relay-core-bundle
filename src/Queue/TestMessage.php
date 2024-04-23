<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Queue;

class TestMessage
{
    public function __construct(public bool $fail = false, public int $number = 1)
    {
    }
}
