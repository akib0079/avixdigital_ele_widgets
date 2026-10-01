<?php
// Delivery derivatives only: preserve the supplied artwork and its aspect ratio.
foreach ([1,2,3] as $index) {
    $source=imagecreatefrompng(__DIR__.'/assets/service-'.$index.'.png');
    foreach ([384,768,1152] as $width) {
        $height=(int)round(imagesy($source)*$width/imagesx($source));
        $image=imagecreatetruecolor($width,$height);
        imagealphablending($image,false); imagesavealpha($image,true);
        imagecopyresampled($image,$source,0,0,0,0,$width,$height,imagesx($source),imagesy($source));
        imagewebp($image,__DIR__.'/assets/service-'.$index.'-'.$width.'.webp',86);
        imagedestroy($image);
    }
    imagedestroy($source);
}
echo 'Responsive WebP delivery assets created.'.PHP_EOL;
