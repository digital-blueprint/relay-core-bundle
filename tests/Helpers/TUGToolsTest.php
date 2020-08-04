<?php

namespace DBP\API\CoreBundle\Tests\Helpers;

use DBP\API\CoreBundle\Helpers\TUGTools;
use PHPUnit\Framework\TestCase;

class TUGToolsTest extends TestCase
{
    public function testFunctionsToRoles()
    {
        $this->assertEquals([], TUGTools::functionsToRoles([]));
        $this->assertEquals(['ROLE_F_EDV_F'], TUGTools::functionsToRoles(['F_EDV:F:95300:34886']));
    }

    public function testAccountTypesToRoles()
    {
        $this->assertEquals([], TUGTools::accountTypesToRoles([]));
        $this->assertEquals(['ROLE_STAFF', 'ROLE_STUDENT'], TUGTools::accountTypesToRoles(['BEDIENSTETE:OK', 'STUDENTEN:OK']));
        $this->assertEquals([], TUGTools::accountTypesToRoles(['FOOBAR']));
    }
}