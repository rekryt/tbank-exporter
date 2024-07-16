<?php

namespace TBank\App\Service;

use Amp\Http\Client\HttpClient;
use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\HttpException;
use Amp\Http\Client\Request;
use Amp\Http\Client\Response;
use Amp\ByteStream\BufferException;
use Amp\ByteStream\StreamException;

use Exception;
use Monolog\Logger;

use function TBank\getEnv;

class InstrumentsService extends AbstractRestService {
    private string $path = '/rest/tinkoff.public.invest.api.contract.v1.InstrumentsService/';

    public function __construct(private readonly Logger $logger) {
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
