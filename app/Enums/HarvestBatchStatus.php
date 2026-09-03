<?php

namespace App\Enums;

enum HarvestBatchStatus: string
{
    case Draft = 'draft';
    case Created = 'created';
    case VerifiedByCollector = 'verifiedByCollector';
    case InDistribution = 'inDistribution';
    case ReceivedByUmkm = 'receivedByUmkm';
    case Processed = 'processed';
    case Sold = 'sold';
    case Rejected = 'rejected';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $status): string => $status->value, self::cases());
    }
}
