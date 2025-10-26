<?php

use App\Models\Ticket;
use App\Models\User;

// Create sample tickets
Ticket::factory(5)->create([
    'status' => 'pending_review',
    'user_id' => User::where('role', 'user')->first()->id ?? null,
]);

Ticket::factory(3)->create([
    'status' => 'open',
    'user_id' => User::where('role', 'user')->first()->id ?? null,
]);

Ticket::factory(2)->create([
    'status' => 'resolved',
    'user_id' => User::where('role', 'user')->first()->id ?? null,
]);

echo "Sample tickets created successfully!\n";