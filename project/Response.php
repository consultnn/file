<?php

use Zend\Diactoros\Response as BaseResponse;

class Response extends BaseResponse
{
    public function withStatus($code, $reasonPhrase = ''): BaseResponse
    {
        $response = BaseResponse::withStatus($code, $reasonPhrase);
        header("HTTP/{$response->getProtocolVersion()} {$code} {$response->getReasonPhrase()}");
        return $response;
    }
}
