<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WooCommerceSyncLog extends Model
{

    protected $table = 'woocommerce_sync_logs';
    protected $fillable = [
        'sku',
        'woocommerce_product_id',
        'status',
        'old_data',
        'new_data',
        'request_payload',
        'response_payload',
    ];

    protected $casts = [
        'old_data' => 'array',
        'new_data' => 'array',
        'request_payload' => 'array',
        'response_payload' => 'array',
    ];
}
