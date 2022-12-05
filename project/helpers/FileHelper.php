<?php

namespace helpers;

class FileHelper
{
    public static function internalHash($filePath, $params, $token)
    {
        $hash = hash('crc32', $token . $filePath . $params . $token);
        return str_pad(self::internalBaseConvert($hash, 16, 36), 5, '0', STR_PAD_LEFT);
    }

    public static function internalBaseConvert($number, $fromBase, $toBase): string
    {
        return gmp_strval(gmp_init($number, $fromBase), $toBase);
    }

    public static function internalDecodeParams(?string $paramString): array
    {
        $result = [];
        if ($paramString === null) {
            return $result;
        }
        if (preg_match_all('/_(?:([a-z]{1,4})\-([a-z\d\|\*\.]+))+/i', $paramString, $matches)) {
            foreach ($matches[1] as $idx => $paramName) {
                $result[$paramName] = $matches[2][$idx];
            }
        }

        if (isset($result['b'])) {
            $result['w'] = $result['h'] = $result['b'];
            unset($result['b']);
        }

        return $result;
    }
    
    public static function getMimeTypeByExtension(string $file): string
    {
        $mimeTypes = include __DIR__ . '/../settings/mime-types.php';
        $extension = self::getPhysicalExtension($file);
        if ($extension !== '' && isset($mimeTypes[$extension])) {
            return $mimeTypes[$extension];
        }
        return 'text/plain';
    }

    public static function getPhysicalExtension(string $path): string
    {
        return strtolower(pathinfo($path, PATHINFO_EXTENSION));
    }
}
