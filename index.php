<?php

use TBank\Infrastructure\API\App;
use TBank\Infrastructure\API\Server;
use TBank\Infrastructure\API\TradingModule;

require_once 'vendor/autoload.php';

App::getInstance()
    ->addHandler((fn(App $app) => Server::getInstance()))
    ->addHandler((fn(App $app) => TradingModule::getInstance()))
    ->start();
