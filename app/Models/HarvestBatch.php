<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HarvestBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'farmer_user_id',
        'farm_id',
        'farm_name_snapshot',
        'variety',
        'grade',
        'quantity_kg',
        'unit',
        'fruit_count',
        'harvest_date',
        'status',
        'fertilizer',
        'harvest_method',
        'maturity_level',
        'shelf_life_estimate',
        'storage_suggestion',
        'notes',
        'photo_path',
        'received_quantity_kg',
        'received_fruit_count',
        'verified_grade',
        'quality_notes',
        'verified_by_user_id',
        'verified_at',
        'rejection_reason',
        'rejected_by_user_id',
        'rejected_at',
        'blockchain_network',
        'blockchain_status',
        'blockchain_anchor_ref',
        'blockchain_tx_hash',
        'blockchain_block_number',
        'blockchain_payload',
        'blockchain_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity_kg' => 'decimal:2',
            'received_quantity_kg' => 'decimal:2',
            'blockchain_block_number' => 'integer',
            'blockchain_payload' => 'array',
            'harvest_date' => 'date',
            'verified_at' => 'datetime',
            'rejected_at' => 'datetime',
            'blockchain_synced_at' => 'datetime',
        ];
    }

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'farmer_user_id');
    }

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(HarvestBatchEvent::class);
    }

    public function gradeBreakdowns(): HasMany
    {
        return $this->hasMany(HarvestBatchGradeBreakdown::class);
    }
}
