<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Authorization\ExpressionLanguage\ExpressionFunctionProviders;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

class PhpStringExpressionFunctionProvider implements ExpressionFunctionProviderInterface
{
    public function getFunctions(): array
    {
        return [
            ExpressionFunction::fromPhp('str_starts_with'),
            ExpressionFunction::fromPhp('str_ends_with'),
            ExpressionFunction::fromPhp('substr'),
            ExpressionFunction::fromPhp('strpos'),
            ExpressionFunction::fromPhp('strlen'),
        ];
    }
}
