<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\Locale;

use Dbp\Relay\CoreBundle\Locale\Locale;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\HttpFoundation\RequestStack;

class TestLocale extends Locale
{
    private $primaryLanguage;

    public function __construct(string $primaryLanguage = 'en')
    {
        parent::__construct(new RequestStack(), new ParameterBag());

        $this->primaryLanguage = $primaryLanguage;
    }

    public function getCurrentPrimaryLanguage(): string
    {
        return $this->primaryLanguage;
    }
}
