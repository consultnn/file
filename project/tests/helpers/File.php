<?php

namespace tests\helpers;

use components\Filesystem;
use components\Upload;
use helpers\FileHelper;
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

    public static function encodeParams(string $file, array $params, string $token): string
    {
        $pathInfo = pathinfo($file);
        if (!empty($params['f'])) {
            $pathInfo['extension'] = $params['f'];
            $file = $pathInfo['filename'] . '.' . $pathInfo['extension'];
            unset($params['f']);
        }

        $paramsString = '';
        foreach ($params as $key => $value) {
            $paramsString .= '_' . $key . '-' . $value;
        }

        $result = $pathInfo['filename'] . '_' . FileHelper::internalHash($file, $paramsString, $token). $paramsString;
        if (!empty($pathInfo['extension'])) {
            $result .= '.' . $pathInfo['extension'];
        }
        return $result;
    }


}
