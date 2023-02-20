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

    public static function pushToSubarray(array &$parentArray, $childArrayKey, $value)
    {
        if (!isset($parentArray[$childArrayKey])) {
            $parentArray[$childArrayKey] = [$value];
        } else {
            $parentArray[$childArrayKey][] = $value;
        }
    }
}
