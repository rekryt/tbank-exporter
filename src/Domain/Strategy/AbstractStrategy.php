<?php

namespace TBank\Domain\Strategy;

use TBank\Infrastructure\API\App;
use TBank\Infrastructure\API\EventDispatcher\EventDispatcher;

use Monolog\Logger;

abstract class AbstractStrategy {
    protected EventDispatcher $dispather;

    public function __construct(private ?Logger $logger = null) {
        $this->logger = $logger ?? App::getLogger();
        $this->dispather = App::getInstance()->getDispatcher();

        $this->logger->notice('started');
    }

    public function __destruct() {
        $this->logger->notice('stopped');
    }
}
