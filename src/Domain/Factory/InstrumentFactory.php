<?php

namespace TBank\Domain\Factory;

use TBank\Domain\Entity\InstrumentEntity;
use function TBank\dbg;

final class InstrumentFactory {
    public static function create(object $data): InstrumentEntity {
        return new InstrumentEntity(
            isin: $data->isin,
            figi: $data->figi,
            ticker: $data->ticker,
            classCode: $data->classCode,
            instrumentType: $data->instrumentType,
            name: $data->name,
            uid: $data->uid,
            positionUid: $data->positionUid,
            instrumentKind: $data->instrumentKind,
            apiTradeAvailableFlag: $data->apiTradeAvailableFlag,
            forIisFlag: $data->forIisFlag,
            forQualInvestorFlag: $data->forQualInvestorFlag,
            weekendFlag: $data->weekendFlag,
            blockedTcaFlag: $data->blockedTcaFlag
        );
    }
}
