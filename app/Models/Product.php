<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'sku',
        'original_id',
        'regular_price',
        'sale_price',
        'sales_start',
        'sales_end',
        'qty',
        'gitn',
        'fields_json',
        'row_hash',
        'to_sync',
        'woo_product_id',
        'woo_parent_id',
        'name',
        'description',
        'deleted'

    ];

    protected $casts = [
        'regular_price' => 'decimal:2',
        'sale_price'    => 'decimal:2',
        'sales_start'   => 'datetime',
        'sales_end'     => 'datetime',
        'fields_json'   => 'array',
    ];

    public function phistories()
    {
        return $this->hasMany(ProductHistory::class)->orderByDesc('changed_at');;
    }

    public function syncLogs()
    {
        return $this->hasMany(WooCommerceSyncLog::class, 'sku', 'sku')
            ->latest();
    }
    public function histories()
    {
        return $this->hasMany(
            \App\Models\InventoryRowHistory::class,
            'product_id'
        )->orderByDesc('id');
    }
}
