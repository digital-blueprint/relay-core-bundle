<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Logging;

final class Tools
{
    /**
     * Try to mask a list of values in a monolog logging record.
     *
     * @param array  $record      The monolog record to mask
     * @param array  $values      The values to mask
     * @param string $replacement The replacement string used for masking
     */
    public static function maskValues(array &$record, array $values, string $replacement)
    {
        $maskValues = function (string $input) use ($values, $replacement): string {
            $output = $input;
            foreach ($values as $value) {
                // Don't mask values contained in words, otherwise if the outer masked word is known the
                // value could be derived.
                $output = preg_replace(
                    '/(^|[^\w])('.preg_quote($value).')($|[^\w])/',
                    '$1'.$replacement.'$3',
                    $output);
            }

            return $output;
        };

        if (isset($record['message'])) {
            $record['message'] = $maskValues($record['message']);
        }
        foreach ($record['extra'] ?? [] as $key => $value) {
            if (is_string($value)) {
                $record['extra'][$key] = $maskValues($value);
            }
        }
        foreach ($record['context'] ?? [] as $key => $value) {
            if (is_string($value)) {
                $record['context'][$key] = $maskValues($value);
            }
        }
    }
}
