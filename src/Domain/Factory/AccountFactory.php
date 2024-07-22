<?php

namespace TBank\Domain\Factory;

use TBank\Domain\Entity\AccountEntity;

final class AccountFactory {
    public static function create(object $data): AccountEntity {
        return new AccountEntity(
            id: $data->id ?? '',
            type: $data->type ?? '',
            name: $data->name ?? '',
            status: $data->status ?? '',
            openedDate: $data->openedDate ?? '',
            closedDate: $data->closedDate ?? '',
            accessLevel: $data->accessLevel ?? ''
        );
    }
}
