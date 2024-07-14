<?php

namespace TBank\App\Controller;

use Amp\ByteStream\BufferException;
use Amp\ByteStream\StreamException;
use Amp\Http\Client\HttpException;
use Amp\Http\HttpStatus;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Monolog\Logger;
use Revolt\EventLoop;
use TBank\App\Service\InstrumentsService;
use TBank\App\Service\MarketDataStreamService;
use TBank\Infrastructure\Storage\Storage;
use function TBank\getEnv;
use function TBank\dbg;

class MainController {
    private Storage $storage;
    private array $tickers = [];

    /**
     * @param Logger $logger
     * @throws BufferException
     * @throws StreamException
     * @throws HttpException
     */
    public function __construct(private readonly Logger $logger) {
        $this->storage = Storage::getInstance();
        $instrumentsService = new InstrumentsService($this->logger);

        $instruments = explode('|', getEnv('API_TICKERS') ?? '');
        foreach ($instruments as $instrument) {
            [$ticker, $figi] = explode(':', $instrument);
            foreach ($instrumentsService->findInstrument($figi ?: $ticker) as $result) {
                if ($result->figi == $figi) {
                    $this->tickers[$result->uid] = $ticker;
                }
            }
        }

        $marketDataStreamService = new MarketDataStreamService($this->logger, function () use (
            &$marketDataStreamService
        ) {
            $marketDataStreamService->subscribeLastPriceRequest(array_keys($this->tickers));
        });

        $this->logger->info('Main controller ready', [$this->tickers]);
        // EventLoop::delay(5, fn() => $marketDataStreamService->subscribeLastPriceRequest(array_keys($this->tickers)));
        //dbg($tickers);
    }

    private function getBody(): string {
        $result = implode("\n", ['# HELP price price', '# TYPE price gauge']) . "\n";
        foreach ($this->storage->getData() as $uid => $value) {
            if (!isset($this->tickers[$uid])) continue;
            $result .= 'price{ticker="' . $this->tickers[$uid] . '"} ' . $value . "\n";
        }
        return $result;
    }

    public function __invoke(Request $request): Response {
        return new Response(HttpStatus::OK, ['content-type' => 'text/plain; version=0.0.4'], $this->getBody());
    }
}
