<?php

namespace TBank\App\Service;

use TBank\Infrastructure\Storage\MainStorage;

use Amp\Http\Client\HttpException;
use Amp\ByteStream\BufferException;
use Amp\ByteStream\StreamException;

use Monolog\Logger;

final class OperationsService extends AbstractRestService {
    private string $path = '/rest/tinkoff.public.invest.api.contract.v1.OperationsService/';
    private MainStorage $operationsStorage;

    /**
     * @param Logger $logger
     * @param string $account_id
     * @throws BufferException
     * @throws HttpException
     * @throws StreamException
     */
    public function __construct(private Logger $logger, string $account_id) {
        $this->logger = $this->logger->withName('OperationsService');
        parent::__construct($this->logger);

        $this->operationsStorage = MainStorage::getInstance();
        $portfolio = $this->getPortfolio($account_id);
        $this->operationsStorage->set('portfolio', $portfolio);
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
