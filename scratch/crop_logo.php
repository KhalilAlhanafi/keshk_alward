<?php

$srcPath = __DIR__ . '/../public/images/logo.png';
$img = imagecreatefrompng($srcPath);

$width = imagesx($img);
$height = imagesy($img);

echo "Original Size: {$width}x{$height}\n";

// Find bounding box of non-white / non-transparent pixels (the circle)
$minX = $width;
$maxX = 0;
$minY = $height;
$maxY = 0;

for ($y = 0; $y < $height; $y++) {
    for ($x = 0; $x < $width; $x++) {
        $rgba = imagecolorat($img, $x, $y);
        $colors = imagecolorsforindex($img, $rgba);
        
        // Check if pixel is not pure white and not transparent
        $isWhite = ($colors['red'] >= 250 && $colors['green'] >= 250 && $colors['blue'] >= 250);
        $isTransparent = ($colors['alpha'] >= 120);

        if (!$isWhite && !$isTransparent) {
            if ($x < $minX) $minX = $x;
            if ($x > $maxX) $maxX = $x;
            if ($y < $minY) $minY = $y;
            if ($y > $maxY) $maxY = $y;
        }
    }
}

echo "Detected Bounding Box: minX=$minX, maxX=$maxX, minY=$minY, maxY=$maxY\n";

$cropWidth = ($maxX - $minX) + 1;
$cropHeight = ($maxY - $minY) + 1;

// Make it a clean square
$side = max($cropWidth, $cropHeight);
$offsetX = $minX - (int)(($side - $cropWidth) / 2);
$offsetY = $minY - (int)(($side - $cropHeight) / 2);

// Add small 2px padding
$padding = 4;
$finalSide = $side + ($padding * 2);

$cropped = imagecreatetruecolor($finalSide, $finalSide);
imagealphablending($cropped, false);
imagesavealpha($cropped, true);
$transparent = imagecolorallocatealpha($cropped, 255, 255, 255, 127);
imagefilledrectangle($cropped, 0, 0, $finalSide, $finalSide, $transparent);

imagecopy(
    $cropped,
    $img,
    $padding,
    $padding,
    $offsetX,
    $offsetY,
    $side,
    $side
);

// Save cropped logo to public/images/logo.png
imagepng($cropped, $srcPath);
imagepng($cropped, __DIR__ . '/../public/storage/logo.png');

echo "Cropped and saved successfully as square {$finalSide}x{$finalSide}!\n";
