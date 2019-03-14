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

        return str_pad(self::internalBaseConvert($hash, 16, 36), 5, '0', STR_PAD_LEFT);
    }

    public static function internalBaseConvert($number, $fromBase, $toBase)
    {
        return gmp_strval(gmp_init($number, $fromBase), $toBase);
    }

    /**
     * Make a path relative to the storage
     *
     * @param string $hash
     * @param string $project
     * @param string $extension
     * @return string
     */
    public static function makePath($hash, $project, $extension)
    {
        $nameParts = self::splitNameIntoParts($hash);

        $pathPrefix = $project . '/' . implode('/', $nameParts);

        return $pathPrefix . '.' . $extension;
    }

    public static function makeSavedPath($name, $project, $extension)
    {
        $nameParts = self::splitNameIntoParts($name);

        $pathPrefix = $project . '/' . implode('/', $nameParts);

        return $pathPrefix . '.' . $extension;
    }

    /**
     * По uri-имени возвращает путь к файлу-оригиналу или false если он не найден.
     * @param string $webPath
     * @return string|boolean
     */
    public static function resolvePhysicalPath($webPath)
    {
        $storagePath = STORAGE_DIR . '/';
        if (is_file($storagePath . $webPath))
            return $storagePath . $webPath;

        $pathInfo = pathinfo($webPath);
        $template = implode('/', [$storagePath, $pathInfo['dirname'], $pathInfo['filename']]);
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
            $extension = explode('/', $imageInfo['mime'])[1];

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

            return explode('/', $mime)[1];
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
    * @return null|string
    */
    public static function getMimeTypeByExtension($file)
    {
        if (($ext = pathinfo($file, PATHINFO_EXTENSION)) !== '') {
            $ext = strtolower($ext);
            if (isset(self::getMimeTypes()[$ext])) {
                return self::getMimeTypes()[$ext];
            }
        }

        return null;
    }

    public static function getPhysicalExtension($path)
    {
        return strtolower(pathinfo($path, PATHINFO_EXTENSION));
    }

    public static function internalDecodeParams($paramString)
    {
        $result = array();
        if (preg_match_all('/_(?:([a-z]{1,4})\-([a-z\d\|\*\.]+))+/i', $paramString, $matches))
        {
            foreach ($matches[1] as $idx => $paramName)
            {
                $result[$paramName] = $matches[2][$idx];
            }
        }

        if (isset($result['b']))
        {
            $result['w'] = $result['h'] = $result['b'];
            unset($result['b']);
        }

        return $result;
    }

    private static function getSettings()
    {

    }
}