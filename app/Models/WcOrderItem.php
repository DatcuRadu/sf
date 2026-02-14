<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WcOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'wc_order_id',
        'sku',
        'name',
        'quantity',
        'price',
        'total',
        'raw_item',
    ];

    protected $casts = [
        'raw_item' => 'array',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function order()
    {
        return $this->belongsTo(WcOrder::class);
    }
}
