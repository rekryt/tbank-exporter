<?php

namespace TBank\App\Service;

use TBank\Domain\Factory\CandleFactory;
use TBank\Infrastructure\API\App;
use TBank\Infrastructure\Storage\InstrumentsStorage;

use Amp\Websocket\WebsocketClosedException;

use Monolog\Logger;
use Revolt\EventLoop;
use TBank\Infrastructure\Storage\MainStorage;

final class MarketDataStreamService extends AbstractStreamService {
    private string $path = '/tinkoff.public.invest.api.contract.v1.MarketDataStreamService/MarketDataStream';
    private ?Logger $logger;

    public function __construct() {
        $this->logger = App::getLogger()->withName('MarketDataStreamService');
        parent::__construct(
            $this->logger,
            function () {
                $instrumentIds = array_keys(MainStorage::getInstance()->getTickers());
                $this->subscribeLastPriceRequest($instrumentIds);
                EventLoop::delay(5, fn() => $this->subscribeCandlesRequest($instrumentIds));
            },
            function (object $payload) {
                $storage = InstrumentsStorage::getInstance();
                switch (true) {
                    case isset($payload->ping):
                        $this->connection->sendText(
                            json_encode([
                                'ping' => [
                                    'time' => date('Y-m-d\TH:i:s.u\Z'),
                                    'streamId' => $payload->ping->streamId,
                                ],
                            ])
                        );
                        break;
                    case isset($payload->lastPrice):
                        $storage->set(
                            $payload->lastPrice->instrumentUid,
                            $payload->lastPrice->price->units + $payload->lastPrice->price->nano / 1000000000
                        );
                        break;
                    case isset($payload->candle):
                        $storage->setCandle(CandleFactory::create($payload->candle));
                        break;
                }
            }
        );
        EventLoop::defer(fn() => $this->connect($this->path));
    }

    /**
     * @param array $instruments Массив uid инструментов для подписки
     * @param string $subscriptionAction
     * @return void
     * @throws WebsocketClosedException
     */
    public function subscribeLastPriceRequest(
        array $instruments,
        string $subscriptionAction = 'SUBSCRIPTION_ACTION_SUBSCRIBE'
    ): void {
        $body = json_encode([
            'subscribeLastPriceRequest' => [
                'subscriptionAction' => $subscriptionAction,
                'instruments' => array_map(fn(string $instrumentId) => ['instrumentId' => $instrumentId], $instruments),
            ],
        ]);
        $this->logger->notice('subscribeLastPriceRequest', [$body]);
        $this->connection->sendText($body);
    }

    /**
     * Подписка на изменения статуса подписки на свечи
     *
     * SUBSCRIPTION_ACTION_UNSPECIFIED - Статус подписки не определён
     * SUBSCRIPTION_ACTION_SUBSCRIBE - Подписаться
     * SUBSCRIPTION_ACTION_UNSUBSCRIBE - Отписаться
     * @param array $instruments Массив uid инструментов для подписки на свечи.
     * @param string $subscriptionAction Изменение статуса подписки
     * @param string $interval Интервал свечей. Двухчасовые и четырёхчасовые свечи в стриме отсчитываются с 0:00 по UTC.
     * @param bool $waitingClose Флаг ожидания закрытия временного интервала для отправки свечи, применяется только для минутных свечей.
     * @return void
     * @throws WebsocketClosedException
     */
    public function subscribeCandlesRequest(
        array $instruments,
        string $subscriptionAction = 'SUBSCRIPTION_ACTION_SUBSCRIBE',
        string $interval = 'SUBSCRIPTION_INTERVAL_ONE_MINUTE',
        bool $waitingClose = false
    ): void {
        $body = json_encode([
            'subscribeCandlesRequest' => [
                'subscriptionAction' => $subscriptionAction,
                'instruments' => array_map(
                    fn(string $instrumentId) => ['instrument_id' => $instrumentId, 'interval' => $interval],
                    $instruments
                ),
                'waitingClose' => $waitingClose,
            ],
        ]);
        $this->logger->notice('subscribeCandles', [$body]);
        $this->connection->sendText($body);
    }
}
