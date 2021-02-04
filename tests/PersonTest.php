<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\Tests;

use DBP\API\CoreBundle\Entity\Person;
use PHPUnit\Framework\TestCase;

class PersonTest extends TestCase
{
    public function testExtraData()
    {
        $person = new Person();

        $person->setExtraData('foo', 42);
        $this->assertSame(42, $person->getExtraData('foo'));

        $person->setExtraData('foo', [1]);
        $this->assertSame([1], $person->getExtraData('foo'));

        $this->assertSame(null, $person->getExtraData('nope'));
    }

    public function testRoles()
    {
        $person = new Person();
        $this->assertSame([], $person->getRoles());
    }
}
