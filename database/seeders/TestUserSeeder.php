<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class TestUserSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Create admin user
        User::updateOrCreate(
            ['email' => 'admin@helpdesk.com'],
            [
                'name' => 'Admin Helpdesk',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'phone' => '081234567890'
            ]
        );

        // Create regular user
        User::updateOrCreate(
            ['email' => 'user@helpdesk.com'],
            [
                'name' => 'User Test',
                'password' => Hash::make('password123'),
                'role' => 'user',
                'phone' => '081234567891'
            ]
        );

        // Create another user
        User::updateOrCreate(
            ['email' => 'user2@helpdesk.com'],
            [
                'name' => 'User Support',
                'password' => Hash::make('password123'),
                'role' => 'user',
                'phone' => '081234567892'
            ]
        );
    }
}
