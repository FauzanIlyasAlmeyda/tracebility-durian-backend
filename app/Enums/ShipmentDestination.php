<?php

namespace App\Enums;

enum ShipmentDestination: string
{
    case Umkm = 'umkm';
    case Distributor = 'distributor';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $status): string => $status->value, self::cases());
    }
}
