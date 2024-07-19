<?php

namespace TBank\Domain\Entity;

final class PortfolioEntity {
    /**
     * @param AmountEntity $totalAmountShares
     * @param AmountEntity $totalAmountBonds
     * @param AmountEntity $totalAmountEtf
     * @param AmountEntity $totalAmountCurrencies
     * @param AmountEntity $totalAmountFutures
     * @param AmountEntity $expectedYield
     * @param array<PositionEntity> $positions
     * @param string $accountId
     * @param AmountEntity $totalAmountOptions
     * @param AmountEntity $totalAmountSp
     * @param AmountEntity $totalAmountPortfolio
     * @param array<PositionEntity> $virtualPositions
     */
    public function __construct(
        public AmountEntity $totalAmountShares,
        public AmountEntity $totalAmountBonds,
        public AmountEntity $totalAmountEtf,
        public AmountEntity $totalAmountCurrencies,
        public AmountEntity $totalAmountFutures,
        public AmountEntity $expectedYield,
        public array $positions,
        public string $accountId,
        public AmountEntity $totalAmountOptions,
        public AmountEntity $totalAmountSp,
        public AmountEntity $totalAmountPortfolio,
        public ?array $virtualPositions = []
    ) {
    }
}
