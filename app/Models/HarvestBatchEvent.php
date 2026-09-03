<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HarvestBatchEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'harvest_batch_id',
        'title',
        'actor_label',
        'actor_user_id',
        'event_at',
        'metadata',
        'previous_ledger_hash',
        'ledger_hash',
        'ledger_height',
    ];

    protected function casts(): array
    {
        return [
            'event_at' => 'datetime',
            'metadata' => 'array',
            'ledger_height' => 'integer',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(HarvestBatch::class, 'harvest_batch_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
