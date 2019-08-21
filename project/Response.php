<?php

use helpers\FileHelper;
use Zend\Diactoros\Response as BaseResponse;

class Response extends BaseResponse
{
    public function withFileHeaders($data, $fileName, $type = 'inline')
    {
        $this->getBody()->write($data);
        $response = $this->withHeader('Content-Transfer-Encoding', 'Binary')
                ->withHeader('Content-Disposition', "$type; filename='$fileName'")
                ->withHeader('Content-Type', FileHelper::getMimeTypeByExtension($fileName));
        return $response;
    }

    public function withJson($data)
    {
        $this->getBody()->write(json_encode($data));
        $this->withHeader('Content-Type', 'application/json');
        return $this;
    }

    public function out()
    {
        if (!headers_sent()) {
            header(sprintf(
                'HTTP/%s %s %s',
                $this->getProtocolVersion(),
                $this->getStatusCode(),
                $this->getReasonPhrase()
            ));

            foreach ($this->getHeaders() as $name => $values) {
                foreach ($values as $value) {
                    header(sprintf('%s: %s', $name, $value), false);
                }
            }
        }
        $body = $this->getBody();
        echo is_file($body) ? readfile($body) : $body;
    }
}
