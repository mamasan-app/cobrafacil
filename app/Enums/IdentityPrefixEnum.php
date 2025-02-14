<?php

namespace App\Enums;

use Illuminate\Support\Collection;

enum IdentityPrefixEnum: string
{
    case V = 'V';
    case E = 'E';
    case J = 'J';

    public function getLabel(): string
    {
        return match ($this) {
            self::V => 'Venezolano',
            self::E => 'Extranjero',
            self::J => 'Jurídico',
        };
    }

    public static function all(): Collection
    {
        return collect([
            self::V,
            self::E,
            self::J,
        ]);
    }
}
