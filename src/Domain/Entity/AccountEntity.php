<?php

namespace TBank\Domain\Entity;

final class AccountEntity {
    public function __construct(
        public string $id,
        public string $type,
        public string $name,
        public string $status,
        public string $openedDate,
        public string $closedDate,
        public string $accessLevel
    ) {
    }
}
