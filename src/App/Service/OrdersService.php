<?php

namespace TBank\App\Service;

use TBank\Domain\Factory\OrderFactory;
use TBank\Infrastructure\API\App;
use TBank\Infrastructure\Storage\MainStorage;
use TBank\Infrastructure\Storage\OrdersStorage;

use Amp\Http\Client\HttpException;
use Amp\ByteStream\BufferException;
use Amp\ByteStream\StreamException;

use Monolog\Logger;
use Revolt\EventLoop;

final class OrdersService extends AbstractRestService {
    private string $path = '/rest/tinkoff.public.invest.api.contract.v1.OrdersService/';
    private OrdersStorage $ordersStorage;
    private Logger $logger;

    public function __construct() {
        $this->logger = App::getLogger()->withName('OrdersService');
        parent::__construct($this->logger);

        $this->ordersStorage = OrdersStorage::getInstance();

        /**
         * @throws BufferException
         * @throws StreamException
         * @throws HttpException
         */
        $ordersUpdate = function () {
            $orders = $this->getOrders(MainStorage::getInstance()->getAccount()->id);
            $this->ordersStorage->setData([]);
            foreach ($orders as $order) {
                $this->ordersStorage->set($order->orderId, OrderFactory::create($order));
            }
        };
        $ordersUpdate();
        // авто-обновление заявок
        EventLoop::repeat(60, $ordersUpdate);
    }

    /**
     * @param int|string $account_id
     * @return array|false
     * @throws BufferException
     * @throws HttpException
     * @throws StreamException
     */
    public function getOrders(int|string $account_id): array|false {
        $response = $this->httpRequest($this->path . 'GetOrders', ['account_id' => $account_id]);
        return $response ? $response->orders ?? false : false;
    }
}
