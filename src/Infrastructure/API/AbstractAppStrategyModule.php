<?php

namespace TBank\Infrastructure\API;

use TBank\Domain\Strategy\AbstractStrategy;

abstract class AbstractAppStrategyModule implements AppStrategyModuleInterface {
    /**
     * @var array<AbstractStrategy>
     */
    protected array $strategies;

    /**
     * @param AbstractStrategy $strategy
     * @return AbstractAppStrategyModule
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
