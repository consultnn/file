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
    public function testFile(string $extension, string $code, array $params = [])
    {
        $fileName = 'r606m0z5ygvgd.png';
        $token = (require __DIR__ . '/../config/config.php')['app']['handler']['image']['downloadSecret'];
        $image = "r606m0z5ygvgd_{$code}.{$extension}";
        $this->assertEquals($image, File::encodeParams($fileName, $params + ['f' => $extension], $token));

        $response = $this->runApp('GET', '/' . $image);
        $this->assertEquals(200, $response->getStatusCode());
    
        $body = $response->getBody()->getContents();
        $info = getimagesizefromstring($body);
        file_put_contents('/www/runtime/' . $image, $body);
        $this->assertEquals("image/{$extension}", $info['mime']);
    }
    
    public function images(): array
    {
        return [
            ['webp', '1mdzovh'],
            ['jpeg', 'z1kvx2'],
            ['png', 'ufw50o_stc-000000', ['stc' => '000000']],
            ['png', '1or6itz_stc-000000_bg-555111_w-300_h-300_far-c',
                ['stc' => '000000', 'bg' => '555111', 'w' => 300, 'h' => 300, 'far' => 'c']
            ],
            ['png', '1g9ylob_stc-000000_bg-transparent_w-300_h-300_far-c',
                ['stc' => '000000', 'bg' => 'transparent', 'w' => 300, 'h' => 300, 'far' => 'c']
            ],
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