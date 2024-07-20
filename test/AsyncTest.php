<?php

namespace TBank;

use Amp\PHPUnit\AsyncTestCase;
use TBank\Infrastructure\API\App;
use TBank\Infrastructure\API\AppModuleInterface;

/**
 * @runInSeparateProcess
 */
abstract class AsyncTest extends AsyncTestCase {
    protected App $app;

    protected function setUp(): void {
        parent::setUp();
        $this->app = App::getInstance();
        foreach ($this->app->getModules() as $module) {
            $this->app->removeModule($module::class);
        }
    }

    protected function tearDown(): void {
        parent::tearDown();
        $this->app->stop();
        $_ENV['HTTP_PORT'] = (getEnv('HTTP_PORT') ?? 8090) + 1;
    }
}
