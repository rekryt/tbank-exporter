<?php

namespace TBank\App\Service;

use TBank\Infrastructure\API\App;
use TBank\Infrastructure\Storage\MainStorage;

use Amp\Websocket\WebsocketClosedException;

use Monolog\Logger;
use Revolt\EventLoop;

final class OperationsStreamService extends AbstractStreamService {
    private string $path = '/tinkoff.public.invest.api.contract.v1.OperationsStreamService/PortfolioStream';
    private ?Logger $logger;

    /**
     * @param array $accounts
     */
    public function __construct(private readonly array $accounts = []) {
        $this->logger = App::getLogger()->withName('OperationsStreamService');
        parent::__construct(
            $this->logger,
            function () {
                $this->subscription($this->accounts);
            },
            function (object $payload) {
                $storage = MainStorage::getInstance();
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
                    case isset($payload->portfolio):
                        $storage->set('portfolio', $payload->portfolio);
                        break;
                }
            }
        );
        EventLoop::defer(fn() => $this->connect($this->path));
    }

    /**
     * @param array $accounts
     * @return void
     * @throws WebsocketClosedException
     */
    public function subscription(array $accounts): void {
        $body = json_encode([
            'accounts' => $accounts,
        ]);
        $this->logger->notice('OperationsStreamService subscription', [$body]);
        $this->connection->sendText($body);
    }
}
