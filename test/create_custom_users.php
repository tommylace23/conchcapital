<?php
/**
 * Script to create users from a custom list
 * 
 * Instructions:
 * 1. Edit user_names.json and add your users (first_name, last_name, username, bio)
 * 2. Run: php create_custom_users.php
 */

require __DIR__ . '/backend/vendor/autoload.php';

$app = require_once __DIR__ . '/backend/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

// Load user names from JSON file
$usersFile = __DIR__ . '/user_names.json';

if (!file_exists($usersFile)) {
    echo "Error: user_names.json file not found!\n";
    echo "Please create the file with your user list.\n";
    exit(1);
}

$usersData = json_decode(file_get_contents($usersFile), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo "Error: Invalid JSON in user_names.json!\n";
    echo "JSON Error: " . json_last_error_msg() . "\n";
    exit(1);
}

if (empty($usersData) || !is_array($usersData)) {
    echo "Error: user_names.json is empty or not an array!\n";
    exit(1);
}

echo "Creating " . count($usersData) . " users from user_names.json...\n\n";

$created = 0;
$skipped = 0;
$errors = 0;

foreach ($usersData as $index => $userData) {
    $userNum = $index + 1;
    
    // Validate required fields
    if (empty($userData['first_name']) || empty($userData['last_name'])) {
        echo "[{$userNum}] SKIPPED: Missing first_name or last_name\n";
        $skipped++;
        continue;
    }
    
    // Generate username if not provided
    $username = $userData['username'] ?? strtolower($userData['first_name'] . $userData['last_name'] . rand(100, 999));
    
    // Check if username already exists
    if (User::where('username', $username)->exists()) {
        echo "[{$userNum}] SKIPPED: Username '{$username}' already exists\n";
        $skipped++;
        continue;
    }
    
    // Generate email
    $email = $userData['email'] ?? ($username . '@example.com');
    
    // Check if email already exists
    if (User::where('email', $email)->exists()) {
        echo "[{$userNum}] SKIPPED: Email '{$email}' already exists\n";
        $skipped++;
        continue;
    }
    
    // Generate display name - use provided display_name or generate from first_name + last_name
    $displayName = $userData['display_name'] ?? trim($userData['first_name'] . ' ' . $userData['last_name']);
    
    // Use provided bio or default
    $bio = $userData['bio'] ?? 'Building wealth through strategic investments and portfolio diversification.';
    
    // Avatar URL (if provided)
    $avatarUrl = $userData['avatar_url'] ?? null;
    
    // Random balances (some users with real funds, some without)
    $realBalance = isset($userData['real_balance']) ? $userData['real_balance'] : (rand(0, 10) > 3 ? rand(1000, 500000) : 0);
    $simulatedBalance = isset($userData['simulated_balance']) ? $userData['simulated_balance'] : rand(50000, 500000);
    
    // Random creation date (spread over last 6 months)
    $createdAt = isset($userData['created_at']) ? \Carbon\Carbon::parse($userData['created_at']) : now()->subDays(rand(0, 180));
    
    // Random last active (some recent, some older)
    $lastActive = isset($userData['last_active_at']) ? \Carbon\Carbon::parse($userData['last_active_at']) : (rand(0, 10) > 2 ? now()->subHours(rand(0, 72)) : now()->subDays(rand(4, 30)));
    
    // Password (use provided or default)
    $password = $userData['password'] ?? 'password';
    
    try {
        // Use display_name for name field as well if provided, otherwise use first_name + last_name
        $name = $userData['display_name'] ?? trim($userData['first_name'] . ' ' . $userData['last_name']);
        
        $userDataArray = [
            'name' => $name,
            'email' => $email,
            'username' => $username,
            'display_name' => $displayName,
            'bio' => $bio,
            'password' => Hash::make($password),
            'real_balance' => $realBalance,
            'simulated_balance' => $simulatedBalance,
            'balance_currency' => 'USD',
            'email_verified_at' => $createdAt,
            'is_admin' => false,
            'is_frozen' => false,
            'last_active_at' => $lastActive,
            'created_at' => $createdAt,
            'updated_at' => $lastActive,
        ];
        
        // Add avatar_url if provided
        if ($avatarUrl) {
            $userDataArray['avatar_url'] = $avatarUrl;
        }
        
        User::create($userDataArray);
        $created++;
        echo "[{$userNum}] ✓ Created: {$username} ({$displayName})\n";
    } catch (\Exception $e) {
        $errors++;
        echo "[{$userNum}] ✗ Error: {$username} - " . $e->getMessage() . "\n";
    }
}

echo "\n";
echo "========================================\n";
echo "Summary:\n";
echo "  Created: {$created}\n";
echo "  Skipped: {$skipped}\n";
echo "  Errors: {$errors}\n";
echo "========================================\n";
