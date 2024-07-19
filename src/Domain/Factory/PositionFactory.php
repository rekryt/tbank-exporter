<?php

namespace TBank\Domain\Factory;

use TBank\Domain\Entity\PositionEntity;

final class PositionFactory {
    public static function create(object $data): PositionEntity {
        return new PositionEntity(
            figi: $data->figi,
            instrumentType: $data->instrumentType,
            quantity: AmountFactory::create($data->quantity),
            averagePositionPrice: AmountFactory::create($data->averagePositionPrice),
            expectedYield: AmountFactory::create($data->expectedYield),
            averagePositionPricePt: AmountFactory::create($data->averagePositionPricePt),
            currentPrice: AmountFactory::create($data->currentPrice),
            averagePositionPriceFifo: AmountFactory::create($data->averagePositionPriceFifo),
            quantityLots: AmountFactory::create($data->quantityLots),
            varMargin: AmountFactory::create($data->varMargin),
            expectedYieldFifo: AmountFactory::create($data->expectedYieldFifo),
            blocked: !!$data->blocked,
            positionUid: $data->positionUid,
            instrumentUid: $data->instrumentUid
        );
    }
}
