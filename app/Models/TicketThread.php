<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketThread extends Model
{
    protected $fillable = [
        'ticket_id',
        'sender_type',
        'sender_id',
        'sender_name',
        'message_type',
        'message',
        'attachments'
    ];

    protected $casts = [
        'attachments' => 'array',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
