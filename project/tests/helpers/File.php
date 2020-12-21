<?php

namespace tests\helpers;

use components\Filesystem;
use components\Upload;
use Laminas\Diactoros\UploadedFile;

class File
{
    public static function copyFileToTemp($name)
    {
        $tempName = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test-' . basename($name);
        copy(dirname(__DIR__) . '/files/' . $name, $tempName);
        return $tempName;
    }

    public static function upload($fileName, Filesystem $fileSystem, array $params = []): string
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
        $upload->params = $params;
        $upload->files = [$file];
        $upload->urls = null;

        $name = current($upload->getFiles());
        $fileSystem->fileName = $name;
        return $name;
    }

}
