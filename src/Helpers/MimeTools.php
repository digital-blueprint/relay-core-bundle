<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Helpers;

use Symfony\Component\Mime\MimeTypes;

class MimeTools
{
    /**
     * Convert binary data to a data url.
     */
    public static function getDataURI(string $data, string $mime): string
    {
        return 'data:'.$mime.';base64,'.base64_encode($data);
    }

    public static function getMimeType(string $data): string
    {
        $info = finfo_open();

        return finfo_buffer($info, $data, FILEINFO_MIME_TYPE);
    }

    public static function getFileExtensionForMimeType(string $mimeType): string
    {
        $mimeTypes = new MimeTypes();
        $extensions = $mimeTypes->getExtensions($mimeType);

        return $extensions[0] ?? 'dump';
    }
}
