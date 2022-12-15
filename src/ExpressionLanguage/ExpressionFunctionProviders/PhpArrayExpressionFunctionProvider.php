<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\ExpressionLanguage\ExpressionFunctionProviders;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

class PhpArrayExpressionFunctionProvider implements ExpressionFunctionProviderInterface
{
    public function getFunctions(): array
    {
        return [
            ExpressionFunction::fromPhp('count'),
            ExpressionFunction::fromPhp('implode'),
            ExpressionFunction::fromPhp('explode'),
        ];
    }
}
