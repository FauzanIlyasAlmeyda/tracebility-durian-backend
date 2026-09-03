<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollectorShipmentSource extends Model
{
    use HasFactory;

    protected $fillable = [
        'collector_shipment_id',
        'harvest_batch_id',
        'source_code_snapshot',
        'source_grade_snapshot',
        'source_weight_kg',
        'source_fruit_count',
        'source_variety_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'source_weight_kg' => 'decimal:2',
        ];
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(CollectorShipment::class, 'collector_shipment_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(HarvestBatch::class, 'harvest_batch_id');
    }
}
