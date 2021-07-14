<?php

namespace handlers;

use Psr\Http\Message\ResponseInterface;

class Status extends BaseHandler
{
    public function handle(): ResponseInterface
    {
        $token = $this->app->request->getAttribute('token');

        $imagesFile = RUNTIME_DIR . "pdf-images-{$token}";

        if (!$token) {
            return $this->app->response->withJson(['status' => 'error']);
        }

        if (!is_file($imagesFile)) {
            return $this->app->response->withJson(['status' => 'process', 'percent' => 0]);
        }

        /** TODO либо отдавать нормальный ответ, либо удалить */
        throw new \LogicException();
        echo $this->app->filesystem->read($imagesFile);
        $this->app->filesystem->delete($imagesFile);

        return $this->app->response;
    }
}
