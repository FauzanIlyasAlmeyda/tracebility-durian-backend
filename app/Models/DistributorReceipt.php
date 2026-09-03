<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DistributorReceipt extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'distributor_user_id',
        'collector_shipment_id',
        'expected_weight_kg',
        'expected_fruit_count',
        'received_weight_kg',
        'received_fruit_count',
        'condition',
        'received_at',
        'discrepancy_note',
        'quality_note',
    ];

    protected function casts(): array
    {
        return [
            'expected_weight_kg' => 'decimal:2',
            'received_weight_kg' => 'decimal:2',
            'received_at' => 'datetime',
        ];
    }

    public function distributor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'distributor_user_id');
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(CollectorShipment::class, 'collector_shipment_id');
    }
}
