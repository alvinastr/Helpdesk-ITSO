<?php

namespace Database\Factories;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        return [
            'ticket_number' => 'TKT-' . now()->format('Ymd') . '-' . rand(1000,9999),
            'user_id' => User::factory(),
            'user_name' => $this->faker->name(),
            'user_email' => $this->faker->unique()->safeEmail(),
            'user_phone' => $this->faker->phoneNumber(),
            'channel' => 'portal',
            'subject' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(2),
            'category' => 'Technical',
            'priority' => 'medium',
            'status' => 'pending_keluhan',
        ];
    }
}
