<?php

use TBank\Domain\Strategy\ExactStrategy;
use TBank\Domain\Strategy\SMAStrategy;
use TBank\Infrastructure\API\App;
use TBank\Infrastructure\API\Server;
use TBank\Infrastructure\API\TradingModule;

use TBank\App\Service\InstrumentsService;
use TBank\App\Service\MarketDataStreamService;
use TBank\App\Service\OperationsService;
use TBank\App\Service\OperationsStreamService;
use TBank\App\Service\OrdersService;
use TBank\App\Service\OrdersStreamService;
use TBank\App\Service\PrometheusMetricsService;
use TBank\App\Service\UsersService;

require_once 'vendor/autoload.php';

App::getInstance()
    // модули
    ->addModule((fn(App $app) => Server::getInstance())) // веб-сервер
    ->addModule(
        (fn(App $app) => TradingModule::getInstance() // модуль торговли
            ->addStrategy(new ExactStrategy())
            ->addStrategy(new SMAStrategy()))
    )
    // сервисы
    ->addService(new InstrumentsService()) // получение списка тикеров
    ->addService(new UsersService()) // получение account_id
    ->addService(new MarketDataStreamService()) // подписка на тикеры
    ->addService(new OperationsService()) // получение портфеля и позиций
    ->addService(new OperationsStreamService()) // подписка на портфель и позиции
    ->addService(new OrdersService()) // получение заявок
    ->addService(new OrdersStreamService()) // подписка на заявки
    ->addService(
        new PrometheusMetricsService() // обновление кастомных метрик из прометеуса
    )
    ->start();
