<?php

namespace TBank\Infrastructure\API;

use Amp\PHPUnit\AsyncTestCase;
use stdClass;
use TBank\AsyncTest;

/**
 * @covers \TBank\Infrastructure\API\App
 * @runInSeparateProcess
 */
final class AppTest extends AsyncTest {
    /**
     * @return void
     * @covers \TBank\Infrastructure\API\App::start()
     */
    public function testAppStart(): void {
        $result = new stdClass();
        $result->isStarted = false;
        $this->app->addModule(function (App $app) use (&$result) {
            return new class ($result) implements AppModuleInterface {
                public function __construct(private readonly object $result) {
                }
                public function start(): void {
                    $this->result->isStarted = true;
                }
                public function stop(): void {
                    $this->result->isStarted = false;
                }
            };
        });
        $this->assertTrue(!$result->isStarted);
        $this->app->start();
        $this->assertTrue(!!$result->isStarted);
        $this->app->stop();
        $this->assertTrue(!$result->isStarted);
    }
}
