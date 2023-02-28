<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Helpers;

class Tools
{
    /**
     * Like json_decode but throws on invalid json data.
     *
     * @throws \JsonException
     *
     * @return mixed
     */
    public static function decodeJSON(string $json, bool $assoc = false)
    {
        $result = json_decode($json, $assoc);
        $json_error = json_last_error();
        if ($json_error !== JSON_ERROR_NONE) {
            throw new \JsonException(sprintf('%s: "%s"', json_last_error_msg(), print_r($json, true)));
        }

        return $result;
    }

    public static function filterErrorMessage(string $message): string
    {
        // hide token parameters
        return preg_replace('/([&?]token=)[\w\d-]+/i', '${1}hidden', $message);
    }

    public static function dumpTrace(...$moreVars)
    {
        $e = new \Exception();
        dump($e->getTraceAsString(), $moreVars);
    }

    public static function isNullOrEmpty(?string $str): bool
    {
        return $str === null || $str === '';
    }

    public static function pushToSubarray(array &$parentArray, $childArrayKey, $value): array
    {
        if (!isset($parentArray[$childArrayKey])) {
            $parentArray[$childArrayKey] = [$value];
        } else {
            $parentArray[$childArrayKey][] = $value;
        }

        return $parentArray;
    }

    /**
     * Removes all occurrences of @see $value in @see $targetArray.
     *
     * @param bool $doReindex         if @see true, array elements will be reindexed, otherwise they will remain unchanged
     * @param bool $doCompareStrictly if @see true, strict camparison (===) will be for the array search
     */
    public static function removeValueFromArray(array &$targetArray, $value, bool $doReindex = false, bool $doCompareStrictly = true): array
    {
        foreach (array_keys($targetArray, $value, $doCompareStrictly) as $key) {
            if ($doReindex) {
                array_splice($targetArray, $key, 1);
            } else {
                unset($targetArray[$key]);
            }
        }

        return $targetArray;
    }
}
