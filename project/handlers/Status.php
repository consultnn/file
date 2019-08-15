<?php

namespace handlers;

use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Response\JsonResponse;

class Status extends BaseHandler
{
    public function handle(): ResponseInterface
    {
        $token = $this->app->request->getAttribute('token');

        $imagesFile = RUNTIME_DIR . "pdf-images-{$token}";

        if (!$token) {
            return new JsonResponse(['status' => 'error']);
        }

        if (!is_file($imagesFile)) {
            return new JsonResponse(['status' => 'process', 'percent' => 0]);
        }

        echo file_get_contents($imagesFile);
        unlink($imagesFile);

        return $this->app->response;
    }
}
