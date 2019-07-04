<?php

namespace helpers;

class PathHelper
{
    /**
     * @param string $fileName
     * @param string $project
     * @return string
     */
    public static function makePath($fileName, $project)
    {
        $nameParts = self::splitNameIntoParts($fileName);

        return $project . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $nameParts);
    }

    /**
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
     * @param string $extension
     * @param string $hash
     * @param string $params
     * @return array
     */
    public static function makeCachePath($filePath, $extension, $hash, $params)
    {
        $paths = explode(DIRECTORY_SEPARATOR, $filePath);
        $keyName = count($paths) - 1;
        $saveName = $paths[$keyName] . "_{$hash}{$params}.{$extension}";
        unset($paths[$keyName]);
        $path = implode(DIRECTORY_SEPARATOR, $paths);
        return [
            CACHE_DIR . $path,
            CACHE_DIR . "{$filePath}_{$hash}{$params}.{$extension}",
            $saveName
        ];
    }

    /**
     * @param string $webPath
     * @return string|boolean
     */
    public static function resolvePhysicalPath($webPath)
    {
        if (is_file(STORAGE_DIR . $webPath)) {
            return STORAGE_DIR . $webPath;
        }

        $pathInfo = pathinfo($webPath);
        $template = STORAGE_DIR . implode(DIRECTORY_SEPARATOR, [$pathInfo['dirname'], $pathInfo['filename']]);
        $glob = glob($template . ".*");
        return $glob ? reset($glob) : false;
    }

    /**
     * @param string $dir
     */
    public static function checkDir($dir)
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    public static function generateWebPath($image, $project)
    {
        list($sha, $tempFile, $extension) = $image;
        list($webPath, $physicalPath, $storageDir) = self::makePathData($project, $sha, $extension);

        if (is_file($physicalPath)) {
            unlink($tempFile);
            return $webPath;
        }
        self::checkDir($storageDir);
        rename($tempFile, $physicalPath);

        return $webPath;
    }

    /**
     * Make file info for save
     *
     * @param string $project
     * @param string $sha
     * @param string $extension
     * @return array
     */
    public static function makePathData($project, $sha, $extension)
    {
        static $nameLength = 13;
        static $shaOffset = 0;

        $shaBase36 = FileHelper::internalBaseConvert($sha, 16, 36);
        $webName   = substr($shaBase36, $shaOffset, $nameLength);

        if (strlen($webName) < $nameLength) {
            $webName = str_pad($webName, $nameLength, '0', STR_PAD_LEFT);
        }

        $fileDirPath = STORAGE_DIR . $project;
        $fileParts = self::splitNameIntoParts($webName);
        $fileName = end($fileParts);
        unset($fileParts[count($fileParts) - 1]);

        foreach ($fileParts as $partItem) {
            $fileDirPath .= DIRECTORY_SEPARATOR . $partItem;
        }

        $fileAbsolutePath = $fileDirPath . DIRECTORY_SEPARATOR . $fileName . '.' . $extension;
        $webName = $webName . '.' . $extension;

        return [
            $webName,
            $fileAbsolutePath,
            $fileDirPath
        ];
    }
}