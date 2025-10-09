<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'ticket_number',
        'user_id',
        'user_name',
        'user_email',
        'user_phone',
        'channel',
        'subject',
        'description',
        'category',
        'priority',
        'status',
        'rejection_reason',
        'resolution_notes',
        'assigned_to',
        'approved_by',
        'approved_at',
        'closed_at',
        'rating',
        'feedback'
    ];
    
    protected $casts = [
        'approved_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
    public function threads()
    {
        return $this->hasMany(TicketThread::class);
    }
    public function statusHistories()
    {
        return $this->hasMany(TicketStatusHistory::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending_review');
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['open', 'in_progress']);
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    // Helper Methods
    public function isReply()
    {
        return $this->threads()->count() > 1;
    }

    public function canBeReopened()
    {
        return $this->status === 'closed' && 
               $this->closed_at->diffInDays(now()) <= 7;
    }
}
