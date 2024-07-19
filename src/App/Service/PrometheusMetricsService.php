<?php

namespace TBank\App\Service;

use Amp\Http\Client\Form;
use Amp\Http\Client\HttpClient;
use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\Request;
use Amp\Http\Client\Response;
use Exception;
use Revolt\EventLoop;
use TBank\App\Event\SignalEvent;
use TBank\Domain\Factory\OrderFactory;
use TBank\Domain\Factory\SignalFactory;
use TBank\Infrastructure\API\App;
use TBank\Infrastructure\Storage\MainStorage;

use Amp\Http\Client\HttpException;
use Amp\ByteStream\BufferException;
use Amp\ByteStream\StreamException;

use Monolog\Logger;
use Throwable;

use function TBank\dbg;
use function TBank\getEnv;

final class PrometheusMetricsService extends AbstractRestService {
    private HttpClient $httpClient;
    private string $baseURL;
    private string $login;
    private string $password;
    private array $metrics;
    private Logger $logger;

    public function __construct() {
        $this->logger = App::getLogger()->withName('PrometheusMetricsService');
        parent::__construct($this->logger);

        $this->httpClient = (new HttpClientBuilder())->build();
        $this->baseURL = getEnv('API_PROMETHEUS_URL') ?? 'http://prometheus:9090';
        $this->login = getEnv('API_PROMETHEUS_LOGIN') ?? 'admin';
        $this->password = getEnv('API_PROMETHEUS_PASSWORD') ?? 'admin';

        if (is_file(PATH_ROOT . '/metrics.json')) {
            // массив метрик которые нужно создавать из прометеуса
            $this->metrics = json_decode(file_get_contents(PATH_ROOT . '/metrics.json')) ?: [];
        }

        /**
         * @throws BufferException
         * @throws StreamException
         * @throws HttpException
         */
        $metricsUpdate = function () {
            foreach ($this->metrics as $metric) {
                foreach ($this->query($metric->query) as $item) {
                    $signal = SignalFactory::create(
                        (object) [
                            'name' => $metric->name,
                            'ticker' => $item->metric->ticker,
                            'value' => (float) $item->value[1],
                        ]
                    );
                    MainStorage::getInstance()->setSignal($signal);
                }
            }
        };
        $metricsUpdate();
        // авто-обновление метрик из metrics.json
        EventLoop::repeat(5, $metricsUpdate);
    }

    /**
     * @param array $params
     * @return Response
     * @throws BufferException
     * @throws HttpException
     * @throws StreamException
     */
    protected function request(array $params): mixed {
        $request = new Request($this->baseURL . '/api/v1/query', 'POST');
        $request->setInactivityTimeout(60);
        $request->setTransferTimeout(60);
        $request->setTlsHandshakeTimeout(60);
        $request->setTcpConnectTimeout(60);

        $form = new Form();
        foreach ($params as $key => $value) {
            $form->addField($key, $value);
        }

        $request->setBody($form);

        $request->setHeader('Authorization', 'Basic ' . base64_encode($this->login . ':' . $this->password));
        $response = $this->httpClient->request($request);
        $data = $response->getBody()->buffer();
        $responseData = json_decode($data);

        if (is_null($responseData)) {
            return $data;
        }

        return $responseData;
    }

    /**
     * @param string $query
     * @return array|false
     * @throws BufferException
     * @throws HttpException
     * @throws StreamException
     */
    public function query(string $query): array|false {
        $response = $this->request(['query' => $query]);

        if ($response && ($response->success = 'success')) {
            return $response->data->result ?? false;
        }

        return false;
    }
}
