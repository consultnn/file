<?php

namespace helpers;

class FileHelper
{
    public static function internalHash($filePath, $params, $token)
    {
        $hash = hash('crc32', $token . $filePath . $params . $token);
        return str_pad(self::internalBaseConvert($hash, 16, 36), 5, '0', STR_PAD_LEFT);
    }

    public static function internalBaseConvert($number, $fromBase, $toBase)
    {
        return gmp_strval(gmp_init($number, $fromBase), $toBase);
    }

    public static function internalDecodeParams($paramString)
    {
        $result = [];
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

    /**
     * @param string $filePath
     * @return string
     */
    public static function getExtension($filePath)
    {
        if ($mime = self::getMimeType($filePath)) {
            return self::getExtensionFromMime($mime);
        }

        $imageInfo = getimagesize($filePath);
        return isset($imageInfo['mime']) ? explode(DIRECTORY_SEPARATOR, $imageInfo['mime'])[1] : false;
    }

    /**
     * @param string $mime
     * @return null|string
     */
    private static function getExtensionFromMime($mime)
    {
        $mime = explode(';', $mime)[0];
        return explode(DIRECTORY_SEPARATOR, $mime)[1];
    }

    /**
     * @param $file
     * @return mixed|null
     */
    private static function getMimeType($file)
    {
        $info = finfo_open(FILEINFO_MIME_TYPE);

        if ($info) {
            $result = finfo_file($info, $file);
            finfo_close($info);

            if ($result !== false) {
                return $result;
            }
        }

        return static::getMimeTypeByExtension($file);
    }

    /**
     * @param string $file
     * @return string
     */
    public static function getMimeTypeByExtension($file)
    {
        $mimeTypes = include __DIR__ . '/../settings/mime-types.php';
        $extension = self::getPhysicalExtension($file);
        if ($extension !== '' && isset($mimeTypes[$extension])) {
            return $mimeTypes[$extension];
        }
        return 'text/plain';
    }

    /**
     * @param string $path
     * @return string
     */
    public static function getPhysicalExtension($path)
    {
        return strtolower(pathinfo($path, PATHINFO_EXTENSION));
    }
}
