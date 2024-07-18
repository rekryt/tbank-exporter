<?php

namespace TBank\App\Service;

use TBank\Infrastructure\API\App;
use TBank\Infrastructure\Storage\MainStorage;

use Amp\Http\Client\HttpException;
use Amp\ByteStream\BufferException;
use Amp\ByteStream\StreamException;

use Monolog\Logger;
use Revolt\EventLoop;

final class OperationsService extends AbstractRestService {
    private string $path = '/rest/tinkoff.public.invest.api.contract.v1.OperationsService/';
    private MainStorage $operationsStorage;
    private ?Logger $logger;

    /**
     * @throws BufferException
     * @throws HttpException
     * @throws StreamException
     */
    public function __construct() {
        $this->logger = App::getLogger()->withName('OperationsService');
        parent::__construct($this->logger);

        $this->operationsStorage = MainStorage::getInstance();
        /**
         * @throws BufferException
         * @throws StreamException
         * @throws HttpException
         */
        $ordersUpdate = function () {
            $portfolio = $this->getPortfolio(MainStorage::getInstance()->get('account')->id);
            $this->operationsStorage->set('portfolio', $portfolio);
        };
        $ordersUpdate();
        // авто-обновление портфолио и позиций
        EventLoop::repeat(60, $ordersUpdate);
    }

    /**
     * @param int|string $account_id
     * @return object|false
     * @throws BufferException
     * @throws HttpException
     * @throws StreamException
     */
    public function getPortfolio(int|string $account_id): object|false {
        $response = $this->httpRequest($this->path . 'GetPortfolio', ['account_id' => $account_id]);
        return $response ?: false;
    }
}
