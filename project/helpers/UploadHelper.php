<?php

namespace helpers;

class UploadHelper
{
    const UPLOAD_SECRET = ''<secret>'';
    const SHA_OFFSET = 0;
    const NAME_LENGTH = 13;

    public static function makeNewFileName($sha, $extension, $project)
    {
        $extension = strtolower($extension);
        $shaBase36 = ZFile::internalBaseConvert($sha, 16, 36);
        $webName   = substr($shaBase36, self::SHA_OFFSET, self::NAME_LENGTH);

        if (strlen($webName) < self::NAME_LENGTH)
            $webName = str_pad($webName, self::NAME_LENGTH, '0', STR_PAD_LEFT);

        $webPath = $webName . '.' . $extension;

        list($firstDir, $secondDir, $storageName) = UploadHelper::splitPathIntoParts($webPath);
        $storagePath = realpath(__DIR__.'/../storage');
        $storageDir = $storagePath .'/'.$project.'/'.$firstDir.'/'.$secondDir.'/';

        $physicalPath = $storageDir . $storageName;

        return [$webPath, $physicalPath, $storageDir, $storageName];
    }

    /**
     * Разделяет uri имя файла на компоненты (папки 1 и 2 уровней вложенности) и имя физического файла.
     * @param string $webName
     * @return array:string
     */
    public static function splitPathIntoParts($webName)
    {
        $firstDir  = substr($webName, 0, 2);
        $secondDir = substr($webName, 2, 2);
        $fileName  = substr($webName, 4);

        return array($firstDir, $secondDir, $fileName);
    }
}