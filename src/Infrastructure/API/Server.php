<?php

namespace TBank\Infrastructure\API;

use Amp\Http\Server\Router;

use Amp\ByteStream\BufferException;
use Amp\ByteStream\StreamException;
use Amp\Http\Client\HttpException;
use Amp\Http\Server\DefaultErrorHandler;
use Amp\Http\Server\Driver\ConnectionLimitingClientFactory;
use Amp\Http\Server\Driver\ConnectionLimitingServerSocketFactory;
use Amp\Http\Server\Driver\DefaultHttpDriverFactory;
use Amp\Http\Server\Driver\SocketClientFactory;
use Amp\Http\Server\ErrorHandler;
use Amp\Http\Server\HttpServer;
use Amp\Http\Server\HttpServerStatus;
use Amp\Http\Server\SocketHttpServer;
use Amp\Socket\BindContext;
use Amp\Sync\LocalSemaphore;
use Amp\Socket;
use Amp\CompositeException;

use Monolog\Logger;
use TBank\Infrastructure\Storage\MainStorage;
use Throwable;

use function TBank\getEnv;
use function Amp\trapSignal;

final class Server extends AbstractAppModule {
    private static Server $_instance;
    private Router $router;

    /**
     * @param ?HttpServer $httpServer
     * @param ?RouterFactoryInterface $routerFactory
     * @param ?ErrorHandler $errorHandler
     * @param ?BindContext $bindContext
     * @param ?Logger $logger
     * @throws Throwable
     */
    private function __construct(
        private ?HttpServer $httpServer,
        private ?RouterFactoryInterface $routerFactory,
        private ?ErrorHandler $errorHandler,
        private ?Socket\BindContext $bindContext,
        private ?Logger $logger
    ) {
        $this->logger = $logger ?? App::getLogger();
        $serverSocketFactory = new ConnectionLimitingServerSocketFactory(new LocalSemaphore(1024));
        $clientFactory = new ConnectionLimitingClientFactory(new SocketClientFactory($this->logger), $this->logger, 10);
        $this->httpServer =
            $httpServer ??
            new SocketHttpServer(
                logger: $this->logger,
                serverSocketFactory: $serverSocketFactory,
                clientFactory: $clientFactory,
                httpDriverFactory: new DefaultHttpDriverFactory(logger: $this->logger, streamTimeout: 60)
            );
        $this->bindContext = $bindContext ?? (new Socket\BindContext())->withoutTlsContext();
        $this->errorHandler = $errorHandler ?? new DefaultErrorHandler();

        $this->router = ($routerFactory ?: new RouterFactory())->create(
            $this->httpServer,
            $this->errorHandler,
            $this->logger
        );

        $this->logger->info('Ready', [
            array_values(array_map(fn($item) => $item->ticker, MainStorage::getInstance()->getTickers())),
        ]);
    }

    /**
     * @param ?HttpServer $httpServer
     * @param ?ErrorHandler $errorHandler
     * @param ?BindContext $bindContext
     * @param ?Logger $logger
     * @throws BufferException
     * @throws HttpException
     * @throws Throwable
     */
    public static function getInstance(
        HttpServer $httpServer = null,
        RouterFactoryInterface $routerFactory = null,
        ErrorHandler $errorHandler = null,
        Socket\BindContext $bindContext = null,
        Logger $logger = null
    ): Server {
        return self::$_instance ??= new self($httpServer, $routerFactory, $errorHandler, $bindContext, $logger);
    }

    /**
     * Запуск веб-сервера
     * @return void
     */
    public function start(): void {
        try {
            $this->httpServer->expose(
                new Socket\InternetAddress(getEnv('HTTP_HOST') ?? '0.0.0.0', getEnv('HTTP_PORT') ?? 8080),
                $this->bindContext
            );
            //$this->socketHttpServer->expose(
            //    new Socket\InternetAddress('[::]', $_ENV['HTTP_PORT'] ?? 8080),
            //    $this->bindContext
            //);
            $this->httpServer->start($this->router, $this->errorHandler);
        } catch (Socket\SocketException | CompositeException $e) {
            $this->logger->warning($e->getMessage());
        } catch (Throwable $e) {
            $this->logger->error($e->getMessage());
        }
    }

    public static function getLogger(): ?Logger {
        return self::$_instance->logger;
    }

    /**
     * @return void
     */
    public function stop(): void {
        $this->httpServer->stop();
    }

    /**
     * @return HttpServerStatus
     */
    public function getStatus(): HttpServerStatus {
        return $this->httpServer->getStatus();
    }
}
