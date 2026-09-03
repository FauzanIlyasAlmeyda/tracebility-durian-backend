<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsumerTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'consumer_user_id',
        'umkm_product_id',
        'product_name_snapshot',
        'quantity',
        'total_amount',
        'buyer_coordinates',
        'payment_status',
        'status',
        'qr_code_data',
        'note',
        'paid_at',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    public function consumer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'consumer_user_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(UmkmProduct::class, 'umkm_product_id');
    }
}
