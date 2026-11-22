<?php
/**
 * Script to create 50 sample users for the profile search page
 * Run this on the server: php create_sample_users.php
 */

require __DIR__ . '/backend/vendor/autoload.php';

$app = require_once __DIR__ . '/backend/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

// Sample first names and last names for realistic names
$firstNames = ['Alex', 'Jordan', 'Taylor', 'Morgan', 'Casey', 'Riley', 'Avery', 'Quinn', 'Sage', 'River', 'Cameron', 'Dakota', 'Blake', 'Hayden', 'Reese', 'Finley', 'Rowan', 'Phoenix', 'Skylar', 'Emery', 'James', 'Michael', 'Robert', 'David', 'William', 'Richard', 'Joseph', 'Thomas', 'Christopher', 'Daniel', 'Matthew', 'Anthony', 'Mark', 'Donald', 'Steven', 'Paul', 'Andrew', 'Joshua', 'Kenneth', 'Kevin', 'Brian', 'George', 'Timothy', 'Ronald', 'Jason', 'Edward', 'Jeffrey', 'Ryan', 'Jacob', 'Gary'];

$lastNames = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis', 'Rodriguez', 'Martinez', 'Hernandez', 'Lopez', 'Wilson', 'Anderson', 'Thomas', 'Taylor', 'Moore', 'Jackson', 'Martin', 'Lee', 'Thompson', 'White', 'Harris', 'Sanchez', 'Clark', 'Ramirez', 'Lewis', 'Robinson', 'Walker', 'Young', 'Allen', 'King', 'Wright', 'Scott', 'Torres', 'Nguyen', 'Hill', 'Flores', 'Green', 'Adams', 'Nelson', 'Baker', 'Hall', 'Rivera', 'Campbell', 'Mitchell', 'Carter', 'Roberts', 'Gomez', 'Phillips'];

$bios = [
    'Building wealth through strategic investments and portfolio diversification.',
    'Passionate about cryptocurrency and blockchain technology.',
    'Long-term investor focused on sustainable growth strategies.',
    'Exploring new investment opportunities in emerging markets.',
    'Professional trader with 10+ years of experience.',
    'Learning and growing my investment portfolio every day.',
    'Interested in DeFi and decentralized finance opportunities.',
    'Building a diversified portfolio for financial independence.',
    'Active investor in stocks, crypto, and alternative assets.',
    'Focused on value investing and long-term wealth creation.',
    'Trading enthusiast exploring various market strategies.',
    'Investment professional specializing in risk management.',
    'Crypto investor and blockchain technology advocate.',
    'Building a strong financial foundation through smart investing.',
    'Passionate about financial markets and economic trends.',
    'Experienced investor with a focus on growth stocks.',
    'Learning the fundamentals of investment management.',
    'Building wealth through systematic investment strategies.',
    'Active trader and portfolio manager.',
    'Investment advisor and financial planning enthusiast.',
    'Crypto trader exploring new opportunities daily.',
    'Long-term investor with a focus on blue-chip assets.',
    'Building a retirement portfolio through smart investments.',
    'Day trader specializing in technical analysis.',
    'Investment strategist focused on market trends.',
    'Building wealth through disciplined investment practices.',
    'Crypto enthusiast and blockchain investor.',
    'Professional investor with expertise in multiple asset classes.',
    'Learning about investment strategies and market dynamics.',
    'Building a diversified portfolio for long-term growth.',
    'Active investor in traditional and digital assets.',
    'Investment professional with a focus on risk-adjusted returns.',
    'Exploring new investment opportunities and strategies.',
    'Building financial independence through smart investing.',
    'Crypto investor and DeFi protocol enthusiast.',
    'Long-term value investor focused on fundamentals.',
    'Building wealth through systematic savings and investing.',
    'Investment advisor helping others achieve financial goals.',
    'Active trader with expertise in multiple markets.',
    'Building a strong investment portfolio for the future.',
    'Crypto trader and blockchain technology investor.',
    'Investment professional specializing in portfolio optimization.',
    'Learning about investment management and financial planning.',
    'Building wealth through strategic asset allocation.',
    'Active investor exploring new market opportunities.',
    'Investment strategist with a focus on growth.',
    'Building a diversified portfolio across asset classes.',
    'Crypto enthusiast and digital asset investor.',
    'Professional investor with years of market experience.',
    'Building financial security through smart investment decisions.'
];

$usernames = [];
$created = 0;

echo "Creating 50 sample users...\n";

for ($i = 1; $i <= 50; $i++) {
    // Generate unique username
    $firstName = $firstNames[array_rand($firstNames)];
    $lastName = $lastNames[array_rand($lastNames)];
    $baseUsername = strtolower($firstName . $lastName . rand(100, 999));
    
    // Ensure username is unique
    while (in_array($baseUsername, $usernames) || User::where('username', $baseUsername)->exists()) {
        $baseUsername = strtolower($firstName . $lastName . rand(1000, 9999));
    }
    $usernames[] = $baseUsername;
    
    // Generate email
    $email = $baseUsername . '@example.com';
    
    // Generate display name
    $displayName = $firstName . ' ' . $lastName;
    
    // Random bio
    $bio = $bios[array_rand($bios)];
    
    // Random balances (some users with real funds, some without)
    $realBalance = rand(0, 10) > 3 ? rand(1000, 500000) : 0; // 70% chance of having real balance
    $simulatedBalance = rand(50000, 500000); // All have simulated balance
    
    // Random creation date (spread over last 6 months)
    $createdAt = now()->subDays(rand(0, 180));
    
    // Random last active (some recent, some older)
    $lastActive = rand(0, 10) > 2 ? now()->subHours(rand(0, 72)) : now()->subDays(rand(4, 30));
    
    try {
        User::create([
            'name' => $displayName,
            'email' => $email,
            'username' => $baseUsername,
            'display_name' => $displayName,
            'bio' => $bio,
            'password' => Hash::make('password'), // Default password for all sample users
            'real_balance' => $realBalance,
            'simulated_balance' => $simulatedBalance,
            'balance_currency' => 'USD',
            'email_verified_at' => $createdAt, // All verified
            'is_admin' => false,
            'is_frozen' => false,
            'last_active_at' => $lastActive,
            'created_at' => $createdAt,
            'updated_at' => $lastActive,
        ]);
        $created++;
        echo "Created user {$i}/50: {$baseUsername}\n";
    } catch (\Exception $e) {
        echo "Error creating user {$i}: " . $e->getMessage() . "\n";
    }
}

echo "\nSuccessfully created {$created} sample users!\n";
