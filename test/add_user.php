<?php
/**
 * Script to add a specific user to the database
 */

require __DIR__ . '/backend/vendor/autoload.php';

$app = require_once __DIR__ . '/backend/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

$email = 'cm.muncaster@gmail.com';
$password = 'Sohil333';
$name = 'Sohil Yasin';

echo "Adding user: {$email}\n\n";

// Check if user already exists
$existingUser = User::where('email', $email)->first();

if ($existingUser) {
    echo "User already exists!\n";
    echo "Updating password...\n";
    
    $existingUser->password = Hash::make($password);
    $existingUser->save();
    
    echo "✓ Password updated for existing user\n";
    echo "User ID: {$existingUser->id}\n";
    echo "Username: {$existingUser->username}\n";
    echo "Email: {$existingUser->email}\n";
} else {
    // Generate username from email
    $username = explode('@', $email)[0];
    
    // Ensure username is unique
    $baseUsername = $username;
    $counter = 1;
    while (User::where('username', $username)->exists()) {
        $username = $baseUsername . $counter;
        $counter++;
    }
    
    // Create user
    $user = User::create([
        'name' => $name,
        'email' => $email,
        'username' => $username,
        'display_name' => $name,
        'password' => Hash::make($password),
        'real_balance' => 0,
        'simulated_balance' => 0,
        'balance_currency' => 'USD',
        'email_verified_at' => now(),
        'is_admin' => false,
        'is_frozen' => false,
        'last_active_at' => now(),
    ]);
    
    echo "✓ User created successfully!\n";
    echo "User ID: {$user->id}\n";
    echo "Username: {$user->username}\n";
    echo "Email: {$user->email}\n";
    echo "Display Name: {$user->display_name}\n";
}

echo "\n✅ Done!\n";
