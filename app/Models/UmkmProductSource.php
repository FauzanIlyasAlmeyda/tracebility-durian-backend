<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UmkmProductSource extends Model
{
    use HasFactory;

    protected $fillable = [
        'umkm_product_id',
        'harvest_batch_id',
        'source_code_snapshot',
        'weight_kg',
        'fruit_count',
    ];

    protected function casts(): array
    {
        return [
            'weight_kg' => 'decimal:2',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(UmkmProduct::class, 'umkm_product_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(HarvestBatch::class, 'harvest_batch_id');
    }
}
