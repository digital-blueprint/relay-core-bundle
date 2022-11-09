<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Locale;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * A service which can be injected, which provides the current active language and allows setting the active
 * language based on a query parameters.
 *
 * This assumes that Symfony is configured to apply the 'Accept-Language' header by default to all requests.
 */
class Locale
{
    /** @var RequestStack */
    private $requestStack;

    /**
     * @var ParameterBagInterface
     */
    private $parameters;

    public function __construct(RequestStack $requestStack, ParameterBagInterface $parameters)
    {
        $this->requestStack = $requestStack;
        $this->parameters = $parameters;
    }

    /**
     * Returns the primary language (in ISO 639‑1 format) for the current context.
     * In case there is a request then the request language, otherwise the default language.
     */
    public function getCurrentPrimaryLanguage(): string
    {
        $locale = $this->getCurrentLocale();
        $lang = \Locale::getPrimaryLanguage($locale);
        /** @psalm-suppress RedundantCondition */
        assert($lang !== null);

        return $lang;
    }

    /**
     * Sets the locale for the active request via a query parameter.
     * The query parameter format is the same as the 'Accept-Language' HTTP header format.
     * In case the query parameter isn't part of the request then nothing changes.
     */
    public function setCurrentRequestLocaleFromQuery(string $queryParam = 'lang'): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request === null) {
            throw new \RuntimeException('No active request');
        }
        self::setRequestLocaleFromQuery($request, $queryParam);
    }

    /**
     * Returns the current locale, either from the active request, or the default one.
     */
    private function getCurrentLocale(): string
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request !== null) {
            $locale = $request->getLocale();
        } else {
            $locale = $this->parameters->get('kernel.default_locale');
            assert(is_string($locale));
        }

        return $locale;
    }

    /**
     * Same as setCurrentRequestLocaleFromQuery(), but takes a request object.
     */
    public static function setRequestLocaleFromQuery(Request $request, string $queryParam): void
    {
        if ($request->query->has($queryParam)) {
            $lang = $request->query->get($queryParam);
            assert(is_string($lang));
            $locale = \Locale::acceptFromHttp($lang);
            if ($locale === false) {
                throw new \RuntimeException('Failed to parse Accept-Language');
            }
            $request->setLocale($locale);
        }
    }
}
