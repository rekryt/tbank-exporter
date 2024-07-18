<?php

namespace TBank\Infrastructure\API\Handler;

use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\RequestHandler\ClosureRequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Server\Router;

use Psr\Log\LoggerInterface;
use Throwable;

use function TBank\getEnv;

final class HTTPHandler extends Handler implements HTTPHandlerInterface {
    private function __construct(private readonly LoggerInterface $logger, array $headers = null) {
    }

    public static function getInstance(LoggerInterface $logger, array $headers = null): HTTPHandler {
        return new self($logger, $headers);
    }

    /**
     * @param string $defaultControllerName
     * @return RequestHandler
     */
    public function getHandler(string $defaultControllerName = 'metrics'): RequestHandler {
        return new ClosureRequestHandler(function (Request $request) use ($defaultControllerName): Response {
            try {
                // $this->logger->notice('HTTP Request', [$request->getHeaders()]);
                $args = $request->getAttribute(Router::class);
                $response = $this->getController(
                    ucfirst($args['controller'] ?? $defaultControllerName),
                    $request,
                    $this->headers ?? []
                )();
            } catch (Throwable $e) {
                $this->logger->warning('Exception', [
                    'exception' => $e::class,
                    'error' => $e->getMessage(),
                    'code' => $e->getCode(),
                ]);
                $response = new Response(
                    status: $e->getCode(),
                    headers: $this->headers ?? [],
                    body: json_encode(
                        array_merge(
                            ['message' => $e->getMessage(), 'code' => $e->getCode()],
                            getEnv('DEBUG') === 'true'
                                ? ['file' => $e->getFile() . ':' . $e->getLine(), 'trace' => $e->getTrace()]
                                : []
                        )
                    )
                );
            }

            return $response;
        });
    }
}
