<?php

namespace TBank\App\Controller;

use Amp\ByteStream\BufferException;
use Amp\Http\Client\HttpException;
use Amp\Http\HttpStatus;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;

use TBank\App\Service\InstrumentsService;
use TBank\App\Service\MarketDataStreamService;
use TBank\App\Service\OperationsService;
use TBank\App\Service\OperationsStreamService;
use TBank\App\Service\OrdersService;
use TBank\App\Service\OrdersStreamService;
use TBank\Domain\Entity\AmountEntity;
use TBank\Domain\Entity\CandleEntity;
use TBank\Domain\Entity\OrderEntity;
use TBank\Domain\Entity\SignalEntity;
use TBank\Domain\Factory\AmountFactory;
use TBank\Domain\Factory\PositionFactory;
use TBank\Infrastructure\API\App;
use TBank\Infrastructure\Storage\InstrumentsStorage;
use TBank\Infrastructure\Storage\MainStorage;
use TBank\Infrastructure\Storage\OrdersStorage;

use Monolog\Logger;

use Throwable;
use function TBank\getEnv;

class MetricsController extends AbstractController {
    private OrdersStorage $ordersStorage;
    private InstrumentsStorage $instrumentsStorage;
    private MainStorage $mainStorage;
    private Logger $logger;

    /**
     * @param Request $request
     * @param array $headers
     * @throws BufferException
     * @throws HttpException
     * @throws Throwable
     */
    public function __construct(protected Request $request, protected array $headers = []) {
        parent::__construct($request, $this->headers);
        $this->logger = App::getLogger();
        // общее хранилище
        $this->mainStorage = MainStorage::getInstance();
        // хранилище котировок
        $this->instrumentsStorage = InstrumentsStorage::getInstance();
        // хранилище заявок
        $this->ordersStorage = OrdersStorage::getInstance();
    }

    /**
     * @param string $name
     * @param string[] $data
     * @param string $type
     * @param string $description
     * @return string
     */
    private function getMetric(string $name, array $data, string $type = 'gauge', string $description = ''): string {
        return implode(
            "\n",
            array_merge(['# HELP ' . $name . ' ' . $description, '# TYPE ' . $name . ' ' . $type], $data)
        ) . "\n";
    }

    /**
     * @return string
     */
    public function getBody(): string {
        $metricsNames = [
            'portfolio' => getEnv('METRICS_PORTFOLIO') ?? 'portfolio',
            'price' => getEnv('METRICS_PRICE') ?? 'price',
            'order_counts' => getEnv('METRICS_ORDER_COUNTS') ?? 'order_counts',
            'order_totals' => getEnv('METRICS_ORDER_TOTALS') ?? 'order_totals',
            'positions_count' => getEnv('METRICS_POSITIONS_COUNT') ?? 'positions_count',
            'positions_price' => getEnv('METRICS_POSITIONS_PRICE') ?? 'positions_price',
            'signal' => getEnv('METRICS_SIGNAL') ?? 'signal',
            'volume' => getEnv('METRICS_VOLUME') ?? 'volume',
        ];
        $result = '';

        // тикеры
        $tickers = $this->mainStorage->getTickers();
        $instruments = [];
        foreach ($this->instrumentsStorage->getData() as $uid => $value) {
            if (!isset($tickers[$uid])) {
                continue;
            }
            $instruments[] = $metricsNames['price'] . '{ticker="' . $tickers[$uid]->ticker . '"} ' . $value;
        }
        $result .= $this->getMetric($metricsNames['price'], $instruments, 'gauge', 'price');

        // заявки
        $counts = [];
        $totals = [];
        /** @var OrderEntity $order */
        foreach ($this->ordersStorage->getData() as $order) {
            if (
                !in_array($order->executionReportStatus, [
                    'EXECUTION_REPORT_STATUS_NEW',
                    'EXECUTION_REPORT_STATUS_PARTIALLYFILL',
                ])
            ) {
                continue;
            }
            $labels = implode(',', [
                'ticker="' . $tickers[$order->instrumentUid]->ticker . '"',
                'executionReportStatus="' . $order->executionReportStatus . '"',
                'direction="' . $order->direction . '"',
                'type="' . $order->orderType . '"',
                'currency="' . strtoupper($order->totalOrderAmount->currency) . '"',
            ]);
            if (!isset($counts[$labels])) {
                $counts[$labels] = 0;
            }
            $counts[$labels] += $order->lotsRequested;

            if (!isset($totals[$labels])) {
                $totals[$labels] = 0;
            }
            $totals[$labels] += $order->totalOrderAmount->units + $order->totalOrderAmount->nano / 1000000000;
        }
        $orders = ['counts' => [], 'totals' => []];
        foreach ($counts as $labels => $count) {
            $orders['counts'][] = $metricsNames['order_counts'] . '{' . $labels . '} ' . $count;
        }
        foreach ($totals as $labels => $count) {
            $orders['totals'][] = $metricsNames['order_totals'] . '{' . $labels . '} ' . $count;
        }
        $result .= $this->getMetric($metricsNames['order_counts'], $orders['counts'], 'gauge', 'order_counts');
        $result .= $this->getMetric($metricsNames['order_totals'], $orders['totals'], 'gauge', 'order_totals');

        // портфель и позиции
        $portfolio = $this->mainStorage->getPortfolio();
        $params = []; // параметры портфеля
        if ($portfolio) {
            foreach ($portfolio as $key => $value) {
                if ($value instanceof AmountEntity) {
                    $params[] = $metricsNames['portfolio'] . '{label="' . $key . '"} ' . $value->units;
                }
            }
            $result .= $this->getMetric($metricsNames['portfolio'], $params, 'gauge', 'portfolio');

            if (isset($portfolio->positions)) {
                $positions = ['count' => [], 'price' => []]; // позиции
                foreach ($portfolio->positions as $position) {
                    $search = array_filter($tickers, fn($item) => $item->figi == $position->figi);
                    if (count($search) || $position->instrumentType == 'currency') {
                        $tickerLabel = isset($search[$position->instrumentUid])
                            ? $search[$position->instrumentUid]->ticker
                            : $position->figi;
                        $positions['count'][] =
                            $metricsNames['positions_count'] .
                            '{ticker="' .
                            $tickerLabel .
                            '"} ' .
                            $position->quantity->get();
                        $positions['price'][] =
                            $metricsNames['positions_price'] .
                            '{ticker="' .
                            $tickerLabel .
                            '"} ' .
                            $position->currentPrice->get();
                    }
                }
                $result .= $this->getMetric(
                    $metricsNames['positions_count'],
                    $positions['count'],
                    'gauge',
                    'positions_count'
                );
                $result .= $this->getMetric(
                    $metricsNames['positions_price'],
                    $positions['price'],
                    'gauge',
                    'positions_price'
                );
            }
        }

        // сигналы
        $signals = array_map(
            fn(SignalEntity $signal) => $metricsNames['signal'] .
                '{ticker="' .
                $signal->ticker .
                '",name="' .
                $signal->name .
                '"} ' .
                $signal->value,
            $this->mainStorage->getSignals()
        );
        $result .= $this->getMetric($metricsNames['signal'], $signals, 'gauge', 'signal');

        // обьёмы продаж
        $volumes = array_map(function (CandleEntity $candle) use ($metricsNames) {
            $instrument =
                $this->mainStorage->getTicker($candle->instrumentUid) ??
                $this->mainStorage->getTickerByFIGI($candle->figi);
            return $metricsNames['volume'] .
                '{ticker="' .
                $instrument->ticker .
                '"} ' .
                $candle->volume * $instrument->lot;
        }, $this->instrumentsStorage->getCandles());
        $result .= $this->getMetric($metricsNames['volume'], $volumes, 'gauge', 'volume');

        return $result;
    }

    /**
     * @return Response
     */
    public function __invoke(): Response {
        return new Response(HttpStatus::OK, ['content-type' => 'text/plain; version=0.0.4'], $this->getBody());
    }
}
