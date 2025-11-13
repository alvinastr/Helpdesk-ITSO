<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmailFetchLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'fetch_started_at',
        'fetch_completed_at',
        'total_fetched',
        'successful',
        'failed',
        'duplicates',
        'error_message',
        'status'
    ];

    protected $casts = [
        'fetch_started_at' => 'datetime',
        'fetch_completed_at' => 'datetime',
    ];

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeRecent($query)
    {
        return $query->latest('fetch_started_at');
    }
}
