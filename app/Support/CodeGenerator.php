<?php

namespace App\Support;

use Illuminate\Support\Str;

class CodeGenerator
{
    public static function batch(?int $id = null): string
    {
        return $id === null
            ? 'DRN'.now()->format('Ymd').Str::upper(Str::random(4))
            : sprintf('DRN-%s-%06d', now()->format('Y'), $id);
    }

    public static function shipment(?int $id = null): string
    {
        return $id === null
            ? 'PGL'.now()->format('Ymd').Str::upper(Str::random(4))
            : sprintf('PGL-%s-%06d', now()->format('Y'), $id);
    }

    public static function receipt(?int $id = null): string
    {
        return $id === null
            ? 'RCP'.now()->format('Ymd').Str::upper(Str::random(4))
            : sprintf('RCP-%s-%06d', now()->format('Y'), $id);
    }

    public static function product(?int $id = null): string
    {
        return $id === null
            ? 'UMKM'.now()->format('Ymd').Str::upper(Str::random(4))
            : sprintf('UMKM-P-%03d', $id);
    }

    public static function order(?int $id = null): string
    {
        return $id === null
            ? 'TRX'.now()->format('Ymd').Str::upper(Str::random(4))
            : sprintf('ORD-%s-%04d', now()->format('Y'), $id);
    }

    public static function transaction(?int $id = null): string
    {
        return $id === null
            ? 'TRX'.now()->format('Ymd').Str::upper(Str::random(4))
            : sprintf('TRX-%s-%04d', now()->format('Y'), $id);
    }
}
