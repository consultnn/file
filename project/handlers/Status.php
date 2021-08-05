<?php

namespace handlers;

use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Response\TextResponse;
use Psr\Http\Message\ResponseInterface;

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

        $response = new TextResponse($this->app->filesystem->read($imagesFile), 200, ['content-type' => 'application/json']);
        $this->app->filesystem->delete($imagesFile);

        return $response;
    }
}
