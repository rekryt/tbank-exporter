<?php

namespace TBank\App\Service;

use Amp\Http\Client\HttpException;
use Amp\Http\Client\SocketException;
use Amp\TimeoutCancellation;
use Amp\Websocket\Client\Rfc6455ConnectionFactory;
use Amp\Websocket\Client\Rfc6455Connector;
use Amp\Websocket\Client\WebsocketConnectException;
use Amp\Websocket\Client\WebsocketConnection;
use Amp\Websocket\Client\WebsocketHandshake;
use Amp\Websocket\Parser\Rfc6455ParserFactory;
use Amp\Websocket\PeriodicHeartbeatQueue;
use Amp\Websocket\WebsocketClosedException;
use Closure;
use Monolog\Logger;
use Revolt\EventLoop;
use TBank\Infrastructure\Storage\InstrumentsStorage;
use function Amp\async;
use function Amp\delay;
use function TBank\getEnv;

class MarketDataStreamService extends AbstractStreamService {
    private string $path = '/tinkoff.public.invest.api.contract.v1.MarketDataStreamService/MarketDataStream';
    private Closure $onConnected;

    /**
     * @param Logger $logger
     * @param array $tickers
     */
    public function __construct(private readonly Logger $logger, private readonly array $tickers = []) {
        $this->onConnected = $onConnected ?? fn() => 0;
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
                        $this->logger->notice('WS ping');
                        break;
                    case isset($payload->lastPrice):
                        $storage->set(
                            $payload->lastPrice->instrumentUid,
                            $payload->lastPrice->price->units + $payload->lastPrice->price->nano / 1000000000
                        );
                        $this->logger->notice('WS lastPrice');
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
