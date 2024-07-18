<?php

namespace TBank\Infrastructure\API\EventDispatcher;

use Amp\PHPUnit\AsyncTestCase;
use TBank\App\Event\AbstractEvent;
use stdClass;

/**
 * @covers \TBank\Infrastructure\API\EventDispatcher
 * @runInSeparateProcess
 */
final class EventDispatcherTest extends AsyncTestCase {
    /**
     * @var EventDispatcher
     */
    private EventDispatcher $dispatcher;

    /**
     * @return void
     */
    protected function setUp(): void {
        $this->dispatcher = new EventDispatcher(new ListenerProvider());
        parent::setUp();
    }

    /**
     * @return void
     */
    public function testEventDispatch(): void {
        $event = new class extends AbstractEvent {};
        $result = new stdClass();
        $result->calls = 0;
        $this->dispatcher->addEventListener($event::class, function () use ($result) {
            $result->calls++;
        });

        $this->dispatcher->dispatch($event);
        $this->dispatcher->dispatch($event);
        $this->dispatcher->dispatch($event);

        $this->assertEquals(3, $result->calls);
    }

    /**
     * @return void
     */
    public function testAddOnceEventListener(): void {
        $event = new class extends AbstractEvent {};
        $result = new stdClass();
        $result->calls = 0;
        $this->dispatcher->addEventListener(
            $event::class,
            function () use ($result) {
                $result->calls++;
            },
            true
        );

        $this->dispatcher->dispatch($event);
        $this->dispatcher->dispatch($event);
        $this->dispatcher->dispatch($event);

        $this->assertEquals(1, $result->calls);
    }

    /**
     * @return void
     */
    public function testStopPropagation(): void {
        $event = new class extends AbstractEvent {};
        $result = new stdClass();
        $result->calls = 0;
        $this->dispatcher->addEventListener($event::class, function (AbstractEvent $event) use ($result) {
            $result->calls = 2;
            $event->stopPropagation();
        });
        $this->dispatcher->addEventListener($event::class, function () use ($result) {
            $result->calls = 1;
        });

        $this->dispatcher->dispatch($event);

        $this->assertEquals(2, $result->calls);
    }

    /**
     * @return void
     */
    public function testRemoveEventListener(): void {
        $event = new class extends AbstractEvent {};
        $result = new stdClass();
        $result->calls = 0;
        $handler = function () use ($result) {
            $result->calls = 1;
        };

        $this->dispatcher->addEventListener($event::class, $handler);
        $this->dispatcher->removeEventListener($event::class, $handler);
        $this->dispatcher->dispatch($event);

        $this->assertEquals(0, $result->calls);
    }
}
