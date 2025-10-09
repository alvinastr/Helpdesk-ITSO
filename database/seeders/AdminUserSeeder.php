<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user if doesn't exist
        $adminEmail = 'admin@itso.com';
        
        if (!User::where('email', $adminEmail)->exists()) {
            User::create([
                'name' => 'Administrator',
                'email' => $adminEmail,
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]);
            
            $this->command->info('✅ Admin user created successfully!');
            $this->command->info('📧 Email: ' . $adminEmail);
            $this->command->info('🔑 Password: admin123');
        } else {
            $this->command->info('ℹ️ Admin user already exists.');
        }
        
        // Create sample user for testing
        $userEmail = 'user@itso.com';
        
        if (!User::where('email', $userEmail)->exists()) {
            User::create([
                'name' => 'Test User',
                'email' => $userEmail,
                'password' => Hash::make('user123'),
                'role' => 'user',
                'email_verified_at' => now(),
            ]);
            
            $this->command->info('✅ Test user created successfully!');
            $this->command->info('📧 Email: ' . $userEmail);
            $this->command->info('🔑 Password: user123');
        } else {
            $this->command->info('ℹ️ Test user already exists.');
        }
    }
}
