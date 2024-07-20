<?php

namespace TBank\App\Service;

use TBank\App\Event\StreamEvent;
use TBank\AsyncTest;

use Closure;
use Revolt\EventLoop;

/**
 * @runInSeparateProcess
 */
final class OrdersStreamServiceTest extends AsyncTest {
    private Closure $closure;

    protected function setUp(): void {
        parent::setUp();
        $this->service = new OrdersStreamService();
    }

    /**
     * @return void
     * @covers \TBank\App\Service\OrdersStreamService::subscription
     */
    public function testSubscription(): void {
        $suspension = EventLoop::getSuspension();
        $this->closure = function (StreamEvent $event) use ($suspension) {
            if (!isset($event->payload->subscription)) {
                return;
            }
            $this->assertIsString($event->payload->subscription->trackingId);
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
