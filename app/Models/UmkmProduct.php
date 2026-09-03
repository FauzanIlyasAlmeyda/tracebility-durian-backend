<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UmkmProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'umkm_user_id',
        'category',
        'name',
        'price',
        'stock_qty',
        'description',
        'status',
        'photo_path',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    public function umkm(): BelongsTo
    {
        return $this->belongsTo(User::class, 'umkm_user_id');
    }

    public function sources(): HasMany
    {
        return $this->hasMany(UmkmProductSource::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(UmkmOrder::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(ConsumerTransaction::class);
    }
}
