<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\ExpressionLanguage\ExpressionFunctionProviders;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

class ArrayExpressionFunctionProvider implements ExpressionFunctionProviderInterface
{
    public function getFunctions(): array
    {
        return [
            new ExpressionFunction('empty',
                function (string $varName): string {
                    return sprintf('empty(%s)', $varName);
                },
                function ($arguments, $varName): bool {
                    return empty($varName);
                }),
            new ExpressionFunction('array_key_exists',
                function (string $keyName, string $arrayName): string {
                    return sprintf('array_key_exists(%s, %s)', $keyName, $arrayName);
                },
                function ($arguments, $keyName, $arrayName): bool {
                    return array_key_exists($keyName, $arrayName);
                }),
        ];
    }
}
