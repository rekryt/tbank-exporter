<?php

namespace TBank\Domain\Factory;

use TBank\Domain\Entity\InstrumentEntity;
use function TBank\dbg;

final class InstrumentFactory {
    public static function create(object $data): InstrumentEntity {
        return new InstrumentEntity(
            figi: $data->figi ?? '',
            ticker: $data->ticker ?? '',
            classCode: $data->classCode ?? '',
            isin: $data->isin ?? '',
            lot: $data->lot ?? '',
            currency: $data->currency ?? '',
            shortEnabledFlag: $data->shortEnabledFlag ?? false,
            name: $data->name ?? '',
            exchange: $data->exchange ?? '',
            countryOfRisk: $data->countryOfRisk ?? '',
            countryOfRiskName: $data->countryOfRiskName ?? '',
            instrumentType: $data->instrumentType ?? '',
            tradingStatus: $data->tradingStatus ?? '',
            otcFlag: $data->otcFlag ?? false,
            buyAvailableFlag: $data->buyAvailableFlag ?? '',
            sellAvailableFlag: $data->sellAvailableFlag ?? '',
            minPriceIncrement: AmountFactory::create($data->minPriceIncrement ?? (object) []),
            apiTradeAvailableFlag: $data->apiTradeAvailableFlag ?? false,
            uid: $data->uid ?? '',
            realExchange: $data->realExchange ?? '',
            positionUid: $data->positionUid ?? '',
            assetUid: $data->assetUid ?? '',
            forIisFlag: $data->forIisFlag ?? false,
            forQualInvestorFlag: $data->forQualInvestorFlag ?? false,
            weekendFlag: $data->weekendFlag ?? false,
            blockedTcaFlag: $data->blockedTcaFlag ?? false,
            instrumentKind: $data->instrumentKind ?? '',
            first1minCandleDate: $data->first1minCandleDate ?? '',
            first1dayCandleDate: $data->first1dayCandleDate ?? '',
            brand: BrandFactory::create($data->brand ?? (object) [])
        );
    }
}
