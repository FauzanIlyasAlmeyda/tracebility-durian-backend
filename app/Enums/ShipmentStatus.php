<?php

namespace App\Enums;

enum ShipmentStatus: string
{
    case ReadyToShip = 'readyToShip';
    case Sent = 'sent';
    case Completed = 'completed';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $status): string => $status->value, self::cases());
    }
}
