<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HarvestBatchGradeBreakdown extends Model
{
    use HasFactory;

    protected $fillable = [
        'harvest_batch_id',
        'grade_label',
        'weight_kg',
        'fruit_count',
    ];

    protected function casts(): array
    {
        return [
            'weight_kg' => 'decimal:2',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(HarvestBatch::class, 'harvest_batch_id');
    }
}
