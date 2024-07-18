<?php

namespace TBank\Infrastructure\API;

use Amp\Http\Server\ErrorHandler;
use Amp\Http\Server\HttpServer;
use Amp\Http\Server\Router;
use Psr\Log\LoggerInterface;

interface RouterFactoryInterface {
    public function create(
        HttpServer $httpServer,
        ErrorHandler $errorHandler,
        LoggerInterface $logger
    ): Router;
}
