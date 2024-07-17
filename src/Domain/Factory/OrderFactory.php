<?php

namespace TBank\Domain\Factory;

use TBank\Domain\Entity\OrderEntity;

final class OrderFactory {
    public static function create(object $data): OrderEntity {
        return new OrderEntity(
            orderId: $data->orderId,
            orderRequestId: $data->orderRequestId ?? '',
            instrumentUid: $data->instrumentUid,
            lotsRequested: $data->lotsRequested,
            lotsExecuted: $data->lotsExecuted,
            totalOrderAmount: AmountFactory::create($data->totalOrderAmount ?? ($data->amount ?? (object) [])),
            executionReportStatus: $data->executionReportStatus ?? 'EXECUTION_REPORT_STATUS_UNSPECIFIED',
            direction: $data->direction,
            orderType: $data->orderType ?? 'ORDER_TYPE_LIMIT'
        );
    }
}
