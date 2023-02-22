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
        $this->assertSame([], $lang->evaluate('filter([], "true")'));
        $this->assertSame([], $lang->evaluate('filter([1, 5, 2, 4], "false")'));
        $this->assertSame([1, 5, 2, 4], $lang->evaluate('filter([1, 5, 2, 4], "true")'));
        $this->assertSame([1, 5, 2, 4], $lang->evaluate('filter([1, 5, 2, 4], "42")'));
        $this->assertSame([5, 4], $lang->evaluate('filter([1, 5, 2, 4], "value > 2")'));
        $this->assertSame([2, 4], $lang->evaluate('filter({1: 2, 3: 4}, "true")'));
        $this->assertSame([5, 2, 4], $lang->evaluate('filter([0.5, 5, 2, 4], "floor(value)")'));

        $this->assertSame([], $lang->evaluate('relay.filter([], "true")'));
        $this->assertSame([], $lang->evaluate('relay.filter([1, 5, 2, 4], "false")'));
        $this->assertSame([1, 5, 2, 4], $lang->evaluate('relay.filter([1, 5, 2, 4], "true")'));
        $this->assertSame([1, 5, 2, 4], $lang->evaluate('relay.filter([1, 5, 2, 4], "42")'));
        $this->assertSame([5, 4], $lang->evaluate('relay.filter([1, 5, 2, 4], "value > 2")'));
        $this->assertSame([2, 4], $lang->evaluate('relay.filter({1: 2, 3: 4}, "true")'));
        $this->assertSame([5, 2, 4], $lang->evaluate('relay.filter([0.5, 5, 2, 4], "relay.floor(value)")'));
    }

    public function testMap()
    {
        $lang = new ExpressionLanguage();
        $this->assertSame([], $lang->evaluate('map([], "true")'));
        $this->assertSame([false], $lang->evaluate('map([1], "false")'));
        $this->assertSame([2, 6, 3, 5], $lang->evaluate('map([1, 5, 2, 4], "value + 1")'));
        $this->assertSame([1 => 3, 3 => 7], $lang->evaluate('map({1: 2, 3: 4}, "key + value")'));
        $this->assertSame([1 => 42, 3 => 42], $lang->evaluate('map({1: 2, 3: 4}, "42")'));
        $this->assertSame([1.0], $lang->evaluate('map([0.5], "ceil(value)")'));

        $this->assertSame([], $lang->evaluate('relay.map([], "true")'));
        $this->assertSame([false], $lang->evaluate('relay.map([1], "false")'));
        $this->assertSame([2, 6, 3, 5], $lang->evaluate('relay.map([1, 5, 2, 4], "value + 1")'));
        $this->assertSame([1 => 3, 3 => 7], $lang->evaluate('relay.map({1: 2, 3: 4}, "key + value")'));
        $this->assertSame([1 => 42, 3 => 42], $lang->evaluate('relay.map({1: 2, 3: 4}, "42")'));
        $this->assertSame([1.0], $lang->evaluate('relay.map([0.5], "relay.ceil(value)")'));
    }

    public function testEmpty()
    {
        $lang = new ExpressionLanguage();
        $this->assertTrue($lang->evaluate('empty([])'));
        $this->assertTrue($lang->evaluate('empty(0)'));
        $this->assertTrue($lang->evaluate('empty("0")'));
        $this->assertFalse($lang->evaluate('empty(42)'));
        $this->assertFalse($lang->evaluate('empty([42])'));

        $this->assertTrue($lang->evaluate('relay.empty([])'));
        $this->assertTrue($lang->evaluate('relay.empty(0)'));
        $this->assertTrue($lang->evaluate('relay.empty("0")'));
        $this->assertFalse($lang->evaluate('relay.empty(42)'));
        $this->assertFalse($lang->evaluate('relay.empty([42])'));
    }

    public function testPhp()
    {
        $lang = new ExpressionLanguage();
        $this->assertSame(2, $lang->evaluate('count([1, 2])'));
        $this->assertSame('1-2', $lang->evaluate('implode("-", ["1", "2"])'));
        $this->assertSame(['1', '2'], $lang->evaluate('explode("-", "1-2")'));

        $this->assertSame(2, $lang->evaluate('relay.count([1, 2])'));
        $this->assertSame('1-2', $lang->evaluate('relay.implode("-", ["1", "2"])'));
        $this->assertSame(['1', '2'], $lang->evaluate('relay.explode("-", "1-2")'));
    }

    public function testNumeric()
    {
        $lang = new ExpressionLanguage();
        $this->assertSame(2.0, $lang->evaluate('ceil(1.2)'));
        $this->assertSame(1.0, $lang->evaluate('floor(1.9)'));
        $this->assertSame(1.0, $lang->evaluate('round(0.5)'));
        $this->assertSame(42, $lang->evaluate('max([2, 42])'));
        $this->assertSame(2, $lang->evaluate('min([2, 42])'));

        $this->assertSame(2.0, $lang->evaluate('relay.ceil(1.2)'));
        $this->assertSame(1.0, $lang->evaluate('relay.floor(1.9)'));
        $this->assertSame(1.0, $lang->evaluate('relay.round(0.5)'));
        $this->assertSame(42, $lang->evaluate('relay.max([2, 42])'));
        $this->assertSame(2, $lang->evaluate('relay.min([2, 42])'));
    }

    public function testString()
    {
        $lang = new ExpressionLanguage();
        $this->assertTrue($lang->evaluate('str_starts_with("foo", "fo")'));
        $this->assertFalse($lang->evaluate('str_starts_with("foo", "xo")'));
        $this->assertTrue($lang->evaluate('str_ends_with("foo", "oo")'));
        $this->assertFalse($lang->evaluate('str_ends_with("foo", "of")'));
        $this->assertSame('foo', $lang->evaluate('substr("foobar", 0, 3)'));
        $this->assertSame(1, $lang->evaluate('strpos("foobar", "oo")'));
        $this->assertSame(6, $lang->evaluate('strlen("foobar")'));

        $this->assertTrue($lang->evaluate('relay.str_starts_with("foo", "fo")'));
        $this->assertFalse($lang->evaluate('relay.str_starts_with("foo", "xo")'));
        $this->assertTrue($lang->evaluate('relay.str_ends_with("foo", "oo")'));
        $this->assertFalse($lang->evaluate('relay.str_ends_with("foo", "of")'));
        $this->assertSame('foo', $lang->evaluate('relay.substr("foobar", 0, 3)'));
        $this->assertSame(1, $lang->evaluate('relay.strpos("foobar", "oo")'));
        $this->assertSame(6, $lang->evaluate('relay.strlen("foobar")'));
    }
}
