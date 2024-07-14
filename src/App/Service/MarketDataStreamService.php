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
use Closure;
use Monolog\Logger;
use Revolt\EventLoop;
use TBank\Infrastructure\Storage\Storage;
use function Amp\async;
use function Amp\delay;
use function TBank\getEnv;

class MarketDataStreamService {
    private string $path = '/tinkoff.public.invest.api.contract.v1.MarketDataStreamService/MarketDataStream';

    private Rfc6455Connector $wsClient;
    private string $token;
    private WebsocketConnection $connection;
    private Closure $onConnected;
    private string $listener = '';

    /**
     * @param Logger $logger
     * @param ?Closure $onConnected
     */
    public function __construct(private readonly Logger $logger, Closure $onConnected = null) {
        $this->wsClient = new Rfc6455Connector(
            new Rfc6455ConnectionFactory(
                heartbeatQueue: new PeriodicHeartbeatQueue(heartbeatPeriod: 30),
                parserFactory: new Rfc6455ParserFactory(messageSizeLimit: PHP_INT_MAX, frameSizeLimit: PHP_INT_MAX)
            )
        );
        $this->token = getEnv('API_TOKEN') ?? '';
        $this->onConnected = $onConnected ?? fn() => 0;

        EventLoop::defer($this->connect(...));
    }

    /**
     * @throws HttpException
     * @throws WebsocketConnectException
     */
    public function connect(): void {
        if ($this->listener) {
            EventLoop::cancel($this->listener);
        }
        if (!empty($this->connection) && !$this->connection->isClosed()) {
            $this->connection->close();
        }

        $url = (getEnv('API_URL_WS') ?? 'wss://invest-public-api.tinkoff.ru/ws') . $this->path;
        $this->logger->notice('WS connecting', [$url]);
        try {
            $this->connection = $this->wsClient->connect(
                new WebsocketHandshake($url, [
                    'Web-Socket-Protocol' => 'json',
                    'Authorization' => 'Bearer ' . $this->token,
                ])
            );
            $this->logger->info('WS connected', [$url]);
            $this->connection->onClose(function () {
                $this->logger->notice('WS connection closed', []);
                EventLoop::delay(5, fn() => $this->connect());
            });

            ($this->onConnected)();

        } catch (SocketException $e) {
            $this->logger->notice('WS connection socket error', []);
            EventLoop::delay(5, fn() => $this->connect());
        }

        //        $this->subscribeLastPriceRequest([
        //            '962e2a95-02a9-4171-abd7-aa198dbe643a',
        //            '81a9e64b-e9bc-4bb4-8940-1ae2d22e3745',
        //            'b91e5a2b-d8a1-4a42-b73e-45152b34edd7',
        //            '4c466956-d2ce-4a95-abb4-17947a65f18a',
        //            'd285d62a-d618-492a-861f-b4a122ef0475',
        //        ]);

        $this->listener = EventLoop::defer(function () {
            while ($message = $this->connection->receive()) {
                $storage = Storage::getInstance();
                $payload = json_decode($message->buffer());
                $this->logger->info('WS Received', [$payload]);

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
        });
    }

    public function subscribeLastPriceRequest(array $instruments) {
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
