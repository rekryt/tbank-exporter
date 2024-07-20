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
final class MarketDataStreamServiceTest extends AsyncTest {
    private Closure $closure;
    protected function setUp(): void {
        parent::setUp();
        $this->service = new MarketDataStreamService();
    }

    /**
     * @return void
     * @covers \TBank\App\Service\InstrumentsService::findInstrument
     */
    public function testSubscribeLastPriceRequest(): void {
        $suspension = EventLoop::getSuspension();
        $this->closure = function (StreamEvent $event) use ($suspension) {
            $this->assertTrue(isset($event->payload->subscribeLastPriceResponse));
            $this->assertIsString($event->payload->subscribeLastPriceResponse->trackingId);
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
