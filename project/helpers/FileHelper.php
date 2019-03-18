<?php

namespace helpers;

class FileHelper
{
    const DEFAULT_QUALITY = 85;

    private static function getMimeTypes()
    {
        return include __DIR__ . '/../src/mime-types.php';
    }

    public static function internalHash($filePath, $params, $token)
    {
        $hash = hash('crc32', $token . $filePath . $params . $token);
//var_dump(str_pad(self::internalBaseConvert($hash, 16, 36), 5, '0', STR_PAD_LEFT));die();
        return str_pad(self::internalBaseConvert($hash, 16, 36), 5, '0', STR_PAD_LEFT);
    }

    public static function internalBaseConvert($number, $fromBase, $toBase)
    {
        return gmp_strval(gmp_init($number, $fromBase), $toBase);
    }

    /**
     * Make a path relative to the storage
     *
     * @param string $fileName
     * @param string $project
     * @return string
     */
    public static function makePath($fileName, $project)
    {
        $nameParts = self::splitNameIntoParts($fileName);

        return $project . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $nameParts);
    }

    public static function makeCachePath($filePath, $extension, $hash, $params)
    {
        $paths = explode(DIRECTORY_SEPARATOR, $filePath);
        $keyName = count($paths) - 1;
        $saveName = $paths[$keyName] . "_{$hash}{$params}.{$extension}";
        unset($paths[$keyName]);
        $path = implode(DIRECTORY_SEPARATOR, $paths);
        return [
            CACHE_DIR . DIRECTORY_SEPARATOR . $path,
            CACHE_DIR . DIRECTORY_SEPARATOR . "{$filePath}_{$hash}{$params}.{$extension}",
            $saveName
        ];
    }

    /**
     * По uri-имени возвращает путь к файлу-оригиналу или false если он не найден.
     * @param string $webPath
     * @return string|boolean
     */
    public static function resolvePhysicalPath($webPath)
    {
        $storagePath = STORAGE_DIR . DIRECTORY_SEPARATOR;
        if (is_file($storagePath . $webPath))
            return $storagePath . $webPath;

        $pathInfo = pathinfo($webPath);
        $template = implode(DIRECTORY_SEPARATOR, [$storagePath, $pathInfo['dirname'], $pathInfo['filename']]);
        $glob = glob($template . ".*");
        return $glob ? reset($glob) : false;
    }


    /**
     * Split file name on path pieces
     *
     * @param string $name
     * @param int $count
     * @return string[]
     */
    public static function splitNameIntoParts($name, $count = 2)
    {
        static $lengthOfPiece = 2;
        $pieces = [];

        do {
            $pieces[] = substr($name, count($pieces) * $lengthOfPiece, $lengthOfPiece);
        } while (count($pieces) < $count);

        $pieces[] = substr($name, count($pieces) * $lengthOfPiece);

        return $pieces;
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

        if (isset($imageInfo['mime'])) {
            $extension = explode(DIRECTORY_SEPARATOR, $imageInfo['mime'])[1];

            return $extension == 'jpeg' ? 'jpg' : $extension;
        }

        return false;
    }

    /**
     * @param string $mime
     * @return null|string
     */
    private static function getExtensionFromMime($mime)
    {
        if ($mime) {
            $mime = explode(';', $mime)[0];

            return explode(DIRECTORY_SEPARATOR, $mime)[1];
        }

        return null;
    }

    /**
     * @param $file
     * @return mixed|null
     */
    public static function getMimeType($file)
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
        $extension = self::getPhysicalExtension($file);
        if ($extension !== '' && isset(self::getMimeTypes()[$extension])) {
            return self::getMimeTypes()[$extension];
        }
        return 'text/plain';
    }

    public static function getPhysicalExtension($path)
    {
        return strtolower(pathinfo($path, PATHINFO_EXTENSION));
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

    public static function checkDir($dir)
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}