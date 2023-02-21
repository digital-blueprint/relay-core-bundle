<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\ExpressionLanguage;

use Dbp\Relay\CoreBundle\ExpressionLanguage\ExpressionFunctionProviders\ArrayExpressionFunctionProvider;
use Dbp\Relay\CoreBundle\ExpressionLanguage\ExpressionFunctionProviders\FilterExpressionFunctionProvider;
use Dbp\Relay\CoreBundle\ExpressionLanguage\ExpressionFunctionProviders\MapExpressionFunctionProvider;
use Dbp\Relay\CoreBundle\ExpressionLanguage\ExpressionFunctionProviders\PhpArrayExpressionFunctionProvider;
use Dbp\Relay\CoreBundle\ExpressionLanguage\ExpressionFunctionProviders\PhpNumericExpressionFunctionProvider;
use Dbp\Relay\CoreBundle\ExpressionLanguage\ExpressionFunctionProviders\PhpStringExpressionFunctionProvider;
use Dbp\Relay\CoreBundle\ExpressionLanguage\ExpressionFunctionProviders\StringExpressionFunctionProvider;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage as SymfonyExpressionLanguage;

class ExpressionLanguage extends SymfonyExpressionLanguage
{
    public function __construct(CacheItemPoolInterface $cache = null, array $providers = [])
    {
        $providers = array_merge([
            new FilterExpressionFunctionProvider($this),
            new MapExpressionFunctionProvider($this),
            new PhpArrayExpressionFunctionProvider(),
            new PhpNumericExpressionFunctionProvider(),
            new PhpStringExpressionFunctionProvider(),
            new ArrayExpressionFunctionProvider(),
            new StringExpressionFunctionProvider(),
        ], $providers);

        parent::__construct($cache, $providers);
    }
}
