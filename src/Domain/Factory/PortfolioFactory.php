<?php

namespace TBank\Domain\Factory;

use TBank\Domain\Entity\PortfolioEntity;
use function TBank\dbg;

final class PortfolioFactory {
    public static function create(object $data): PortfolioEntity {
        return new PortfolioEntity(
            totalAmountShares: AmountFactory::create($data->totalAmountShares ?? (object) []),
            totalAmountBonds: AmountFactory::create($data->totalAmountBonds ?? (object) []),
            totalAmountEtf: AmountFactory::create($data->totalAmountEtf ?? (object) []),
            totalAmountCurrencies: AmountFactory::create($data->totalAmountCurrencies ?? (object) []),
            totalAmountFutures: AmountFactory::create($data->totalAmountFutures ?? (object) []),
            expectedYield: AmountFactory::create($data->expectedYield ?? (object) []),
            positions: array_map(fn($item) => PositionFactory::create($item), $data->positions ?? []),
            accountId: $data->accountId ?? '',
            totalAmountOptions: AmountFactory::create($data->totalAmountOptions ?? (object) []),
            totalAmountSp: AmountFactory::create($data->totalAmountSp ?? (object) []),
            totalAmountPortfolio: AmountFactory::create($data->totalAmountPortfolio ?? (object) [])
            // virtualPositions: array_map(fn($item) => PositionFactory::create($item), $data->virtualPositions)
        );
    }
}
