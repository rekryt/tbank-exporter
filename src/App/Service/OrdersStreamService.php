<?php

namespace TBank\App\Service;

use TBank\Domain\Entity\OrderEntity;
use TBank\Domain\Factory\AmountFactory;
use TBank\Domain\Factory\OrderFactory;
use TBank\Infrastructure\API\App;
use TBank\Infrastructure\Storage\MainStorage;
use TBank\Infrastructure\Storage\OrdersStorage;

use Amp\Websocket\WebsocketClosedException;

use Monolog\Logger;
use Revolt\EventLoop;

final class OrdersStreamService extends AbstractStreamService {
    private string $path = '/tinkoff.public.invest.api.contract.v1.OrdersStreamService/OrderStateStream';
    private Logger $logger;

    public function __construct() {
        $this->logger = App::getLogger()->withName('OrdersStreamService');
        parent::__construct(
            $this->logger,
            function () {
                $this->subscription([MainStorage::getInstance()->getAccount()->id]);
            },
            function (object $payload) {
                $storage = OrdersStorage::getInstance();
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
                    case isset($payload->orderState):
                        $orderId = $payload->orderState->orderId;
                        if (strpos($orderId, '-')) {
                            break;
                        }

                        /** @var OrderEntity $order */
                        $order = $storage->has($orderId)
                            ? $storage->get($orderId)
                            : OrderFactory::create($payload->orderState);
                        $order->totalOrderAmount = AmountFactory::create($payload->orderState->amount);
                        $order->direction = $payload->orderState->direction;
                        $order->executionReportStatus = $payload->orderState->executionReportStatus;
                        $order->lotsExecuted = $payload->orderState->lotsExecuted;
                        $order->lotsRequested = $payload->orderState->lotsRequested;
                        $order->orderType = $payload->orderState->orderType;

                        $storage->set($order->orderId, $order);
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
     * @see OrdersStreamServiceTest::testSubscription()
     */
    public function subscription(array $accounts): void {
        $body = json_encode([
            'accounts' => $accounts,
        ]);
        $this->logger->notice('OrdersStreamService subscription', [$body]);
        $this->connection->sendText($body);
    }
}
