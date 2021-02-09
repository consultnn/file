<?php

namespace tests\functional;

use Application;
use Imagine\Imagick\Imagine;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\UploadedFile;
use Laminas\Diactoros\Uri;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use tests\helpers\File;

class ImageTest extends TestCase
{
    private $_files = [];
    public function setUp(): void
    {
        parent::setUp();

        $source = [
            'di.png' => 'r606m0z5ygvgd.png'
        ];

        $files = [];
        foreach ($source as $fileName => $saveName) {
            $tempPath = File::copyFileToTemp($fileName);
            new UploadedFile($tempPath, filesize($tempPath), UPLOAD_ERR_OK);
            $files[$saveName] = $tempPath;
        }
        $this->_files = $files;
    }
    
    /**
     * @dataProvider images
     */
    public function testFile(string $extension, string $code)
    {
        $fileName = 'r606m0z5ygvgd.png';
        $token = (require __DIR__ . '/../config/config.php')['app']['handler']['image']['downloadSecret'];
        $image = "r606m0z5ygvgd_{$code}.{$extension}";
        $this->assertEquals($image, File::encodeParams($fileName, ['f' => $extension], $token));

        $response = $this->runApp('GET', '/' . $image);
        $this->assertEquals(200, $response->getStatusCode());
    
        $response->getBody()->rewind();
        $info = getimagesizefromstring($response->getBody()->getContents());
        $this->assertEquals("image/{$extension}", $info['mime']);
    }
    
    public function images(): array
    {
        return [
            ['webp', '1mdzovh'],
            ['jpeg', 'z1kvx2'],
//            ['avif', '1fvy639'], //Imagine не поддерживает avif
        ];
    }

    /**
     * Process the application given a request method and URI
     *
     * @param string $requestMethod the request method (e.g. GET, POST, etc.)
     * @param string $requestUri the request URI
     * @param \Psr\Http\Message\UploadedFileInterface[] $files
     * @return ResponseInterface
     */
    public function runApp(string $requestMethod, string $requestUri, array $headers = [])
    {
        $server = ['DOMAIN' => 'example'];
        $request = (new ServerRequest($server, [], new Uri($requestUri), $requestMethod));
        foreach ($headers as $header => $value) {
            $request->withHeader($header, $value);
        }

        $config = array_replace_recursive(
            require __DIR__ . '/../../settings/config.php',
            require __DIR__ . '/../config/config.php'
        );

        $application = new Application();
        $application->request = $request;
        return $application->run($config);
    }
}