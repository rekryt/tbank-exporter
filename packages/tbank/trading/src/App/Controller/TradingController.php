<?php

namespace TBank\App\Controller;

use TBank\Infrastructure\Storage\InstrumentsStorage;
use TBank\Infrastructure\Storage\MainStorage;

use Amp\Http\HttpStatus;
use Amp\Http\Server\Response;
use Exception;

class TradingController extends AbstractController {
    /**
     * @return Response
     * @throws Exception
     */
    public function __invoke(): Response {
        return new Response(HttpStatus::OK, ['content-type' => 'application/json; charset=utf-8'], $this->getBody());
    }
    public function getBody(): string {
        $storage = MainStorage::getInstance();
        return json_encode(
            [
                'account' => $storage->getAccount(),
                'portfolio' => $storage->getPortfolio(),
                'tickers' => $storage->getTickers(),
                'signals' => $storage->getSignals(),
                'candles' => InstrumentsStorage::getInstance()->getCandles(),
                'instruments' => InstrumentsStorage::getInstance()->getData(),
            ],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
        );
    }
}
