<?php

namespace Tests\unit;

use components\Filesystem;
use PHPUnit\Framework\TestCase;
use Tests\helpers\File;

/**
 * Class FileTest
 */
class FileTest extends TestCase
{
    /**
     * @param string $filename
     * @param string $saveName
     * @dataProvider fileProvider
     */
    public function testUpload($filename, $saveName)
    {
        $tempFile = \Tests\helpers\File::moveFileToTemp($filename);
        $sha = sha1_file($tempFile);

        $fileSystem = new Filesystem(['project' => 'example']);
        $name = File::upload($tempFile, $fileSystem);
        $this->assertEquals($name, $saveName, 'Файл сохранён с неправильным названием');
        $this->assertEquals($sha, sha1_file($fileSystem->resolvePhysicalPath()), 'Файл пережат без нужды');
    }


    /**
     * @return array
     */
    public function fileProvider()
    {
        return [
            ['8307331.jpe', 'a2s11i90y2ixk.jpeg'], //должны исправить расширение
            ['di.png', 'r606m0z5ygvgd.png'],
            ['napoleon for svg 1.svg', 'fzlohhh132w6i.svg'],
            ['свободу сократу.png', 'q4g7g0h98m9t8.png'],
        ];
    }
}
