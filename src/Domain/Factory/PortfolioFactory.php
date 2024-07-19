<?php

namespace TBank\Domain\Factory;

use TBank\Domain\Entity\PortfolioEntity;
use function TBank\dbg;

final class PortfolioFactory {
    public static function create(object $data): PortfolioEntity {
        return new PortfolioEntity(
            totalAmountShares: AmountFactory::create($data->totalAmountShares),
            totalAmountBonds: AmountFactory::create($data->totalAmountBonds),
            totalAmountEtf: AmountFactory::create($data->totalAmountEtf),
            totalAmountCurrencies: AmountFactory::create($data->totalAmountCurrencies),
            totalAmountFutures: AmountFactory::create($data->totalAmountFutures),
            expectedYield: AmountFactory::create($data->expectedYield),
            positions: array_map(fn($item) => PositionFactory::create($item), $data->positions),
            accountId: $data->accountId,
            totalAmountOptions: AmountFactory::create($data->totalAmountOptions),
            totalAmountSp: AmountFactory::create($data->totalAmountSp),
            totalAmountPortfolio: AmountFactory::create($data->totalAmountPortfolio)
            // virtualPositions: array_map(fn($item) => PositionFactory::create($item), $data->virtualPositions)
        );
    }
}
