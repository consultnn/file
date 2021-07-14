<?php

use helpers\FileHelper;
use Laminas\Diactoros\Response as BaseResponse;
use Psr\Http\Message\StreamInterface;

class Response extends BaseResponse
{
    public function withFileHeaders(StreamInterface $body, string $fileName, string $type = 'inline')
    {
        /** TODO спереть из Yii отдачу имени файла */
        $response = $this->withHeader('Content-Transfer-Encoding', 'Binary')
            ->withHeader('Content-Disposition', "$type; filename='$fileName'")
            ->withHeader('Content-Type', FileHelper::getMimeTypeByExtension($fileName))
            ->withBody($body)
        ;
        return $response;
    }

    public function withJson($data)
    {
        /** TODO использовать JsonResponse */
        $this->getBody()->write(json_encode($data,  JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        return $this->withHeader('Content-Type', 'application/json');
    }

    public function out(): void
    {
        /** TODO скорее всего не нужен, см. \Laminas\Diactoros\Response\Serializer::toString */
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
        
        echo $this->getBody()->getContents();
    }
}
