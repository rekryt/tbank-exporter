<?php

namespace TBank\App\Controller;

use Amp\Http\HttpStatus;
use Amp\Http\Server\Response;
use Exception;
use TBank\Infrastructure\Storage\MainStorage;

class TradingController extends AbstractController {
    /**
     * @return Response
     * @throws Exception
     */
    public function __invoke(): Response {
        return new Response(HttpStatus::OK, ['content-type' => 'application/json; charset=utf-8'], $this->getBody());
    }
    public function getBody(): string {
        return json_encode(MainStorage::getInstance()->getData(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
