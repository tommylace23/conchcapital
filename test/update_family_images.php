<?php
/**
 * Script to update family profiles with images
 * 
 * Instructions:
 * 1. Add your images to the images array below (URLs or local paths)
 * 2. Run: php update_family_images.php
 */

require __DIR__ . '/backend/vendor/autoload.php';

$app = require_once __DIR__ . '/backend/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;

// Load images from JSON file
$imagesFile = __DIR__ . '/family_images.json';

if (!file_exists($imagesFile)) {
    echo "Error: family_images.json file not found!\n";
    echo "Please create the file with your image mappings.\n";
    exit(1);
}

$familyImages = json_decode(file_get_contents($imagesFile), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo "Error: Invalid JSON in family_images.json!\n";
    echo "JSON Error: " . json_last_error_msg() . "\n";
    exit(1);
}

// Map of usernames to image URLs or paths
// You can use:
// - Full URLs: 'https://example.com/image.jpg'
// - Local paths: '/path/to/image.jpg' (will be uploaded to storage)
// - Storage paths: '/storage/avatars/family.jpg'

echo "=== Updating Family Profile Images ===\n\n";

$updated = 0;
$skipped = 0;
$errors = 0;

foreach ($familyImages as $username => $imagePath) {
    if (empty($imagePath)) {
        echo "[SKIP] {$username}: No image provided\n";
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
        // If it's a URL, use it directly
        if (filter_var($imagePath, FILTER_VALIDATE_URL)) {
            $user->avatar_url = $imagePath;
            $user->save();
            echo "[✓] {$username}: Updated with URL image\n";
            $updated++;
        }
        // If it's a local file path, upload it
        elseif (file_exists($imagePath)) {
            // Copy file to storage
            $extension = pathinfo($imagePath, PATHINFO_EXTENSION);
            $filename = $username . '.' . $extension;
            $destination = storage_path('app/public/avatars/' . $filename);
            
            // Create directory if it doesn't exist
            $dir = dirname($destination);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            copy($imagePath, $destination);
            $user->avatar_url = '/storage/avatars/' . $filename;
            $user->save();
            echo "[✓] {$username}: Uploaded and updated with image\n";
            $updated++;
        }
        // If it's already a storage path, use it directly
        elseif (strpos($imagePath, '/storage/') === 0) {
            $user->avatar_url = $imagePath;
            $user->save();
            echo "[✓] {$username}: Updated with storage path\n";
            $updated++;
        }
        else {
            echo "[ERROR] {$username}: Invalid image path: {$imagePath}\n";
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
echo "  Updated: {$updated}\n";
echo "  Skipped: {$skipped}\n";
echo "  Errors: {$errors}\n";
echo "========================================\n";
