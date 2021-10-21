<?php

namespace App\Utils;

use Exception;
use Imagick;

class IIIFUtil
{
    public static $imageWidth = 150;

    public static function filterPublicImages($imageUrl) {
        if(strpos($imageUrl, '/public@') !== false) {
            return $imageUrl;
        } else {
            return '';
        }
    }

    public static function generateIIIFThumbnail($imageUrl, $onlyPublic = true)
    {
        if($onlyPublic) {
            $imageUrl = self::filterPublicImages($imageUrl);
            if($imageUrl === '') {
                return '';
            }
        }
        return $imageUrl . '/full/' . self::$imageWidth . ',/0/default.jpg';
    }

    public static function generateThumbnail($file, $thumbnail)
    {
        if (is_file($file)) {
            $imagick = new Imagick(realpath($file));
            $imagick->setImageFormat('jpeg');
            $imagick->setImageCompression(Imagick::COMPRESSION_JPEG);
            $imagick->setImageCompressionQuality(100);
            $imagick->thumbnailImage(self::$imageWidth, 500, true, false);
            if (file_put_contents($thumbnail, $imagick) === false) {
                throw new Exception("Could not store thumbnail.");
            }
            return true;
        }
        else {
            throw new Exception("No valid image provided with {$file}.");
        }
    }
}
