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
            'validation_status' => 'pending',
            'input_method' => 'manual',
        ];
    }

    /**
     * Indicate ticket is approved
     */
    public function approved()
    {
        return $this->state(fn (array $attributes) => [
            'validation_status' => 'approved',
            'status' => 'open',
            'approved_at' => now(),
            'approved_by' => User::factory(),
        ]);
    }

    /**
     * Indicate ticket has KPI data
     */
    public function withKpiData()
    {
        $createdAt = now()->subHours(rand(24, 72));
        $firstResponseMinutes = rand(30, 180);
        $resolutionMinutes = rand(120, 1440);

        return $this->state(fn (array $attributes) => [
            'created_at' => $createdAt,
            'first_response_at' => $createdAt->copy()->addMinutes($firstResponseMinutes),
            'response_time_minutes' => $firstResponseMinutes,
            'resolution_time_minutes' => $resolutionMinutes,
            'sla_breached' => false,
            'sla_deadline' => $createdAt->copy()->addHours(4),
        ]);
    }

    /**
     * Indicate ticket was fetched from email
     */
    public function emailFetched()
    {
        return $this->state(fn (array $attributes) => [
            'input_method' => 'email_auto_fetch',
            'channel' => 'email',
            'email_message_id' => 'msg-' . Str::random(16),
            'sender_email' => $this->faker->email,
            'email_received_at' => now()->subHours(rand(1, 24)),
            'processing_time_ms' => rand(500, 3000),
            'validation_status' => 'approved',
        ]);
    }

    /**
     * Indicate ticket is from WhatsApp
     */
    public function whatsapp()
    {
        return $this->state(fn (array $attributes) => [
            'input_method' => 'whatsapp',
            'channel' => 'whatsapp',
        ]);
    }

    /**
     * Indicate ticket is closed with satisfaction rating
     */
    public function closedWithRating()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'closed',
            'closed_at' => now(),
            'satisfaction_rating' => rand(3, 5),
            'satisfaction_comment' => $this->faker->sentence(),
            'resolution_notes' => $this->faker->paragraph(),
        ]);
    }
}
