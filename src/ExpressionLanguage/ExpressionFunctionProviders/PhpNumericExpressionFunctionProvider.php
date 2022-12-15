<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\ExpressionLanguage\ExpressionFunctionProviders;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

class PhpNumericExpressionFunctionProvider implements ExpressionFunctionProviderInterface
{
    public function getFunctions(): array
    {
        return [
            ExpressionFunction::fromPhp('ceil'),
            ExpressionFunction::fromPhp('floor'),
            ExpressionFunction::fromPhp('round'),
            ExpressionFunction::fromPhp('max'),
            ExpressionFunction::fromPhp('min'),
        ];
    }
}
