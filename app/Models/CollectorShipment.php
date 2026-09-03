<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CollectorShipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'collector_user_id',
        'destination_type',
        'total_weight_kg',
        'total_fruit_count',
        'packaged_at',
        'warehouse_note',
        'sent_at',
        'completed_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'total_weight_kg' => 'decimal:2',
            'packaged_at' => 'datetime',
            'sent_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function collector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collector_user_id');
    }

    public function sources(): HasMany
    {
        return $this->hasMany(CollectorShipmentSource::class);
    }
}
