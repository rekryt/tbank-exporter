<?php

namespace TBank\App\Service;

use Amp\Http\Client\HttpException;
use Amp\ByteStream\BufferException;
use Amp\ByteStream\StreamException;

use Monolog\Logger;
use TBank\Domain\Factory\InstrumentFactory;
use TBank\Infrastructure\API\App;
use TBank\Infrastructure\Storage\MainStorage;
use function TBank\getEnv;

final class InstrumentsService extends AbstractRestService {
    private string $path = '/rest/tinkoff.public.invest.api.contract.v1.InstrumentsService/';
    private ?Logger $logger;

    /**
     * @throws BufferException
     * @throws HttpException
     * @throws StreamException
     */
    public function __construct() {
        $this->logger = App::getLogger()->withName('InstrumentsService');
        parent::__construct($this->logger);

        // получение тикеров
        /** @var InstrumentsService $instrumentsService */
        $instruments = explode('|', getEnv('API_TICKERS') ?? '');
        $tickers = [];
        foreach ($instruments as $instrument) {
            [$ticker, $figi] = explode(':', $instrument);
            foreach ($this->findInstrument($figi ?: $ticker) as $result) {
                if ($result->figi == $figi) {
                    $tickers[$result->uid] = $result;
                }
            }
        }
        MainStorage::getInstance()->setTickers(array_map(fn($item) => InstrumentFactory::create($item), $tickers));
    }

    /**
     * @param string $query
     * @param array $options
     * @return array|false
     * @throws BufferException
     * @throws HttpException
     * @throws StreamException
     * @see InstrumentsServiceTest::testServiceLoading()
     */
    public function findInstrument(string $query, array $options = []): array|false {
        $response = $this->httpRequest($this->path . 'FindInstrument', array_merge(['query' => $query], $options));
        return $response ? $response->instruments ?? false : false;
    }
}
