<?php
// At the beginning of your file, add this code to set up the images directory

// Check if the images directory exists, if not create it
$imagesDir = __DIR__ . '/assets/images';
if (!file_exists($imagesDir)) {
    mkdir($imagesDir, 0755, true);
}

// Create a default image if it doesn't exist
$defaultImage = $imagesDir . '/default.png';
if (!file_exists($defaultImage)) {
    // Create a simple default image programmatically
    $image = imagecreate(200, 200);
    $bgColor = imagecolorallocate($image, 240, 240, 240);
    $textColor = imagecolorallocate($image, 100, 100, 100);
    imagestring($image, 5, 40, 90, "No Image Available", $textColor);
    imagepng($image, $defaultImage);
    imagedestroy($image);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../routes/web.php';

$products = Product::getAllProducts();
?>