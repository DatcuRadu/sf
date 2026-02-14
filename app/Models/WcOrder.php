<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WcOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'wc_order_id',
        'order_number',
        'status',
        'total',
        'currency',
        'billing',
        'shipping',
        'raw_payload',
        'epicor_status',
    ];

    protected $casts = [
        'billing' => 'array',
        'shipping' => 'array',
        'raw_payload' => 'array',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function items()
    {
        return $this->hasMany(WcOrderItem::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }
}
