<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryFile extends Model
{
    protected $fillable = [
        'file_name',
        'received_at',
        'type',
        'total_rows',
        'processed_rows',
        'status',
        'started_at',
        'finished_at',
        'error_message',

    ];


    protected $casts = [
        'received_at' => 'datetime',
        'started_at'  => 'datetime',
        'finished_at' => 'datetime',
    ];
}