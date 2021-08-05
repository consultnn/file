<?php

use Laminas\Diactoros\Response;
use Psr\Http\Message\StreamInterface;

class FileResponse extends Response
{
    public function __construct(StreamInterface $body, string $fileName, string $attachment = 'inline', array $headers = [])
    {
        parent::__construct($body, 200, $headers);
        $this->withHeader('Content-Transfer-Encoding', 'Binary')
            ->withHeader('Content-Disposition', "{$attachment}; filename='{$fileName}'")
            ->withHeader('Content-Type', \helpers\FileHelper::getMimeTypeByExtension($fileName))
            ->withBody($body)
        ;
    }
}