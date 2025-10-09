<?php

namespace App\Jobs;

use App\Models\Ticket;
use App\Mail\TicketReceived;
use App\Mail\TicketApproved;
use App\Mail\TicketRejected;
use App\Mail\TicketClosed;
use App\Mail\RevisionRequest;
use App\Mail\TicketUpdate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendTicketNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $ticket;
    protected $type;
    protected $data;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public $backoff = 60;

    public function __construct(Ticket $ticket, $type, $data = [])
    {
        $this->ticket = $ticket;
        $this->type = $type;
        $this->data = $data;
    }

    public function handle()
    {
        try {
            switch ($this->type) {
                case 'received':
                    Mail::to($this->ticket->user_email)
                        ->send(new TicketReceived($this->ticket));
                    break;
                
                case 'approved':
                    Mail::to($this->ticket->user_email)
                        ->send(new TicketApproved($this->ticket));
                    break;
                
                case 'rejected':
                    Mail::to($this->ticket->user_email)
                        ->send(new TicketRejected($this->ticket));
                    break;
                
                case 'closed':
                    Mail::to($this->ticket->user_email)
                        ->send(new TicketClosed($this->ticket));
                    break;
                
                case 'revision':
                    Mail::to($this->ticket->user_email)
                        ->send(new RevisionRequest($this->ticket, $this->data['message']));
                    break;
                
                case 'progress':
                    Mail::to($this->ticket->user_email)
                        ->send(new TicketUpdate($this->ticket, $this->data['message']));
                    break;
                
                default:
                    Log::warning("Unknown notification type: {$this->type}");
            }

            Log::info("Notification sent: {$this->type} for ticket {$this->ticket->ticket_number}");
        } catch (\Exception $e) {
            Log::error("Failed to send notification: {$e->getMessage()}");
            throw $e; // Re-throw to trigger retry
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception)
    {
        Log::error("Notification job failed permanently for ticket {$this->ticket->ticket_number}: {$exception->getMessage()}");
    }
}