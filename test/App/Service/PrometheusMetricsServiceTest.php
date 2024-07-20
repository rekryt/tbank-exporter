<?php

namespace TBank\App\Service;

use Amp\CompositeException;
use Amp\Http\Server\DefaultErrorHandler;
use Amp\Http\Server\Driver\ConnectionLimitingClientFactory;
use Amp\Http\Server\Driver\ConnectionLimitingServerSocketFactory;
use Amp\Http\Server\Driver\DefaultHttpDriverFactory;
use Amp\Http\Server\Driver\SocketClientFactory;
use Amp\Http\Server\SocketHttpServer;
use Amp\Sync\LocalSemaphore;
use TBank\AsyncTest;
use TBank\Infrastructure\API\App;
use TBank\Infrastructure\API\RouterFactoryInterface;
use TBank\Infrastructure\API\Server;

use Amp\Http\HttpStatus;
use Amp\Http\Server\ErrorHandler;
use Amp\Http\Server\HttpServer;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler\ClosureRequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Server\Router;
use Amp\Socket;
use Psr\Log\LoggerInterface;

use Amp\ByteStream\BufferException;
use Amp\ByteStream\StreamException;
use Amp\Http\Client\HttpException;
use Throwable;

use function TBank\getEnv;

/**
 * @runInSeparateProcess
 */
final class PrometheusMetricsServiceTest extends AsyncTest {
    /**
     * @throws Throwable
     * @throws BufferException
     * @throws HttpException
     */
    protected function setUp(): void {
        parent::setUp();

        $errorHandler = new DefaultErrorHandler();
        $this->httpServer = new SocketHttpServer(
            logger: App::getLogger(),
            serverSocketFactory: new ConnectionLimitingServerSocketFactory(new LocalSemaphore(1024)),
            clientFactory: new ConnectionLimitingClientFactory(
                new SocketClientFactory(App::getLogger()),
                App::getLogger(),
                10
            ),
            httpDriverFactory: new DefaultHttpDriverFactory(logger: App::getLogger(), streamTimeout: 60)
        );
        $this->httpServer->expose(
            new Socket\InternetAddress('0.0.0.0', getEnv('HTTP_PORT') ?? 8090),
            (new Socket\BindContext())->withoutTlsContext()
        );

        $router = new Router($this->httpServer, App::getLogger(), $errorHandler);
        $router->setFallback(
            new ClosureRequestHandler(function (Request $request): Response {
                return new Response(
                    HttpStatus::OK,
                    ['content-type' => 'application/json'],
                    json_encode(
                        (object) [
                            'status' => 'success',
                            'data' => [
                                'resultType' => 'vector',
                                'result' => [
                                    [
                                        'metric' => [
                                            'ticker' => 'GOLD',
                                        ],
                                        'value' => [time(), '-0.055555'],
                                    ],
                                ],
                            ],
                        ]
                    )
                );
            })
        );
        $this->httpServer->start($router, $errorHandler);

        $_ENV['API_PROMETHEUS_URL'] = 'http://localhost:' . (getEnv('HTTP_PORT') ?? 8090);
        $this->service = new PrometheusMetricsService();
    }

    /**
     * @return void
     * @throws BufferException
     * @throws StreamException
     * @throws HttpException
     * @covers \TBank\App\Service\PrometheusMetricsService::query
     */
    public function testQuery(): void {
        $metrics = $this->service->query('price');
        $this->assertIsArray($metrics);
        $this->assertGreaterThan(0, count($metrics));
    }

    /**
     * @throws CompositeException
     */
    protected function tearDown(): void {
        parent::tearDown();
        $this->httpServer->stop();
    }
}
