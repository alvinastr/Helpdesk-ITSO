<?php

namespace Database\Factories;

use App\Models\WhatsAppTicketResponse;
use App\Models\WhatsAppTicket;
use Illuminate\Database\Eloquent\Factories\Factory;

class WhatsAppTicketResponseFactory extends Factory
{
    protected $model = WhatsAppTicketResponse::class;

    public function definition(): array
    {
        return [
            'ticket_id' => WhatsAppTicket::factory(),
            'user_id' => null,
            'message' => $this->faker->paragraph(),
            'type' => $this->faker->randomElement(['internal_note', 'reply', 'status_change']),
            'metadata' => null,
        ];
    }
}
