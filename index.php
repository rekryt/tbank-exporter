<?php

use TBank\Infrastructure\API\App;
use TBank\Infrastructure\API\Server;

require_once 'vendor/autoload.php';

App::getInstance()
    ->addHandler((fn(App $app) => Server::getInstance()))
    ->start();
