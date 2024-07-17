<?php

namespace TBank\App\Controller;

use Amp\ByteStream\BufferException;
use Amp\ByteStream\StreamException;
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
use TBank\Domain\Entity\OrderEntity;
use TBank\Domain\Factory\AmountFactory;
use TBank\Domain\Factory\PositionFactory;
use TBank\Infrastructure\Storage\InstrumentsStorage;
use TBank\Infrastructure\Storage\MainStorage;
use TBank\Infrastructure\Storage\OrdersStorage;

use Monolog\Logger;

use function TBank\dbg;
use function TBank\getEnv;

class MainController {
    private array $services = [];
    private OrdersStorage $ordersStorage;
    private InstrumentsStorage $instrumentsStorage;
    private MainStorage $mainStorage;

    /**
     * @param Logger $logger
     * @throws BufferException
     * @throws StreamException
     * @throws HttpException
     */
    public function __construct(private readonly Logger $logger) {
        // общее хранилище
        $this->mainStorage = MainStorage::getInstance();
        // хранилище котировок
        $this->instrumentsStorage = InstrumentsStorage::getInstance();
        // хранилище заявок
        $this->ordersStorage = OrdersStorage::getInstance();

        $account_id = '2024854739';

        // получение тикеров
        $this->services[InstrumentsService::class] = new InstrumentsService($this->logger);
        $instruments = explode('|', getEnv('API_TICKERS') ?? '');
        $tickers = [];
        foreach ($instruments as $instrument) {
            [$ticker, $figi] = explode(':', $instrument);
            foreach ($this->services[InstrumentsService::class]->findInstrument($figi ?: $ticker) as $result) {
                if ($result->figi == $figi) {
                    $tickers[$result->uid] = $result;
                }
            }
        }
        $this->mainStorage->set('tickers', $tickers);
        // подписка на тикеры
        $this->services[MarketDataStreamService::class] = new MarketDataStreamService($this->logger, $tickers);

        // получение портфеля и позиций
        $this->services[OperationsService::class] = new OperationsService($this->logger, $account_id);
        // подписка на портфель и позиции
        $this->services[OperationsStreamService::class] = new OperationsStreamService($this->logger, [$account_id]);

        // получение заявок
        $this->services[OrdersService::class] = new OrdersService($this->logger, $account_id);
        // подписка на заявки
        $this->services[OrdersStreamService::class] = new OrdersStreamService($this->logger, [$account_id]);

        $this->logger->info('Main controller ready', [array_map(fn($item) => $item->ticker, $tickers)]);
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
    private function getBody(): string {
        $metricsNames = [
            'portfolio' => getEnv('METRICS_PORTFOLIO') ?? 'portfolio',
            'price' => getEnv('METRICS_PRICE') ?? 'price',
            'order_counts' => getEnv('METRICS_ORDER_COUNTS') ?? 'order_counts',
            'order_totals' => getEnv('METRICS_ORDER_TOTALS') ?? 'order_totals',
            'positions_count' => getEnv('METRICS_POSITIONS_COUNT') ?? 'positions_count',
            'positions_price' => getEnv('METRICS_POSITIONS_PRICE') ?? 'positions_price',
        ];
        $result = '';

        // тикеры
        $tickers = $this->mainStorage->get('tickers');
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
        $portfolio = $this->mainStorage->get('portfolio');
        $params = []; // параметры портфеля
        if (isset($portfolio)) {
            foreach ($portfolio as $key => $data) {
                if (
                    in_array($key, [
                        'totalAmountShares',
                        'totalAmountBonds',
                        'totalAmountEtf',
                        'totalAmountCurrencies',
                        'totalAmountFutures',
                        'expectedYield',
                    ])
                ) {
                    $value = AmountFactory::create($data);
                    $params[] = $metricsNames['portfolio'] . '{label="' . $key . '"} ' . $value->get();
                }
            }
            $result .= $this->getMetric($metricsNames['portfolio'], $params, 'gauge', 'portfolio');

            if (isset($portfolio->positions)) {
                $positions = ['count' => [], 'price' => []]; // позиции
                foreach ($portfolio->positions as $data) {
                    $position = PositionFactory::create($data);
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

        return $result;
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function __invoke(Request $request): Response {
        return new Response(HttpStatus::OK, ['content-type' => 'text/plain; version=0.0.4'], $this->getBody());
    }
}
