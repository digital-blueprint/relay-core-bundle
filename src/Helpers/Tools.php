<?php

namespace DBP\API\CoreBundle\Helpers;

class Tools
{
    /**
     * Like json_decode but throws on invalid json data
     *
     * @param string $json
     * @param bool $assoc
     * @throws JsonException
     * @return mixed
     */
    static public function decodeJSON(string $json, bool $assoc = FALSE) {
        $result = json_decode($json, $assoc);
        $json_error = json_last_error();
        if ($json_error !== JSON_ERROR_NONE) {
            throw new JsonException(sprintf('%s: "%s"', json_last_error_msg(), print_r($json, true)));
        }
        return $result;
    }

    /**
     * @param string $message
     * @return string
     */
    public static function filterErrorMessage(string $message) :string {
        // hide token parameters
        return preg_replace('/([&?]token=)[\w\d-]+/i', '${1}hidden', $message);
    }

    public static function dumpTrace(...$moreVars) {
        $e = new \Exception();
        dump($e->getTraceAsString(), $moreVars);
    }
}
