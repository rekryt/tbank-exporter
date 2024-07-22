<?php

namespace TBank\Infrastructure\API;

use TBank\Domain\Strategy\AbstractStrategy;

abstract class AbstractAppModule implements AppModuleInterface {
    /**
     * @var array<AbstractStrategy>
     */
    protected array $strategies;

    /**
     * @param AbstractStrategy $strategy
     * @return AbstractAppModule
     */
    public function addStrategy(AbstractStrategy $strategy): self {
        $this->strategies[$strategy::class] = $strategy;
        return $this;
    }

    /**
     * @param class-string $className
     * @return AbstractStrategy
     */
    public function getStrategy(string $className): AbstractStrategy {
        return $this->strategies[$className];
    }
}
