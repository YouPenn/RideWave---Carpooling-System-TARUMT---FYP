<?php
header('Content-Type: image/png');
$image = imagecreatetruecolor(100, 100);
$background_color = imagecolorallocate($image, 255, 255, 255); // White
imagefill($image, 0, 0, $background_color);
imagepng($image);
imagedestroy($image);
?>