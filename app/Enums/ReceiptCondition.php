<?php

namespace App\Enums;

enum ReceiptCondition: string
{
    case Good = 'good';
    case MinorDamage = 'minorDamage';
    case Damaged = 'damaged';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $condition): string => $condition->value, self::cases());
    }
}
