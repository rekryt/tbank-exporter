<?php

namespace TBank\Infrastructure\API;

use TBank\App\Controller\MainController;

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
use Amp\Http\Server\RequestHandler\ClosureRequestHandler;
use Amp\Http\Server\SocketHttpServer;
use Amp\ByteStream\WritableResourceStream;
use Amp\Log\ConsoleFormatter;
use Amp\Log\StreamHandler;
use Amp\Socket\BindContext;
use Amp\Sync\LocalSemaphore;
use Amp\Socket;
use Amp\CompositeException;

use Dotenv\Dotenv;
use Monolog\Logger;
use Psr\Log\LogLevel;
use Revolt\EventLoop;

use function TBank\getEnv;
use function Amp\trapSignal;

final class Server {
    private static Server $_instance;
    private ClosureRequestHandler $requestHandler;

    /**
     * @param ?HttpServer $httpServer
     * @param ?ErrorHandler $errorHandler
     * @param ?BindContext $bindContext
     * @param ?Logger $logger
     * @throws BufferException
     * @throws StreamException
     * @throws HttpException
     */
    private function __construct(
        private ?HttpServer $httpServer,
        private ?ErrorHandler $errorHandler,
        private ?Socket\BindContext $bindContext,
        private ?Logger $logger
    ) {
        ini_set('memory_limit', getEnv('SYS_MEMORY_LIMIT') ?? '2048M');

        if (!defined('PATH_ROOT')) {
            define('PATH_ROOT', dirname(__DIR__, 3));
        }

        $dotenv = Dotenv::createImmutable(PATH_ROOT);
        $dotenv->safeLoad();

        if ($timezone = getEnv('SYS_TIMEZONE')) {
            date_default_timezone_set($timezone);
        }

        $this->logger = $logger ?? new Logger(getEnv('COMPOSE_PROJECT_NAME') ?? 'exporter');
        $logHandler = new StreamHandler(new WritableResourceStream(STDOUT));
        $logHandler->setFormatter(new ConsoleFormatter());
        $logHandler->setLevel(getEnv('DEBUG') === 'false' ? LogLevel::INFO : LogLevel::INFO);
        $this->logger->pushHandler($logHandler);

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

        $this->requestHandler = new ClosureRequestHandler((new MainController($this->logger))(...));
    }

    /**
     * @param ?HttpServer $httpServer
     * @param ?ErrorHandler $errorHandler
     * @param ?BindContext $bindContext
     * @param ?Logger $logger
     * @throws BufferException
     * @throws HttpException
     * @throws StreamException
     */
    public static function getInstance(
        HttpServer $httpServer = null,
        ErrorHandler $errorHandler = null,
        Socket\BindContext $bindContext = null,
        Logger $logger = null
    ): Server {
        return self::$_instance ??= new self($httpServer, $errorHandler, $bindContext, $logger);
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
            $this->httpServer->start($this->requestHandler, $this->errorHandler);

            EventLoop::setErrorHandler(function ($e) {
                $this->logger->error($e->getMessage());
            });

            if (defined('SIGINT') && defined('SIGTERM')) {
                // Await SIGINT or SIGTERM to be received.
                $signal = trapSignal([SIGINT, SIGTERM]);
                $this->logger->info(\sprintf('Received signal %d, stopping HTTP server', $signal));
                $this->httpServer->stop();
            } else {
                EventLoop::run();
            }
        } catch (Socket\SocketException | CompositeException $e) {
            $this->logger->warning($e->getMessage());
        } catch (\Throwable $e) {
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
