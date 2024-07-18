<?php

namespace TBank\Infrastructure\API\Handler;

use Amp\Http\HttpStatus;
use Amp\Http\Server\Request;

use TBank\App\Controller\AbstractController;

use Exception;

abstract class Handler implements HandlerInterface {
    /**
     * @param string $name
     * @param Request $request
     * @param ?string[] $headers
     * @return AbstractController
     * @throws Exception
     */
    protected function getController(string $name, Request $request, array $headers = null): AbstractController {
        $className = '\\TBank\\App\\Controller\\' . ucfirst($name) . 'Controller';
        if (!class_exists($className)) {
            throw new Exception('Controller ' . $className . ' not found', HttpStatus::NOT_FOUND);
        }
        return new $className($request, $headers ?? ['content-type' => 'text/plain']);
    }
}
