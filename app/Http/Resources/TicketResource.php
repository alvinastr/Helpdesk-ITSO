<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ticket_number' => $this->ticket_number,
            'user_name' => $this->user_name,
            'user_email' => $this->user_email,
            'user_phone' => $this->user_phone,
            'channel' => $this->channel,
            'subject' => $this->subject,
            'description' => $this->description,
            'category' => $this->category,
            'priority' => $this->priority,
            'status' => $this->status,
            'rejection_reason' => $this->rejection_reason,
            'resolution_notes' => $this->resolution_notes,
            'rating' => $this->rating,
            'feedback' => $this->feedback,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'approved_at' => $this->approved_at,
            'closed_at' => $this->closed_at,
            
            // Relationships
            'user' => $this->whenLoaded('user'),
            'assigned_user' => $this->whenLoaded('assignedUser'),
            'approved_by_user' => $this->whenLoaded('approvedBy'),
            'threads' => $this->whenLoaded('threads'),
            'status_histories' => $this->whenLoaded('statusHistories'),
        ];
    }
}
