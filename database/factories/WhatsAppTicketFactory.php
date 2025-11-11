<?php

namespace Database\Factories;

use App\Models\WhatsAppTicket;
use Illuminate\Database\Eloquent\Factories\Factory;

class WhatsAppTicketFactory extends Factory
{
    protected $model = WhatsAppTicket::class;

    public function definition(): array
    {
        return [
            'ticket_number' => 'WA-' . $this->faker->unique()->numerify('######'),
            'sender_wa_id' => $this->faker->numerify('62###########') . '@c.us',
            'sender_phone' => $this->faker->numerify('62###########'),
            'sender_name' => $this->faker->name(),
            'subject' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'original_message' => $this->faker->paragraph(),
            'category' => $this->faker->randomElement(['network', 'hardware', 'software', 'account', 'email', 'security', 'other']),
            'priority' => $this->faker->randomElement(['normal', 'high', 'urgent']),
            'status' => $this->faker->randomElement(['open', 'in_progress', 'pending', 'resolved', 'closed']),
            'source' => 'whatsapp',
            'is_group' => $this->faker->boolean(20),
            'has_media' => $this->faker->boolean(30),
            'message_type' => 'chat',
            'raw_data' => null,
            'wa_timestamp' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ];
    }

    public function open()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'open',
        ]);
    }

    public function urgent()
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'urgent',
        ]);
    }

    public function resolved()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);
    }

    public function withMedia()
    {
        return $this->state(fn (array $attributes) => [
            'has_media' => true,
            'message_type' => $this->faker->randomElement(['image', 'video', 'document', 'audio']),
        ]);
    }

    public function fromGroup()
    {
        return $this->state(fn (array $attributes) => [
            'is_group' => true,
        ]);
    }
}
