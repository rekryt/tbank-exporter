<?php

namespace TBank\App\Service;

use Amp\Http\Client\HttpException;
use Amp\ByteStream\BufferException;
use Amp\ByteStream\StreamException;

use Monolog\Logger;

final class InstrumentsService extends AbstractRestService {
    private string $path = '/rest/tinkoff.public.invest.api.contract.v1.InstrumentsService/';

    public function __construct(private Logger $logger) {
        $this->logger = $this->logger->withName('InstrumentsService');
        parent::__construct($this->logger);
    }

    /**
     * @param string $query
     * @param array $options
     * @return array|false
     * @throws BufferException
     * @throws HttpException
     * @throws StreamException
     */
    public function findInstrument(string $query, array $options = []): array|false {
        $response = $this->httpRequest($this->path . 'FindInstrument', array_merge(['query' => $query], $options));
        return $response ? $response->instruments ?? false : false;
    }
}
