<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Ticket extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'ticket_number',
        'user_id',
        'user_name',
        'user_email',
        'user_phone',
        'reporter_nip',
        'reporter_name',
        'reporter_email',
        'reporter_phone',
        'reporter_department',
        'reporter_position',
        'channel',
        'input_method',
        'subject',
        'description',
        'original_message',
        'category',
        'priority',
        'status',
        'rejection_reason',
        'resolution_notes',
        'assigned_to',
        'approved_by',
        'created_by_admin',
        'approved_at',
        'closed_at',
        // KPI Fields
        'email_received_at',
        'first_response_at',
        'resolved_at',
        'response_time_minutes',
        'resolution_time_minutes',
        'ticket_creation_delay_minutes',
        // Email Content Fields
        'email_subject',
        'email_body_original',
        'email_response_admin',
        'email_resolution_message',
        'email_thread',
        'email_from',
        'email_to',
        'email_cc',
    ];
    
    protected $casts = [
        'approved_at' => 'datetime',
        'closed_at' => 'datetime',
        'email_received_at' => 'datetime',
        'first_response_at' => 'datetime',
        'resolved_at' => 'datetime',
        'email_thread' => 'array', // JSON cast
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
    public function createdByAdmin()
    {
        return $this->belongsTo(User::class, 'created_by_admin');
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

    // KPI Helper Methods
    public function calculateResponseTime()
    {
        if (!$this->first_response_at) {
            $this->response_time_minutes = null;
            return;
        }
        
        $startTime = $this->email_received_at ?? $this->created_at;
        $this->response_time_minutes = $startTime->diffInMinutes($this->first_response_at);
    }

    public function calculateResolutionTime()
    {
        if (!$this->resolved_at) {
            $this->resolution_time_minutes = null;
            return;
        }
        
        $startTime = $this->email_received_at ?? $this->created_at;
        $this->resolution_time_minutes = $startTime->diffInMinutes($this->resolved_at);
    }

    public function calculateTicketCreationDelay()
    {
        if (!$this->email_received_at || !$this->created_at) {
            $this->ticket_creation_delay_minutes = null;
            return;
        }
        
        $this->ticket_creation_delay_minutes = $this->email_received_at->diffInMinutes($this->created_at);
    }

    public function getResponseTimeFormatted()
    {
        if (!$this->response_time_minutes) return '-';
        
        $hours = floor($this->response_time_minutes / 60);
        $minutes = $this->response_time_minutes % 60;
        $days = floor($hours / 24);
        $hours = $hours % 24;
        
        if ($days > 0) {
            return sprintf('%d hari %d jam %d menit', $days, $hours, $minutes);
        } elseif ($hours > 0) {
            return sprintf('%d jam %d menit', $hours, $minutes);
        } else {
            return sprintf('%d menit', $minutes);
        }
    }

    public function getResolutionTimeFormatted()
    {
        if (!$this->resolution_time_minutes) return '-';
        
        $hours = floor($this->resolution_time_minutes / 60);
        $minutes = $this->resolution_time_minutes % 60;
        $days = floor($hours / 24);
        $hours = $hours % 24;
        
        if ($days > 0) {
            return sprintf('%d hari %d jam %d menit', $days, $hours, $minutes);
        } elseif ($hours > 0) {
            return sprintf('%d jam %d menit', $hours, $minutes);
        } else {
            return sprintf('%d menit', $minutes);
        }
    }

    public function getTicketCreationDelayFormatted()
    {
        if (!$this->ticket_creation_delay_minutes) return '-';
        
        $hours = floor($this->ticket_creation_delay_minutes / 60);
        $minutes = $this->ticket_creation_delay_minutes % 60;
        $days = floor($hours / 24);
        $hours = $hours % 24;
        
        if ($days > 0) {
            return sprintf('%d hari %d jam %d menit', $days, $hours, $minutes);
        } elseif ($hours > 0) {
            return sprintf('%d jam %d menit', $hours, $minutes);
        } else {
            return sprintf('%d menit', $minutes);
        }
    }

    // Check if KPI is within target
    public function isResponseTimeWithinTarget($targetMinutes = 30)
    {
        return $this->response_time_minutes && $this->response_time_minutes <= $targetMinutes;
    }

    public function isResolutionTimeWithinTarget($targetMinutes = 2880) // 2 days
    {
        return $this->resolution_time_minutes && $this->resolution_time_minutes <= $targetMinutes;
    }
}
