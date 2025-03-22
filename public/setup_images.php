<?php
// Simple script to set up sample product images

// Sample product images to be created
$sampleImages = [
    'bcaa.png',
    'kreatin.png',
    'rukavice.png',
    'tycinka_cokolada.png',
    'tricko_panske.png',
    'gainer.png',
    'leginy.png',
    'sejkr.png',
    'tracker.png'
];

// Directory to store images
$imagesDir = __DIR__ . '/assets/images';

// Create directory if it doesn't exist
if (!file_exists($imagesDir)) {
    if (mkdir($imagesDir, 0755, true)) {
        echo "Created directory: $imagesDir<br>";
    } else {
        echo "Failed to create directory: $imagesDir<br>";
        exit;
    }
}

// Create sample images
foreach ($sampleImages as $imageName) {
    $imagePath = $imagesDir . '/' . $imageName;
    
    if (!file_exists($imagePath)) {
        // Create a simple colored image with the product name
        $image = imagecreate(300, 300);
        
        // Random background color
        $bgR = rand(200, 255);
        $bgG = rand(200, 255);
        $bgB = rand(200, 255);
        $bgColor = imagecolorallocate($image, $bgR, $bgG, $bgB);
        
        // Text color
        $textColor = imagecolorallocate($image, 50, 50, 50);
        
        // Draw product name
        $productName = pathinfo($imageName, PATHINFO_FILENAME);
        imagestring($image, 5, 50, 140, $productName, $textColor);
        
        // Save the image
        imagepng($image, $imagePath);
        imagedestroy($image);
        
        echo "Created sample image: $imageName<br>";
    } else {
        echo "Image already exists: $imageName<br>";
    }
}

echo "<p>Setup complete! <a href='/mprojekt/public/admin'>Go to Admin Panel</a></p>";
?>
