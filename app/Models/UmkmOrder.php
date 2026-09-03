<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UmkmOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'umkm_product_id',
        'buyer_name',
        'buyer_phone',
        'buyer_address',
        'quantity',
        'total_amount',
        'qr_code_data',
        'note',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(UmkmProduct::class, 'umkm_product_id');
    }
}
