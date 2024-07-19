<?php

namespace TBank\App\Controller;

use Amp\ByteStream\BufferException;
use Amp\Http\Client\HttpException;
use Amp\Http\HttpStatus;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;

use Exception;
use TBank\App\Event\SignalEvent;
use TBank\App\Service\InstrumentsService;
use TBank\App\Service\MarketDataStreamService;
use TBank\App\Service\OperationsService;
use TBank\App\Service\OperationsStreamService;
use TBank\App\Service\OrdersService;
use TBank\App\Service\OrdersStreamService;
use TBank\Domain\Entity\OrderEntity;
use TBank\Domain\Factory\AmountFactory;
use TBank\Domain\Factory\PositionFactory;
use TBank\Domain\Factory\SignalFactory;
use TBank\Infrastructure\API\App;
use TBank\Infrastructure\Storage\InstrumentsStorage;
use TBank\Infrastructure\Storage\MainStorage;
use TBank\Infrastructure\Storage\OrdersStorage;

use Monolog\Logger;

use Throwable;
use function TBank\getEnv;

class SignalsController extends AbstractController {
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
    }

    /**
     * @param string $str
     * @return ?string
     */
    private function extractValue(string $str): string|null {
        $pattern = '/value=([\d.]+)/';
        if (preg_match($pattern, $str, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getBody(): string {
        $signals = $this->mainStorage->getSignals();
        $data = json_decode($this->request->getBody()->buffer());
        $this->logger->notice('Signal', [$data]);

        // grafana
        if (isset($data->alerts)) {
            $statues = [
                'firing' => 1,
                'resolved' => 0,
            ];

            foreach ($data->alerts as $alert) {
                if (!($ticker = $alert->labels->ticker)) {
                    throw new Exception('Bad input data', 400);
                }
                foreach ($alert->labels as $key => $label) {
                    if (str_starts_with($key, 'signal')) {
                        if (!($signalName = $alert->labels->{$key})) {
                            throw new Exception('Bad input data', 400);
                        }
                        $signal = SignalFactory::create(
                            (object) [
                                'name' => $signalName,
                                'ticker' => $ticker,
                                'value' => $statues[$alert->status] ?? 0,
                            ]
                        );
                        MainStorage::getInstance()->setSignal($signal);
                    }
                }
            }
        }

        return json_encode(['status' => true]);
    }

    /**
     * @return Response
     * @throws Exception
     */
    public function __invoke(): Response {
        return new Response(HttpStatus::OK, ['content-type' => 'application/json; charset=utf-8'], $this->getBody());
    }
}
