<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\Tests\Helpers;

use DBP\API\CoreBundle\Helpers\JsonException;
use DBP\API\CoreBundle\Helpers\Tools;
use PHPUnit\Framework\TestCase;

class ToolsTest extends TestCase
{
    public function testDecodeJSON()
    {
        $this->assertEquals([], Tools::decodeJSON('{}', true));
        $this->assertEquals(null, Tools::decodeJSON('null', true));
        $this->assertEquals(true, Tools::decodeJSON('true', true));
        $this->assertEquals(false, Tools::decodeJSON('false', true));
        $this->assertEquals(42, Tools::decodeJSON('42', true));
        $this->assertEquals([1, 2], Tools::decodeJSON('[1, 2]', true));
        $this->assertEquals(['foo' => 'bar'], Tools::decodeJSON('{"foo": "bar"}', true));

        $this->assertEquals(new \stdClass(), Tools::decodeJSON('{}', false));
        $this->assertEquals(42, Tools::decodeJSON('42', false));

        $invalid = ['', '[', '<xml>', '{', 'undefined'];
        foreach ($invalid as $input) {
            try {
                Tools::decodeJSON($input, true);
                $this->fail('not thrown');
            } catch (JsonException $e) {
            }
        }
    }
}
