<?php

namespace TBank\Domain\Strategy;

use Closure;
use Monolog\Logger;
use TBank\App\Event\SignalEvent;
use TBank\Infrastructure\API\App;
use TBank\Infrastructure\API\EventDispatcher\EventDispatcher;

abstract class AbstractStrategy {
    protected EventDispatcher $dispather;

    public function __construct(private ?Logger $logger = null) {
        $this->logger = $logger ?? App::getLogger();
        $this->dispather = App::getInstance()->getDispatcher();

        $this->dispather->addEventListener(SignalEvent::class, $this->getHandler());
        $this->logger->notice('started');
    }

    public function __destruct() {
        $this->dispather->addEventListener(SignalEvent::class, $this->getHandler());
        $this->logger->notice('stopped');
    }

    abstract function getHandler(): Closure;
}
