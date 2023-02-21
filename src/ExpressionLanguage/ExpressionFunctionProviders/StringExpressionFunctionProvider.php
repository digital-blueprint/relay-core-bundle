<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\ExpressionLanguage\ExpressionFunctionProviders;

use Dbp\Relay\CoreBundle\Helpers\Tools;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

class StringExpressionFunctionProvider implements ExpressionFunctionProviderInterface
{
    public function getFunctions(): array
    {
        return [
            new ExpressionFunction('isNullOrEmpty',
                function (string $varName): string {
                    return sprintf('isNullOrEmpty(%s)', $varName);
                },
                function ($arguments, $varName): bool {
                    return Tools::isNullOrEmpty($varName);
                }),
        ];
    }
}
