<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryRowHistory extends Model
{
    protected $table = 'inventory_row_history';

    protected $fillable = [
        'inventory_file_id',
        'product_id',
        'sku',
        'csv_row',
        'action',
        'row_hash',
        'row_json',
        'changes_json'
    ];

    protected $casts = [
        'row_json' => 'array',
         'changes_json' => 'array',
    ];
}