<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\ExpressionLanguage\ExpressionFunctionProviders;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class MapExpressionFunctionProvider implements ExpressionFunctionProviderInterface
{
    /** @var ExpressionLanguage */
    private $expressionLanguage;

    public function __construct(ExpressionLanguage $expressionLanguage)
    {
        $this->expressionLanguage = $expressionLanguage;
    }

    public function getFunctions(): array
    {
        return [
            new ExpressionFunction('map',
                function (string $expression, string $array): string {
                    return sprintf('map(%s, %s)', $expression, $array);
                },
                function ($arguments, ?string $expression, array $array): array {
                    if ($expression === null) {
                        $transformedArray = $array;
                    } else {
                        $transformedArray = [];
                        foreach ($array as $key => $value) {
                            $transformedArray[$key] = $this->expressionLanguage->evaluate($expression, ['key' => $key, 'value' => $value]);
                        }
                    }

                    return $transformedArray;
                }),
        ];
    }
}
