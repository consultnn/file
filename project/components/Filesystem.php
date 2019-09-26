<?php

namespace components;

use helpers\FileHelper;
use League\Flysystem\Adapter\Local;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Util;

/**
 * Class Filesystem
 * @package components
 */
class Filesystem extends \League\Flysystem\Filesystem
{
    public $fileName;
    public $file;
    public $path;
    public $saveName;
    public $cachePath;
    public $cacheFile;
    protected $project;

    public function __construct($config = null)
    {
        foreach ($config as $name => $param) {
            if (property_exists($this, $name)) {
                $this->$name = $param;
                unset($config[$name]);
            }
        }
        $adapter = new Local('/');
        parent::__construct($adapter, $config);
    }

    public function resolvePhysicalPath()
    {
        $webPath = $this->makePath();

        if (is_file(STORAGE_DIR . $webPath)) {
            return STORAGE_DIR . $webPath;
        }

        $pathInfo = Util::pathinfo($webPath);
        $template = STORAGE_DIR . implode(DIRECTORY_SEPARATOR, [$pathInfo['dirname'], $pathInfo['filename']]);
        $glob = glob($template . '.*');
        return $glob ? reset($glob) : false;
    }

    public function makeCachePath($extension, $hash, $params)
    {
        $this->cacheFile = $this->withCacheDir("{$this->path}_{$hash}{$params}.{$extension}");
        $paths = explode(DIRECTORY_SEPARATOR, $this->path);
        $this->saveName = array_pop($paths) . "_{$hash}{$params}.{$extension}";
        $path = implode(DIRECTORY_SEPARATOR, $paths);
        $this->cachePath = $this->withCacheDir($path);
    }

    public function makePathData($sha, $extension)
    {
        $nameLength = 13;

        $shaBase36 = FileHelper::internalBaseConvert($sha, 16, 36);
        $webName   = substr($shaBase36, 0, $nameLength);

        if (strlen($webName) < $nameLength) {
            $webName = str_pad($webName, $nameLength, '0', STR_PAD_LEFT);
        }

        $fileDirPath = STORAGE_DIR . $this->project;
        $fileParts = self::splitNameIntoParts($webName);
        $fileName = array_pop($fileParts);

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

    public function generateWebPath($image)
    {
        list($sha, $tempFile, $extension) = $image;
        list($webPath, $physicalPath, $storageDir) = $this->makePathData($sha, $extension);

        if ($this->has($physicalPath)) {
            $this->delete($tempFile);
            return $webPath;
        }
        $this->createDir($storageDir);
        $this->rename($tempFile, $physicalPath);

        return $webPath;
    }

    private function makePath()
    {
        $nameParts = self::splitNameIntoParts($this->fileName);
        return $this->path = $this->project . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $nameParts);
    }

    private function splitNameIntoParts($name, $count = 2)
    {
        static $lengthOfPiece = 2;
        $pieces = [];

        do {
            $pieces[] = substr($name, count($pieces) * $lengthOfPiece, $lengthOfPiece);
        } while (count($pieces) < $count);

        $pieces[] = substr($name, count($pieces) * $lengthOfPiece);

        return $pieces;
    }

    private function withCacheDir($path)
    {
        return CACHE_DIR . $path;
    }
}
