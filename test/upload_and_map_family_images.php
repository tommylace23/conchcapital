<?php
/**
 * Script to upload family images and map them to profiles
 * 
 * This script will:
 * 1. Copy images from "the family names" folder to server storage
 * 2. Map images 1-12 to the first 12 family profiles
 */

require __DIR__ . '/backend/vendor/autoload.php';

$app = require_once __DIR__ . '/backend/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Storage;

// Mapping: Image number => Username
$imageMapping = [
    1 => 'whitmorefamily',
    2 => 'ashfordfamily',
    3 => 'pembertonfamily',
    4 => 'hartleyfamily',
    5 => 'sinclairfamily',
    6 => 'weatherbyfamily',
    7 => 'thorntonfamily',
    8 => 'blackwoodfamily',
    9 => 'morrisonfamily',
    10 => 'hendersonfamily',
    11 => 'crawfordfamily',
    12 => 'sullivanfamily',
    13 => 'mitchellfamily',
    14 => 'reynoldsfamily',
];

// Path to the images folder (on server)
$imagesFolder = '/tmp/family_images';

echo "=== Uploading and Mapping Family Images ===\n\n";

$uploaded = 0;
$updated = 0;
$skipped = 0;
$errors = 0;

// Ensure avatars directory exists
$avatarsPath = storage_path('app/public/avatars');
if (!is_dir($avatarsPath)) {
    mkdir($avatarsPath, 0755, true);
}

foreach ($imageMapping as $imageNum => $username) {
    $imageFile = $imagesFolder . '/' . $imageNum . '.jpeg';
    
    if (!file_exists($imageFile)) {
        echo "[SKIP] {$username}: Image {$imageNum}.jpeg not found\n";
        $skipped++;
        continue;
    }
    
    $user = User::where('username', $username)->first();
    
    if (!$user) {
        echo "[ERROR] {$username}: User not found\n";
        $errors++;
        continue;
    }
    
    try {
        // Copy file to storage with username as filename
        $destinationFile = $avatarsPath . '/' . $username . '.jpeg';
        
        if (copy($imageFile, $destinationFile)) {
            // Set avatar_url to storage path
            $user->avatar_url = '/storage/avatars/' . $username . '.jpeg';
            $user->save();
            
            echo "[✓] {$username}: Uploaded image {$imageNum}.jpeg and updated profile\n";
            $uploaded++;
            $updated++;
        } else {
            echo "[ERROR] {$username}: Failed to copy image file\n";
            $errors++;
        }
    } catch (\Exception $e) {
        echo "[ERROR] {$username}: " . $e->getMessage() . "\n";
        $errors++;
    }
}

echo "\n";
echo "========================================\n";
echo "Summary:\n";
echo "  Uploaded: {$uploaded} images\n";
echo "  Updated: {$updated} profiles\n";
echo "  Skipped: {$skipped}\n";
echo "  Errors: {$errors}\n";
echo "========================================\n";
echo "\n";
echo "Images have been uploaded to: /storage/avatars/\n";
echo "You may need to create a symbolic link if it doesn't exist:\n";
echo "  cd /var/www/conchcapital/backend && php artisan storage:link\n";
