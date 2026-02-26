<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryFile extends Model
{
    protected $fillable = [
        'file_name',
        'type',
        'total_rows',
        'processed_rows',
        'status',
        'started_at',
        'finished_at',
        'error_message',
    ];
}