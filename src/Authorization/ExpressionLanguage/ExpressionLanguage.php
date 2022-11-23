<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Authorization\ExpressionLanguage;

use Dbp\Relay\CoreBundle\Authorization\ExpressionLanguage\ExpressionFunctionProviders\FilterExpressionFunctionProvider;
use Dbp\Relay\CoreBundle\Authorization\ExpressionLanguage\ExpressionFunctionProviders\MapExpressionFunctionProvider;
use Dbp\Relay\CoreBundle\Authorization\ExpressionLanguage\ExpressionFunctionProviders\PhpArrayExpressionFunctionProvider;
use Dbp\Relay\CoreBundle\Authorization\ExpressionLanguage\ExpressionFunctionProviders\PhpNumericExpressionFunctionProvider;
use Dbp\Relay\CoreBundle\Authorization\ExpressionLanguage\ExpressionFunctionProviders\PhpStringExpressionFunctionProvider;
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
        ], $providers);

        parent::__construct($cache, $providers);
    }
}
