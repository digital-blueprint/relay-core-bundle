<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests;

use Dbp\Relay\CoreBundle\ExpressionLanguage\ExpressionLanguage;
use PHPUnit\Framework\TestCase;

class ExpressionLanguageTest extends TestCase
{
    public function testBasics()
    {
        $lang = new ExpressionLanguage();
        $this->assertTrue($lang->evaluate('true'));
        $this->assertFalse($lang->evaluate('false'));
    }

    public function testFilter()
    {
        $lang = new ExpressionLanguage();
        $this->assertSame([], $lang->evaluate('relay.filter([], "true")'));
        $this->assertSame([], $lang->evaluate('relay.filter([1, 5, 2, 4], "false")'));
        $this->assertSame([1, 5, 2, 4], $lang->evaluate('relay.filter([1, 5, 2, 4], "true")'));
        $this->assertSame([1, 5, 2, 4], $lang->evaluate('relay.filter([1, 5, 2, 4], "42")'));

        // preserve keys:
        $this->assertSame([1 => 5, 3 => 4], $lang->evaluate('relay.filter([1, 5, 2, 4], "value > 2", true)'));
        $this->assertSame([1 => 2, 3 => 4], $lang->evaluate('relay.filter({1: 2, 3: 4}, "true", true)'));
        $this->assertSame([1 => 5, 2 => 2, 3 => 4], $lang->evaluate('relay.filter([0.5, 5, 2, 4], "relay.floor(value)", true)'));

        // reorder:
        $this->assertSame([5, 4], $lang->evaluate('relay.filter([1, 5, 2, 4], "value > 2")'));
        $this->assertSame([2, 4], $lang->evaluate('relay.filter({1: 2, 3: 4}, "true")'));
        $this->assertSame([5, 2, 4], $lang->evaluate('relay.filter([0.5, 5, 2, 4], "relay.floor(value)")'));
    }

    public function testMap()
    {
        $lang = new ExpressionLanguage();
        $this->assertSame([], $lang->evaluate('relay.map([], "true")'));
        $this->assertSame([false], $lang->evaluate('relay.map([1], "false")'));
        $this->assertSame([2, 6, 3, 5], $lang->evaluate('relay.map([1, 5, 2, 4], "value + 1")'));
        $this->assertSame([1 => 3, 3 => 7], $lang->evaluate('relay.map( {1: 2, 3: 4}, "key + value")'));
        $this->assertSame([1 => 42, 3 => 42], $lang->evaluate('relay.map({1: 2, 3: 4}, "42")'));
        $this->assertSame([1.0], $lang->evaluate('relay.map([0.5], "relay.ceil(value)")'));
    }

    public function testEmpty()
    {
        $lang = new ExpressionLanguage();
        $this->assertTrue($lang->evaluate('relay.empty([])'));
        $this->assertTrue($lang->evaluate('relay.empty(0)'));
        $this->assertTrue($lang->evaluate('relay.empty("0")'));
        $this->assertFalse($lang->evaluate('relay.empty(42)'));
        $this->assertFalse($lang->evaluate('relay.empty([42])'));
    }

    public function testArray()
    {
        $lang = new ExpressionLanguage();
        $this->assertSame(2, $lang->evaluate('relay.count([1, 2])'));
        $this->assertSame('1-2', $lang->evaluate('relay.implode("-", ["1", "2"])'));
        $this->assertSame(['1', '2'], $lang->evaluate('relay.explode("-", "1-2")'));
        $this->assertTrue($lang->evaluate('relay.isNullOrEmptyArray(null)'));
        $this->assertTrue($lang->evaluate('relay.isNullOrEmptyArray([])'));
        $this->assertFalse($lang->evaluate('relay.isNullOrEmptyArray([1])'));
    }

    public function testNumeric()
    {
        $lang = new ExpressionLanguage();
        $this->assertSame(2.0, $lang->evaluate('relay.ceil(1.2)'));
        $this->assertSame(1.0, $lang->evaluate('relay.floor(1.9)'));
        $this->assertSame(1.0, $lang->evaluate('relay.round(0.5)'));
        $this->assertSame(42, $lang->evaluate('relay.max([2, 42])'));
        $this->assertSame(2, $lang->evaluate('relay.min([2, 42])'));
    }

    public function testString()
    {
        $lang = new ExpressionLanguage();
        $this->assertTrue($lang->evaluate('relay.str_starts_with("foo", "fo")'));
        $this->assertFalse($lang->evaluate('relay.str_starts_with("foo", "xo")'));
        $this->assertTrue($lang->evaluate('relay.str_ends_with("foo", "oo")'));
        $this->assertFalse($lang->evaluate('relay.str_ends_with("foo", "of")'));
        $this->assertSame('foo', $lang->evaluate('relay.substr("foobar", 0, 3)'));
        $this->assertSame(1, $lang->evaluate('relay.strpos("foobar", "oo")'));
        $this->assertSame(6, $lang->evaluate('relay.strlen("foobar")'));
        $this->assertFalse($lang->evaluate('relay.isNullOrEmptyString("foobar")'));
        $this->assertTrue($lang->evaluate('relay.isNullOrEmptyString(null)'));
        $this->assertTrue($lang->evaluate('relay.isNullOrEmptyString("")'));
        $this->assertFalse($lang->evaluate('relay.isNullOrEmptyString("1")'));
    }

    public function testOperators()
    {
        $lang = new ExpressionLanguage();
        $this->assertSame('foo', $lang->evaluate('relay.ternaryOperator(true, "foo", "bar")'));
        $this->assertSame('bar', $lang->evaluate('relay.ternaryOperator(false, "foo", "bar")'));
        $this->assertSame('foo', $lang->evaluate('relay.nullCoalescingOperator("foo", "bar")'));
        $this->assertSame('bar', $lang->evaluate('relay.nullCoalescingOperator(null, "bar")'));
    }

    public function testRegexFormat()
    {
        $lang = new ExpressionLanguage();
        $this->assertSame('bazbarfoo', $lang->evaluate('relay.regexFormat("/(foo)(bar)(baz)/", "foobarbaz", "%4$s%3$s%2$s")'));
        $this->assertSame('default', $lang->evaluate('relay.regexFormat("/(foo)(bar)(baz)/", "foobar", "%4$s%3$s%2$s", "default")'));
    }
}
