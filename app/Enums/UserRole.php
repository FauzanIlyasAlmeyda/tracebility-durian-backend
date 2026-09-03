<?php

namespace App\Enums;

enum UserRole: string
{
    case Petani = 'petani';
    case Pengepul = 'pengepul';
    case Distributor = 'distributor';
    case Umkm = 'umkm';
    case Konsumen = 'konsumen';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $role): string => $role->value,
            self::cases()
        );
    }
}
