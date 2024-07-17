<?php

namespace TBank\Domain\Entity;

final class OrderEntity {
    public function __construct(
        public string $orderId,
        public string $orderRequestId,
        public string $instrumentUid,
        public string $lotsRequested,
        public string $lotsExecuted,
        public AmountEntity $totalOrderAmount,
        public string $executionReportStatus,
        public string $direction,
        public string $orderType = 'ORDER_TYPE_LIMIT'
    ) {
    }
}
