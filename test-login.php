<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\Hash;

// Load Laravel
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Test user credentials
$user = \App\Models\User::where('email', 'admin@itso.com')->first();

if ($user) {
    echo "User found:\n";
    echo "ID: " . $user->id . "\n";
    echo "Name: " . $user->name . "\n";
    echo "Email: " . $user->email . "\n";
    echo "Role: " . $user->role . "\n";
    echo "Email verified: " . ($user->email_verified_at ? 'Yes' : 'No') . "\n";
    
    $passwordCheck = Hash::check('admin123', $user->password);
    echo "Password check for 'admin123': " . ($passwordCheck ? 'PASS' : 'FAIL') . "\n";
    
    // Try authentication
    if (\Illuminate\Support\Facades\Auth::attempt(['email' => 'admin@itso.com', 'password' => 'admin123'])) {
        echo "Authentication: SUCCESS\n";
        echo "Authenticated user: " . \Illuminate\Support\Facades\Auth::user()->name . "\n";
    } else {
        echo "Authentication: FAILED\n";
    }
} else {
    echo "User not found!\n";
}