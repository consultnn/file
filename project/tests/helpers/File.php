<?php

namespace Tests\helpers;

use components\Filesystem;
use components\Upload;
use Zend\Diactoros\UploadedFile;

class File
{
    public static function moveFilesToTemp($files)
    {
        $result = [];
        foreach ($files as $name) {
            $tempName = self::moveFileToTemp($name);
            $result[] = $tempName;
        }
        return $result;
    }

    public static function moveFileToTemp($name)
    {
        $temp = sys_get_temp_dir();
        $tempName = $temp . DIRECTORY_SEPARATOR . basename($name);
        copy(__DIR__ . '/../files/' . $name, $tempName);
        return $tempName;
    }

    public static function upload($fileName, Filesystem $fileSystem): string
    {
        if (dirname($fileName) !== sys_get_temp_dir()) {
            throw new \Exception('bad file location');
        }

        if (!is_file($fileName)) {
            throw new \Exception('file does not exists');
        }


        $file = new UploadedFile($fileName, filesize($fileName), UPLOAD_ERR_OK);
        $upload = new Upload();
        $upload->filesystem = $fileSystem;
        $upload->params = [];
        $upload->files = [$file];
        $upload->urls = null;

        $name = current($upload->getFiles());
        $fileSystem->fileName = $name;
        return $name;
    }

}
