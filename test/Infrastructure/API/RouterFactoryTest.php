<?php

namespace TBank\Infrastructure\API;

use Amp\ByteStream\BufferException;
use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\HttpException;
use Amp\Http\Client\Request;
use Amp\PHPUnit\AsyncTestCase;

use Revolt\EventLoop;
use TBank\AsyncTest;
use Throwable;

use function Amp\delay;
use function TBank\getEnv;

/**
 * @covers RouterFactory
 * @runInSeparateProcess
 */
final class RouterFactoryTest extends AsyncTest {
    /**
     * @return void
     * @throws BufferException
     * @throws HttpException
     * @throws Throwable
     */
    protected function setUp(): void {
        parent::setUp();
        $this->app->addModule(fn(App $app) => Server::getInstance(routerFactory: new RouterFactory()));
        $this->app->start();
    }

    /**
     * @return void
     * @throws HttpException
     * @throws Throwable
     * @covers \TBank\Infrastructure\API\RouterFactory::create()
     */
    public function testCreate(): void {
        $httpClient = (new HttpClientBuilder())->build();
        $request = new Request('http://localhost:' . getEnv('HTTP_PORT') . '/metrics', 'GET');
        $response = $httpClient->request($request);
        $this->assertEquals('text/plain; version=0.0.4', $response->getHeader('content-type'));
    }
}
