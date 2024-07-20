<?php

namespace TBank\App\Service;

use TBank\App\Event\StreamEvent;
use TBank\AsyncTest;
use TBank\Infrastructure\API\App;

use Closure;
use Revolt\EventLoop;

/**
 * @runInSeparateProcess
 */
final class OperationsStreamServiceTest extends AsyncTest {
    private Closure $closure;

    protected function setUp(): void {
        $this->service = new OperationsStreamService();
        parent::setUp();
    }

    /**
     * @return void
     * @covers \TBank\App\Service\OperationsStreamService::subscription
     */
    public function testSubscription(): void {
        $suspension = EventLoop::getSuspension();
        $this->closure = function (StreamEvent $event) use ($suspension) {
            if (!isset($event->payload->subscriptions)) {
                return;
            }
            $this->assertGreaterThan(0, count($event->payload->subscriptions->accounts));
            $suspension->resume();
        };
        $this->app->getDispatcher()->addEventListener(StreamEvent::class, $this->closure);
        $suspension->suspend();
    }

    protected function tearDown(): void {
        parent::tearDown();
        $this->app->getDispatcher()->removeEventListener(StreamEvent::class, $this->closure);
    }
}
