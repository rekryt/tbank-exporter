<?php

namespace TBank\Infrastructure\API;

use Amp\ByteStream\WritableResourceStream;
use Amp\Log\ConsoleFormatter;
use Amp\Log\StreamHandler;
use Psr\Log\LogLevel;
use TBank\App\Service\ServiceInterface;
use TBank\Infrastructure\API\EventDispatcher\EventDispatcher;

use Revolt\EventLoop;
use Revolt\EventLoop\UnsupportedFeatureException;
use Closure;
use Dotenv\Dotenv;
use Monolog\Logger;

use function Amp\trapSignal;
use function TBank\getEnv;
use function sprintf;

final class App {
    private static App $_instance;

    private EventDispatcher $dispatcher;

    /**
     * @param array<AppModuleInterface> $modules
     */
    private array $modules = [];

    private bool $isEventLoopStarted = false;

    private int $connectionLimit = 1000;

    private int $connectionPerIpLimit = 10;

    private array $services = [];

    /**
     * @param ?Logger $logger
     */
    private function __construct(private ?Logger $logger = null) {
        ini_set('memory_limit', getEnv('SYS_MEMORY_LIMIT') ?? '2048M');

        if (!defined('PATH_ROOT')) {
            define('PATH_ROOT', dirname(__DIR__, 3));
        }

        $dotenv = Dotenv::createImmutable(PATH_ROOT);
        $dotenv->safeLoad();

        if ($timezone = getEnv('SYS_TIMEZONE')) {
            date_default_timezone_set($timezone);
        }

        $this->logger = $logger ?? new Logger(getEnv('COMPOSE_PROJECT_NAME') ?? 'exporter');
        $logHandler = new StreamHandler(new WritableResourceStream(STDOUT));
        $logHandler->setFormatter(new ConsoleFormatter());
        $logHandler->setLevel(getEnv('DEBUG') === 'false' ? LogLevel::INFO : LogLevel::INFO);
        $this->logger->pushHandler($logHandler);

        $this->dispatcher = new EventDispatcher();

        EventLoop::setErrorHandler(function ($e) {
            $this->logger->error($e->getMessage());
        });
    }
    public static function getInstance(?Logger $logger = null): self {
        return self::$_instance ??= new self($logger);
    }

    public function getDispatcher(): EventDispatcher {
        return $this->dispatcher;
    }

    /**
     * @param Closure<AppModuleInterface> $handler
     * @return $this
     */
    public function addHandler(Closure $handler): self {
        $module = $handler($this);
        $this->modules[$module::class] = $module;
        return $this;
    }
    public function getModule($className) {
        return $this->modules[$className];
    }
    public function getModules(): array {
        return $this->modules;
    }
    public static function getLogger(): ?Logger {
        return self::$_instance->logger;
    }

    public function start(): void {
        foreach ($this->getModules() as $module) {
            $module->start();
        }
        if (defined('SIGINT') && defined('SIGTERM')) {
            // Await SIGINT or SIGTERM to be received.
            try {
                $signal = trapSignal([SIGINT, SIGTERM]);
                $this->logger->info(sprintf('Received signal %d, stopping server', $signal));
            } catch (UnsupportedFeatureException $e) {
                $this->logger->error($e->getMessage());
            }
            $this->stop();
        } else {
            if (!$this->isEventLoopStarted) {
                $this->isEventLoopStarted = true;
                EventLoop::run();
            }
        }
    }

    public function stop(): void {
        foreach ($this->modules as $module) {
            $module->stop();
        }
    }

    /**
     * @return int
     */
    public function getConnectionLimit(): int {
        return $this->connectionLimit;
    }

    /**
     * @return int
     */
    public function getConnectionPerIpLimit(): int {
        return $this->connectionPerIpLimit;
    }

    /**
     * @param string $className
     * @return ServiceInterface
     */
    public function getService(string $className): ServiceInterface {
        return $this->services[$className];
    }

    /**
     * @param ServiceInterface $service
     * @return App
     */
    public function addService(ServiceInterface $service): self {
        $this->services[$service::class] = $service;
        return $this;
    }
}
