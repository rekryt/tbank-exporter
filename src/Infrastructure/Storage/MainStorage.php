<?php

namespace TBank\Infrastructure\Storage;

use Revolt\EventLoop;
use TBank\Domain\Entity\AccountEntity;
use TBank\Domain\Entity\InstrumentEntity;
use TBank\Domain\Entity\PortfolioEntity;
use TBank\Domain\Entity\SignalEntity;
use TBank\Domain\Factory\AccountFactory;
use TBank\Domain\Factory\InstrumentFactory;
use TBank\Domain\Factory\PortfolioFactory;
use TBank\Domain\Factory\SignalFactory;
use TBank\Infrastructure\API\Server;
use function TBank\dbg;

final class MainStorage {
    private static MainStorage $_instance;

    private ?AccountEntity $account;
    private ?PortfolioEntity $portfolio;
    /**
     * @var array<InstrumentEntity>
     */
    private array $tickers;

    /**
     * @var array<SignalEntity>
     */
    private array $signals;

    private string $filename = 'storage-main.json';

    private function __construct() {
        $file = PATH_ROOT . '/storage/' . $this->filename;
        if (is_file($file)) {
            $data = (array) json_decode(file_get_contents($file)) ?? [
                'account' => null,
                'portfolio' => null,
                'tickers' => [],
                'signals' => [],
            ];
            $this->account = AccountFactory::create($data['account']);
            $this->portfolio = PortfolioFactory::create($data['portfolio']);
            $this->tickers = array_map(fn($item) => InstrumentFactory::create($item), (array) $data['tickers']);
            $this->signals = array_map(fn($item) => SignalFactory::create($item), (array) $data['signals']);
        }

        EventLoop::repeat(60, fn() => $this->save());
    }

    public static function getInstance(): MainStorage {
        return self::$_instance ??= new self();
    }

    /**
     * @return ?AccountEntity
     */
    public function getAccount(): ?AccountEntity {
        return $this->account;
    }

    /**
     * @param AccountEntity $account
     * @return MainStorage
     */
    public function setAccount(AccountEntity $account): self {
        $this->account = $account;
        return $this;
    }

    /**
     * @return ?PortfolioEntity
     */
    public function getPortfolio(): ?PortfolioEntity {
        return $this->portfolio;
    }

    /**
     * @param ?PortfolioEntity $portfolio
     */
    public function setPortfolio(?PortfolioEntity $portfolio): void {
        $this->portfolio = $portfolio;
    }

    /**
     * @return array
     */
    public function getTickers(): array {
        return $this->tickers;
    }

    /**
     * @param array $tickers
     */
    public function setTickers(array $tickers): void {
        $this->tickers = $tickers;
    }

    /**
     * @return array
     */
    public function getSignals(): array {
        return $this->signals;
    }

    /**
     * @param array $signals
     */
    public function setSignals(array $signals): void {
        $this->signals = $signals;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool {
        return isset($this->data[$key]);
    }

    private function save(): void {
        $logger = Server::getLogger()->withName('MainStorage');
        $logger->notice('Background saving');
        file_put_contents(
            PATH_ROOT . '/storage/' . $this->filename,
            json_encode([
                'account' => $this->account,
                'portfolio' => $this->portfolio,
                'tickers' => $this->tickers,
                'signals' => $this->signals,
            ])
        );
    }
}
