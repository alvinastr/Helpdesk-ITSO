<?php

namespace App\Mail;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TicketUpdate extends Mailable
{
    use Queueable, SerializesModels;

    public $ticket;
    public $updateMessage;

    public function __construct(Ticket $ticket, $updateMessage)
    {
        $this->ticket = $ticket;
        $this->updateMessage = $updateMessage;
    }

    public function build()
    {
        return $this->subject("Update Ticket - {$this->ticket->ticket_number}")
                    ->markdown('emails.tickets.update')
                    ->with([
                        'ticket' => $this->ticket,
                        'message' => $this->updateMessage
                    ]);
    }
}