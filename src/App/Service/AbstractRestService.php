<?php

namespace TBank\App\Service;

use Amp\Http\Client\HttpClient;
use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\HttpException;
use Amp\Http\Client\Request;
use Amp\Http\Client\Response;
use Amp\ByteStream\BufferException;
use Amp\ByteStream\StreamException;

use Monolog\Logger;

use Exception;

use function TBank\getEnv;

abstract class AbstractRestService implements ServiceInterface {
    private HttpClient $httpClient;
    private string $token;
    private string $baseURL;

    public function __construct(private readonly Logger $logger) {
        $this->httpClient = (new HttpClientBuilder())->build();
        $this->baseURL = getEnv('API_URL_REST') ?? 'https://invest-public-api.tinkoff.ru';
        $this->token = getEnv('API_TOKEN') ?? '';
    }

    /**
     * @param string $url
     * @param array $params
     * @param string $method
     * @return Response
     * @throws BufferException
     * @throws HttpException
     * @throws StreamException
     * @throws Exception
     */
    protected function httpRequest(string $url, array $params = [], string $method = 'POST'): mixed {
        $request = new Request($this->baseURL . $url, $method);
        $request->setInactivityTimeout(60);
        $request->setTransferTimeout(60);
        $request->setTlsHandshakeTimeout(60);
        $request->setTcpConnectTimeout(60);

        if ($method === 'POST') {
            $request->setBody(json_encode($params));
        } else {
            $request->setQueryParameters($params);
        }

        $request->setHeader('Authorization', 'Bearer ' . $this->token);
        $request->setHeader('Content-type', 'application/json');
        $response = $this->httpClient->request($request);
        $data = $response->getBody()->buffer();
        $responseData = json_decode($data);

        $this->logger->notice($url, [$params]);

        if (is_null($responseData)) {
            return $data;
        }

        return $responseData;
    }
}
