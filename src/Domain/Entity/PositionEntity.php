<?php

namespace TBank\Domain\Entity;

final class PositionEntity {
    public function __construct(
        public string $figi,
        public string $instrumentType,
        public AmountEntity $quantity,
        public AmountEntity $averagePositionPrice,
        public AmountEntity $expectedYield,
        public AmountEntity $averagePositionPricePt,
        public AmountEntity $currentPrice,
        public AmountEntity $averagePositionPriceFifo,
        public AmountEntity $quantityLots,
        public bool $blocked,
        public string $positionUid,
        public string $instrumentUid
    ) {
    }
}
