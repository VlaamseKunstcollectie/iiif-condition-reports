<?php

namespace App\Utils;

class IIIFUtil
{
    public static function filterPublicImages($imageUrl) {
        if(strpos($imageUrl, '/public@') !== false) {
            return $imageUrl;
        } else {
            return '';
        }
    }

    public static function generateThumbnail($imageUrl, $onlyPublic = true)
    {
        if($onlyPublic) {
            $imageUrl = self::filterPublicImages($imageUrl);
            if($imageUrl === '') {
                return '';
            }
        }
        return $imageUrl . '/full/150,/0/default.jpg';
    }
}
