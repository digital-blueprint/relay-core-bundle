<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests;

use Dbp\Relay\CoreBundle\Locale\Locale;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class LocaleTest extends TestCase
{
    public function testWithRequest()
    {
        $stack = new RequestStack();
        $request = new Request(['lang' => 'de']);

        $request->setLocale(\Locale::acceptFromHttp('en'));
        $stack->push($request);
        $params = new ParameterBag([]);
        $service = new Locale($stack, $params);

        $lang = $service->getCurrentPrimaryLanguage();
        $this->assertSame('en', $lang);

        $service->setCurrentRequestLocaleFromQuery('lang');
        $lang = $service->getCurrentPrimaryLanguage();
        $this->assertSame('de', $lang);
    }

    public function testWithoutRequest()
    {
        $stack = new RequestStack();
        $params = new ParameterBag(['kernel.default_locale' => \Locale::acceptFromHttp('de')]);
        $service = new Locale($stack, $params);

        $lang = $service->getCurrentPrimaryLanguage();
        $this->assertSame('de', $lang);
    }
}
