<?php

namespace App\Mail;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RevisionRequest extends Mailable
{
    use Queueable, SerializesModels;

    public $ticket;
    public $message;

    public function __construct(Ticket $ticket, $message)
    {
        $this->ticket = $ticket;
        $this->message = $message;
    }

    public function build()
    {
        return $this->subject("Revisi Diperlukan - {$this->ticket->ticket_number}")
                    ->markdown('emails.tickets.revision')
                    ->with([
                        'ticket' => $this->ticket,
                        'message' => $this->message
                    ]);
    }
}