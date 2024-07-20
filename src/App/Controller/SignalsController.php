<?php

namespace TBank\App\Controller;

use TBank\Infrastructure\API\App;
use TBank\Infrastructure\Storage\MainStorage;

use Amp\ByteStream\BufferException;
use Amp\Http\Client\HttpException;
use Amp\Http\HttpStatus;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;

use Monolog\Logger;
use Exception;
use Throwable;

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

                        $this->mainStorage->setSignal($signalName, $ticker, $statues[$alert->status] ?? 0);
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
