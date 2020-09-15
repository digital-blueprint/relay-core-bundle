<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\Tests\Helpers;

use DBP\API\CoreBundle\Helpers\TUGTools;
use PHPUnit\Framework\TestCase;

class TUGToolsTest extends TestCase
{
    public function testFunctionsToRoles()
    {
        $this->assertEquals([], TUGTools::functionsToRoles([]));
        $this->assertEquals(['ROLE_LIBRARY_MANAGER', 'ROLE_F_BIB_F'], TUGTools::functionsToRoles(['F_BIB:F:1490:681']));
        $this->assertEquals([], TUGTools::functionsToRoles(['F_FOO:D:1490:681']));
    }

    public function testAccountTypesToRoles()
    {
        $this->assertEquals([], TUGTools::accountTypesToRoles([]));
        $this->assertEquals(['ROLE_STAFF', 'ROLE_STUDENT'], TUGTools::accountTypesToRoles(['BEDIENSTETE:OK', 'STUDENTEN:OK']));
        $this->assertEquals([], TUGTools::accountTypesToRoles(['FOOBAR']));
    }
}
