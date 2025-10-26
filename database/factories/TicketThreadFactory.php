<?php

namespace Database\Factories;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TicketThread>
 */
class TicketThreadFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ticket_id' => Ticket::factory(),
            'sender_id' => User::factory(),
            'sender_name' => $this->faker->name(),
            'message' => $this->faker->paragraph(),
            'sender_type' => $this->faker->randomElement(['user', 'admin']),
            'message_type' => $this->faker->randomElement(['complaint', 'reply', 'note']),
            'created_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }

    /**
     * Indicate that the thread is from an admin.
     */
    public function admin()
    {
        return $this->state(function (array $attributes) {
            return [
                'sender_type' => 'admin',
                'user_id' => User::factory()->state(['role' => 'admin']),
            ];
        });
    }

    /**
     * Indicate that the thread is internal.
     */
    public function internal()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_internal' => true,
            ];
        });
    }
}
