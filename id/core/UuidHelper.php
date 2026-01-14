<?php
namespace App\Core;

class UuidHelper {
    /**
     * Permanent Namespace for Sidiksae Identity Module
     * DO NOT CHANGE this as it will break all external references.
     */
    private const NAMESPACE = '8f4c2e6b-7d5a-4b9e-9d3c-1a2b3c4d5e6f';

    public static function generateV5($name) {
        $nhex = str_replace(['-', '{', '}'], '', self::NAMESPACE);
        $nstr = '';

        for ($i = 0; $i < strlen($nhex); $i += 2) {
            $nstr .= chr(hexdec($nhex[$i] . $nhex[$i + 1]));
        }

        $hash = sha1($nstr . $name);

        return sprintf('%08s-%04s-%04x-%04x-%12s',
            substr($hash, 0, 8),
            substr($hash, 8, 4),
            (hexdec(substr($hash, 12, 4)) & 0x0fff) | 0x5000,
            (hexdec(substr($hash, 16, 4)) & 0x3fff) | 0x8000,
            substr($hash, 20, 12)
        );
    }

    public static function isValid($uuid) {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-5[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uuid);
    }
}
