<?php

namespace TBank\Infrastructure\API;

use Amp\Http\Server\ErrorHandler;
use Amp\Http\Server\HttpServer;
use Amp\Http\Server\Router;
use Amp\Http\Server\StaticContent\DocumentRoot;

use Psr\Log\LoggerInterface;
use TBank\Infrastructure\API\Handler\HTTPHandler;
use function TBank\getEnv;

final class RouterFactory implements RouterFactoryInterface {
    public function create(HttpServer $httpServer, ErrorHandler $errorHandler, LoggerInterface $logger): Router {
        $router = new Router($httpServer, $logger, $errorHandler);
        $httpHandler = HTTPHandler::getInstance($logger)->getHandler();

        $router->addRoute('GET', '/', $httpHandler);
        $router->addRoute('GET', '/metrics', $httpHandler);

        $router->addRoute('GET', '/api/{controller}', $httpHandler);
        $router->addRoute('POST', '/api/{controller}', $httpHandler);

        $fallback = new DocumentRoot(
            $httpServer,
            $errorHandler,
            PATH_ROOT . '/' . (getEnv('HTTP_DOCUMENT_ROOT') ?? 'public')
        );
        $router->setFallback($fallback);

        return $router;
    }
}
