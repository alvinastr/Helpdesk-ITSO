<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WhatsAppTicket extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'whatsapp_tickets';

    protected $fillable = [
        'ticket_number',
        'sender_wa_id',
        'sender_phone',
        'sender_name',
        'subject',
        'description',
        'original_message',
        'category',
        'priority',
        'status',
        'assigned_to',
        'source',
        'is_group',
        'has_media',
        'message_type',
        'raw_data',
        'wa_timestamp',
        'resolved_at',
        'closed_at',
        // KPI fields
        'actual_report_time',
        'first_response_at',
        'first_assigned_at',
        'work_started_at',
        'time_tracking',
        'total_handle_time',
        'total_wait_time',
        'sla_breach_at',
        'sla_breached',
    ];

    protected $casts = [
        'raw_data' => 'array',
        'is_group' => 'boolean',
        'has_media' => 'boolean',
        'wa_timestamp' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        // KPI casts
        'actual_report_time' => 'datetime',
        'first_response_at' => 'datetime',
        'first_assigned_at' => 'datetime',
        'work_started_at' => 'datetime',
        'time_tracking' => 'array',
        'sla_breach_at' => 'datetime',
        'sla_breached' => 'boolean',
    ];

    // Relationships
    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function responses()
    {
        return $this->hasMany(WhatsAppTicketResponse::class, 'ticket_id');
    }

    // Scopes
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeUrgent($query)
    {
        return $query->where('priority', 'urgent');
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeUnassigned($query)
    {
        return $query->whereNull('assigned_to');
    }

    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    // Accessors
    public function getPriorityColorAttribute()
    {
        return match($this->priority) {
            'urgent' => 'red',
            'high' => 'orange',
            'normal' => 'green',
            default => 'gray'
        };
    }

    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'open' => 'blue',
            'in_progress' => 'yellow',
            'pending' => 'orange',
            'resolved' => 'green',
            'closed' => 'gray',
            default => 'gray'
        };
    }

    // Methods
    public function assignTo($userId)
    {
        $this->update([
            'assigned_to' => $userId,
            'status' => 'in_progress'
        ]);
    }

    public function resolve($note = null)
    {
        $this->update([
            'status' => 'resolved',
            'resolved_at' => now()
        ]);

        if ($note) {
            $this->addResponse($note, 'status_change');
        }
    }

    public function close($note = null)
    {
        $this->update([
            'status' => 'closed',
            'closed_at' => now()
        ]);

        if ($note) {
            $this->addResponse($note, 'status_change');
        }
    }

    public function addResponse($message, $type = 'internal_note', $userId = null)
    {
        return $this->responses()->create([
            'user_id' => $userId ?? auth()->id(),
            'message' => $message,
            'type' => $type,
        ]);
    }

    // Generate ticket number
    public static function generateTicketNumber()
    {
        $prefix = 'WA';
        $date = now()->format('Ymd');

        $lastTicket = self::whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastTicket 
            ? intval(substr($lastTicket->ticket_number, -4)) + 1 
            : 1;

        return sprintf('%s-%s-%04d', $prefix, $date, $sequence);
    }

    // ==================== KPI CALCULATION METHODS ====================

    /**
     * Get First Response Time (FRT) in minutes
     * Time from ticket creation to first admin response
     */
    public function getFirstResponseTimeAttribute()
    {
        if (!$this->first_response_at) {
            return null;
        }

        $start = $this->actual_report_time ?? $this->created_at;
        return $start->diffInMinutes($this->first_response_at);
    }

    /**
     * Get Resolution Time (RT) in minutes
     * Time from ticket creation to resolved
     */
    public function getResolutionTimeAttribute()
    {
        if (!$this->resolved_at) {
            return null;
        }

        $start = $this->actual_report_time ?? $this->created_at;
        return $start->diffInMinutes($this->resolved_at);
    }

    /**
     * Get Handle Time (HT) in minutes
     * Total active handling time (excluding pending)
     */
    public function getHandleTimeAttribute()
    {
        return $this->total_handle_time;
    }

    /**
     * Get Customer Wait Time (CWT) in minutes
     * Total time customer waited for responses
     */
    public function getWaitTimeAttribute()
    {
        return $this->total_wait_time;
    }

    /**
     * Get SLA target in minutes based on priority
     */
    public function getSlaTargetAttribute()
    {
        return match($this->priority) {
            'urgent' => 30,      // 30 minutes
            'high' => 120,       // 2 hours
            'normal' => 480,     // 8 hours
            'low' => 1440,       // 24 hours
            default => 480
        };
    }

    /**
     * Check if SLA is breached
     */
    public function getIsSlaBreachedAttribute()
    {
        if ($this->resolved_at) {
            // Already resolved, check if it was within SLA
            $resolutionTime = $this->resolution_time;
            return $resolutionTime > $this->sla_target;
        }

        // Still open, check if current time exceeds SLA
        $start = $this->actual_report_time ?? $this->created_at;
        $elapsed = $start->diffInMinutes(now());
        return $elapsed > $this->sla_target;
    }

    /**
     * Get formatted First Response Time
     */
    public function getFormattedFrtAttribute()
    {
        $minutes = $this->first_response_time;
        if ($minutes === null) return '-';

        if ($minutes < 60) {
            return "{$minutes}m";
        } elseif ($minutes < 1440) {
            $hours = round($minutes / 60, 1);
            return "{$hours}h";
        } else {
            $days = round($minutes / 1440, 1);
            return "{$days}d";
        }
    }

    /**
     * Get formatted Resolution Time
     */
    public function getFormattedRtAttribute()
    {
        $minutes = $this->resolution_time;
        if ($minutes === null) return '-';

        if ($minutes < 60) {
            return "{$minutes}m";
        } elseif ($minutes < 1440) {
            $hours = round($minutes / 60, 1);
            return "{$hours}h";
        } else {
            $days = round($minutes / 1440, 1);
            return "{$days}d";
        }
    }

    /**
     * Update first response timestamp when admin replies
     */
    public function recordFirstResponse()
    {
        if (!$this->first_response_at) {
            $this->update(['first_response_at' => now()]);
        }
    }

    /**
     * Update work started timestamp
     */
    public function recordWorkStarted()
    {
        if (!$this->work_started_at && $this->status === 'in_progress') {
            $this->update(['work_started_at' => now()]);
        }
    }

    /**
     * Update first assigned timestamp
     */
    public function recordFirstAssignment()
    {
        if (!$this->first_assigned_at && $this->assigned_to) {
            $this->update(['first_assigned_at' => now()]);
        }
    }

    /**
     * Check and update SLA breach status
     */
    public function checkSlaStatus()
    {
        if ($this->is_sla_breached && !$this->sla_breached) {
            $this->update([
                'sla_breached' => true,
                'sla_breach_at' => now()
            ]);
        }
    }

    /**
     * Calculate total handle time (active work time)
     * This should be called periodically or on status change
     */
    public function calculateHandleTime()
    {
        $tracking = $this->time_tracking ?? [];
        
        $activeStatuses = ['open', 'in_progress'];
        $totalMinutes = 0;

        foreach ($activeStatuses as $status) {
            $totalMinutes += $tracking[$status] ?? 0;
        }

        $this->update(['total_handle_time' => $totalMinutes]);
    }
}
