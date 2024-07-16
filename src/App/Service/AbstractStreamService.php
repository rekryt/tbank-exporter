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
use TBank\Infrastructure\Storage\InstrumentsStorage;
use function Amp\async;
use function Amp\delay;
use function TBank\getEnv;

class AbstractStreamService {
    private Rfc6455Connector $wsClient;
    private string $token;
    protected WebsocketConnection $connection;
    private Closure $onConnected;
    private Closure $onMessage;
    private string $listener = '';

    /**
     * @param Logger $logger
     * @param ?Closure $onConnected
     * @param ?Closure $onMessage
     */
    public function __construct(
        private readonly Logger $logger,
        Closure $onConnected = null,
        Closure $onMessage = null
    ) {
        $this->wsClient = new Rfc6455Connector(
            new Rfc6455ConnectionFactory(
                heartbeatQueue: new PeriodicHeartbeatQueue(heartbeatPeriod: 30),
                parserFactory: new Rfc6455ParserFactory(messageSizeLimit: PHP_INT_MAX, frameSizeLimit: PHP_INT_MAX)
            )
        );
        $this->token = getEnv('API_TOKEN') ?? '';
        $this->onConnected = $onConnected ?? fn() => 0;
        $this->onMessage = $onMessage ?? fn() => 0;
    }

    /**
     * @throws HttpException
     * @throws WebsocketConnectException
     */
    public function connect(string $path): void {
        if ($this->listener) {
            EventLoop::cancel($this->listener);
        }
        if (!empty($this->connection) && !$this->connection->isClosed()) {
            $this->connection->close();
        }

        $url = (getEnv('API_URL_WS') ?? 'wss://invest-public-api.tinkoff.ru/ws') . $path;
        $this->logger->notice('WS connecting', [$url]);
        try {
            $this->connection = $this->wsClient->connect(
                new WebsocketHandshake($url, [
                    'Web-Socket-Protocol' => 'json',
                    'Authorization' => 'Bearer ' . $this->token,
                ])
            );

            $this->connection->onClose(function () use ($path) {
                $this->logger->notice('WS connection closed', []);
                EventLoop::delay(5, fn() => $this->connect($path));
            });

            $this->logger->info('WS connected', [$url]);
            ($this->onConnected)();
        } catch (SocketException $e) {
            $this->logger->notice('WS connection socket error', []);
            EventLoop::delay(5, fn() => $this->connect($path));
        }

        $this->listener = EventLoop::defer(function () {
            while ($message = $this->connection->receive()) {
                $payload = json_decode($message->buffer());

                $this->logger->info('WS Received', [$payload]);
                ($this->onMessage)($payload);
            }
        });
    }
}
