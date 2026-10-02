<?php

$srcPath = 'public/images/logo/logo_original.png';
$dstPath = 'public/images/logo/logo.png';

$src = imagecreatefrompng($srcPath);
$width = imagesx($src);
$height = imagesy($src);

$dst = imagecreatetruecolor($width, $height);
imagesavealpha($dst, true);
$transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
imagefill($dst, 0, 0, $transparent);

// Rounded corner radius: ~240px on 1254px (approx 19%, perfect rounded squircle)
$radius = 240;

for ($x = 0; $x < $width; $x++) {
    for ($y = 0; $y < $height; $y++) {
        // Determine distance to corner if in corner regions
        $inCorner = false;
        $cx = 0;
        $cy = 0;

        if ($x < $radius && $y < $radius) {
            // Top-left
            $inCorner = true;
            $cx = $radius;
            $cy = $radius;
        } elseif ($x >= $width - $radius && $y < $radius) {
            // Top-right
            $inCorner = true;
            $cx = $width - $radius - 1;
            $cy = $radius;
        } elseif ($x < $radius && $y >= $height - $radius) {
            // Bottom-left
            $inCorner = true;
            $cx = $radius;
            $cy = $height - $radius - 1;
        } elseif ($x >= $width - $radius && $y >= $height - $radius) {
            // Bottom-right
            $inCorner = true;
            $cx = $width - $radius - 1;
            $cy = $height - $radius - 1;
        }

        if ($inCorner) {
            $dx = $x - $cx;
            $dy = $y - $cy;
            $dist = sqrt($dx * $dx + $dy * $dy);

            if ($dist <= $radius - 1) {
                // Inside corner arc
                $color = imagecolorat($src, $x, $y);
                imagesetpixel($dst, $x, $y, $color);
            } elseif ($dist <= $radius) {
                // Edge anti-aliasing
                $alphaRatio = $radius - $dist;
                $color = imagecolorat($src, $x, $y);
                $r = ($color >> 16) & 0xFF;
                $g = ($color >> 8) & 0xFF;
                $b = $color & 0xFF;
                $origAlpha = ($color >> 24) & 0x7F;
                $newAlpha = (int) (127 - (127 - $origAlpha) * $alphaRatio);
                $newColor = imagecolorallocatealpha($dst, $r, $g, $b, max(0, min(127, $newAlpha)));
                imagesetpixel($dst, $x, $y, $newColor);
            }
            // else outside corner: transparent
        } else {
            // Inside straight sides or center
            $color = imagecolorat($src, $x, $y);
            imagesetpixel($dst, $x, $y, $color);
        }
    }
}

imagepng($dst, $dstPath);
imagedestroy($src);
imagedestroy($dst);

echo "Successfully applied smooth border-radius to logo.png!\n";
