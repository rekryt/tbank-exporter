<?php

namespace TBank\Infrastructure\API;

interface AppModuleInterface {
    public function start(): void;
    public function stop(): void;
}
