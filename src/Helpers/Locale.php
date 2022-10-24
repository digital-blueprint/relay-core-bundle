<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Helpers;

use Symfony\Component\HttpFoundation\RequestStack;

class Locale
{
    public const LANGUAGE_OPTION = 'lang';
    public const DEFAULT_LANGUAGE = 'de';

    /** @var RequestStack */
    private $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function addLanguageOption(array &$targetOptions)
    {
        $targetOptions[self::LANGUAGE_OPTION] = $this->getCurrentRequestLanguage();
    }

    public function getCurrentRequestLanguage(): string
    {
        return \Locale::getPrimaryLanguage($this->requestStack->getCurrentRequest()->getLanguages()[0] ?? self::DEFAULT_LANGUAGE);
    }
}
