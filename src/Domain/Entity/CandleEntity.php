<?php

namespace TBank\Domain\Entity;

final class CandleEntity {
    public function __construct(
        public string $figi,
        public string $interval,
        public AmountEntity $open,
        public AmountEntity $high,
        public AmountEntity $low,
        public AmountEntity $close,
        public int $volume,
        public string $time,
        public string $lastTradeTs,
        public string $instrumentUid
    ) {
    }
}
