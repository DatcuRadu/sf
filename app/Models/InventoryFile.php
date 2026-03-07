<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryFile extends Model
{
    protected $fillable = [
        'file_name',
        'archive_path',
        'received_at',
        'type',
        'batch_id',
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