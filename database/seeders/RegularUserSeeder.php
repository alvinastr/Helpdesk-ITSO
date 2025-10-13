<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RegularUserSeeder extends Seeder
{
    public function run()
    {
        // Create regular user for testing
        User::create([
            'name' => 'User Test',
            'email' => 'user@test.com',
            'email_verified_at' => now(),
            'password' => Hash::make('usertest123'),
            'role' => 'user',
        ]);

        // Create additional test users
        User::create([
            'name' => 'Customer Support',
            'email' => 'customer@test.com',
            'email_verified_at' => now(),
            'password' => Hash::make('customer123'),
            'role' => 'user',
        ]);
    }
}