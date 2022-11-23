<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Authorization\ExpressionLanguage\ExpressionFunctionProviders;

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
                function (string $iterableName, string $expression): string {
                    return sprintf('filter(%s, %s)', $iterableName, $expression);
                },
                function ($arguments, iterable $iterable, string $expression): array {
                    $filteredResult = [];
                    foreach ($iterable as $key => $value) {
                        if ($this->expressionLanguage->evaluate($expression, ['key' => $key, 'value' => $value])) {
                            $filteredResult[] = $value;
                        }
                    }

                    return $filteredResult;
                }),
        ];
    }
}
