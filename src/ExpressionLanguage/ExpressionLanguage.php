<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\ExpressionLanguage;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage as SymfonyExpressionLanguage;

class ExpressionLanguage extends SymfonyExpressionLanguage
{
    /**
     * @deprecated Use 'Relay' instead
     */
    private const DEPRECATE_RELAY_EXTENSION_VARIABLE_NAME = 'relay';
    private const RELAY_EXTENSION_VARIABLE_NAME = 'Relay';

    /* @var array */
    private $globalVariables;

    public function __construct(array $globalVariables = [], ?CacheItemPoolInterface $cache = null, array $providers = [])
    {
        parent::__construct($cache, $providers);

        $relayExtension = new ExpressionExtension($this);
        $globalVariables[self::RELAY_EXTENSION_VARIABLE_NAME] = $relayExtension;
        $globalVariables[self::DEPRECATE_RELAY_EXTENSION_VARIABLE_NAME] = $relayExtension;
        $this->globalVariables = $globalVariables;
    }

    public function evaluate($expression, array $values = []): mixed
    {
        return parent::evaluate($expression, array_merge($this->globalVariables, $values));
    }
}
