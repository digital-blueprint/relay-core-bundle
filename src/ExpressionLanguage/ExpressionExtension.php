<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\ExpressionLanguage;

/**
 * This Type gets injected into our expression language variant with the name
 * 'relay'. This allows us to add functions/methods with some kind of namespacing,
 * instead of polluting the global namespace.
 *
 * @internal
 */
class ExpressionExtension
{
    /**
     * @var ExpressionLanguage
     */
    private $lang;

    public function __construct(ExpressionLanguage $lang)
    {
        $this->lang = $lang;
    }

    public static function str_starts_with()
    {
        $args = func_get_args();

        return call_user_func_array('str_starts_with', $args);
    }

    public static function str_ends_with()
    {
        $args = func_get_args();

        return call_user_func_array('str_ends_with', $args);
    }

    public static function substr()
    {
        $args = func_get_args();

        return call_user_func_array('substr', $args);
    }

    public static function strpos()
    {
        $args = func_get_args();

        return call_user_func_array('strpos', $args);
    }

    public static function strlen()
    {
        $args = func_get_args();

        return call_user_func_array('strlen', $args);
    }

    public static function ceil()
    {
        $args = func_get_args();

        return call_user_func_array('ceil', $args);
    }

    public static function floor()
    {
        $args = func_get_args();

        return call_user_func_array('floor', $args);
    }

    public static function round()
    {
        $args = func_get_args();

        return call_user_func_array('round', $args);
    }

    public static function max()
    {
        $args = func_get_args();

        return call_user_func_array('max', $args);
    }

    public static function min()
    {
        $args = func_get_args();

        return call_user_func_array('min', $args);
    }

    public static function count()
    {
        $args = func_get_args();

        return call_user_func_array('count', $args);
    }

    public static function implode()
    {
        $args = func_get_args();

        return call_user_func_array('implode', $args);
    }

    public static function explode()
    {
        $args = func_get_args();

        return call_user_func_array('explode', $args);
    }

    public static function empty($value): bool
    {
        // empty is not a real function, so call_user_func_array doesn't work
        return empty($value);
    }

    public function map(iterable $iterable, string $expression): array
    {
        $transformedResult = [];
        foreach ($iterable as $key => $value) {
            $transformedResult[$key] = $this->lang->evaluate($expression, ['key' => $key, 'value' => $value]);
        }

        return $transformedResult;
    }

    public function filter(iterable $iterable, string $expression): array
    {
        $filteredResult = [];
        foreach ($iterable as $key => $value) {
            if ($this->lang->evaluate($expression, ['key' => $key, 'value' => $value])) {
                $filteredResult[] = $value;
            }
        }

        return $filteredResult;
    }
}
