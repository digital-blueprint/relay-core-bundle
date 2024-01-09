<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\ExpressionLanguage\ExpressionFunctionProviders;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class FilterExpressionFunctionProvider implements ExpressionFunctionProviderInterface
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
            new ExpressionFunction('filter',
                function (string $array, string $expression): string {
                    return sprintf('filter(%s, %s)', $array, $expression);
                },
                function ($arguments, array $array, string $expression = null): array {
                    $filteredResult = [];
                    foreach ($array as $key => $value) {
                        if ($expression !== null ? $this->expressionLanguage->evaluate($expression, ['key' => $key, 'value' => $value]) : !empty($value)) {
                            $filteredResult[$key] = $value;
                        }
                    }

                    return $filteredResult;
                }),
        ];
    }
}
