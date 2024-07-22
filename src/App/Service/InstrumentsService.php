<?php

namespace TBank\App\Service;

use Amp\Http\Client\HttpException;
use Amp\ByteStream\BufferException;
use Amp\ByteStream\StreamException;

use Monolog\Logger;
use TBank\Domain\Factory\InstrumentFactory;
use TBank\Infrastructure\API\App;
use TBank\Infrastructure\Storage\MainStorage;
use function TBank\dbg;
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
                    $result = $this->getInstrumentBy('INSTRUMENT_ID_TYPE_UID', $result->uid);
                    $tickers[$result->uid] = InstrumentFactory::create($result);
                }
            }
        }
        MainStorage::getInstance()->setTickers($tickers);
    }

    /**
     * @param string $query
     * @param array $options
     * @return ?array
     * @throws BufferException
     * @throws HttpException
     * @throws StreamException
     * @see InstrumentsServiceTest::testServiceLoading()
     */
    public function findInstrument(string $query, array $options = []): ?array {
        $response = $this->httpRequest($this->path . 'FindInstrument', array_merge(['query' => $query], $options));
        return $response ? $response->instruments : null;
    }

    /**
     * @param string $id_type
     * @param string $id
     * @param ?string| $class_code
     * @return ?object
     * @throws BufferException
     * @throws HttpException
     * @throws StreamException
     */
    public function getInstrumentBy(string $id_type, string $id, ?string $class_code = null): ?object {
        $response = $this->httpRequest(
            $this->path . 'GetInstrumentBy',
            array_merge(
                [
                    'id_type' => $id_type,
                    'id' => $id,
                ],
                $class_code ? ['class_code' => $class_code] : []
            )
        );
        return $response ? $response->instrument : null;
    }
}
