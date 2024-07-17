<?php

namespace TBank\App\Service;

use TBank\Infrastructure\Storage\InstrumentsStorage;

use Amp\Websocket\WebsocketClosedException;

use Monolog\Logger;
use Revolt\EventLoop;

final class MarketDataStreamService extends AbstractStreamService {
    private string $path = '/tinkoff.public.invest.api.contract.v1.MarketDataStreamService/MarketDataStream';

    /**
     * @param Logger $logger
     * @param array $tickers
     */
    public function __construct(private Logger $logger, private readonly array $tickers = []) {
        $this->logger = $this->logger->withName('MarketDataStreamService');
        parent::__construct(
            $this->logger,
            function () {
                $this->subscribeLastPriceRequest(array_keys($this->tickers));
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
                }
            }
        );
        EventLoop::defer(fn() => $this->connect($this->path));
    }

    /**
     * @param array $instruments
     * @return void
     * @throws WebsocketClosedException
     */
    public function subscribeLastPriceRequest(array $instruments): void {
        $body = json_encode([
            'subscribeLastPriceRequest' => [
                'subscriptionAction' => 'SUBSCRIPTION_ACTION_SUBSCRIBE',
                'instruments' => array_map(fn(string $instrumentId) => ['instrumentId' => $instrumentId], $instruments),
            ],
        ]);
        $this->logger->notice('subscribeLastPriceRequest', [$body]);
        $this->connection->sendText($body);
    }
}
