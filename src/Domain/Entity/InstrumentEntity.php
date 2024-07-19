<?php

namespace TBank\Domain\Entity;

final class InstrumentEntity {
    public function __construct(
        public string $isin,
        public string $figi,
        public string $ticker,
        public string $classCode,
        public string $instrumentType,
        public string $name,
        public string $uid,
        public string $positionUid,
        public string $instrumentKind,
        public bool $apiTradeAvailableFlag,
        public bool $forIisFlag,
        public bool $forQualInvestorFlag,
        public bool $weekendFlag,
        public bool $blockedTcaFlag
    ) {
    }
}
