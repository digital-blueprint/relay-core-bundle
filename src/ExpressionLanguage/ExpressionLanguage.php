<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\ExpressionLanguage;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage as SymfonyExpressionLanguage;

class ExpressionLanguage extends SymfonyExpressionLanguage
{
    private const RELAY_EXTENSION_VARIABLE_NAE = 'relay';

    /* @var array */
    private $globalVariables;

    public function __construct(array $globalVariables = [], CacheItemPoolInterface $cache = null, array $providers = [])
    {
        parent::__construct($cache, $providers);

        $globalVariables[self::RELAY_EXTENSION_VARIABLE_NAE] = new ExpressionExtension($this);
        $this->globalVariables = $globalVariables;
    }

    /**
     * @return mixed
     */
    public function evaluate($expression, array $values = [])
    {
        return parent::evaluate($expression, array_merge($this->globalVariables, $values));
    }
}
