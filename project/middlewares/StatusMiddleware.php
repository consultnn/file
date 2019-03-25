<?php

namespace middlewares;

use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Http\Response;

class StatusMiddleware implements RequestHandlerInterface
{
    private $response;

    public function __construct()
    {
        $this->response = new Response();
    }

    /**
     * Handles a request and produces a response.
     *
     * May call other collaborating code to generate the response.
     * @param $request ServerRequestInterface
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $token = $request->getAttribute('token');

        $imagesFile = RUNTIME_DIR . "pdf-images-{$token}";

        if (!$token) {
            return $this->response->withJson(['status' => 'error']);
        }

        if (!is_file($imagesFile)) {
            return $this->response->withJson(['status' => 'process', 'percent' => 0]);
        }

        echo file_get_contents($imagesFile);
        unlink($imagesFile);

        return $this->response;
    }
}