<?php

namespace middleware;

use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\Response\JsonResponse;

class StatusMiddleware implements RequestHandlerInterface
{
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
            return new JsonResponse(['status' => 'error']);
        }

        if (!is_file($imagesFile)) {
            return new JsonResponse(['status' => 'process', 'percent' => 0]);
        }

        echo file_get_contents($imagesFile);
        unlink($imagesFile);

        return new Response;
    }
}