<?php

namespace TBank\Infrastructure\API;

use TBank\Domain\Strategy\AbstractStrategy;

interface AppStrategyModuleInterface extends AppModuleInterface {
    public function addStrategy(AbstractStrategy $strategy): self;
    public function getStrategy(string $className): AbstractStrategy;
}
