<?php

namespace TBank\Infrastructure\API;

use Amp\ByteStream\BufferException;
use Amp\Http\Client\HttpException;
use Amp\PHPUnit\AsyncTestCase;
use TBank\AsyncTest;
use Throwable;

/**
 * @covers \TBank\Infrastructure\API\Server
 * @runInSeparateProcess
 */
final class ServerTest extends AsyncTest {
    /**
     * @return void
     * @throws BufferException
     * @throws HttpException
     * @throws Throwable
     * @covers \TBank\Infrastructure\API\Server::start()
     */
    public function testServerStart(): void {
        $this->app->addModule(fn(App $app) => Server::getInstance())->start();
        $this->assertEquals('Started', $this->app->getModule(Server::class)->getStatus()->name);
    }
}
