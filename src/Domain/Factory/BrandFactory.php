<?php

namespace TBank\Domain\Factory;

use TBank\Domain\Entity\BrandEntity;
use TBank\Domain\Entity\InstrumentEntity;
use function TBank\dbg;

final class BrandFactory {
    public static function create(object $data): BrandEntity {
        return new BrandEntity(
            logoName: $data->logoName ?? '',
            logoBaseColor: $data->logoBaseColor ?? '',
            textColor: $data->textColor ?? ''
        );
    }
}
