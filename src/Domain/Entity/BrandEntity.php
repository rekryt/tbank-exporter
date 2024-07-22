<?php

namespace TBank\Domain\Entity;

final class BrandEntity {
    public function __construct(public string $logoName, public string $logoBaseColor, public string $textColor) {
    }
}
