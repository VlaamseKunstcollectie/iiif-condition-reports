<?php

namespace App\Utils;

class CurlUtil
{
    public static function get($url, $trustHost = true, $overrideCA = false, $sslCA = null) {
        $ch = curl_init();
        if($trustHost) {
            curl_setopt($ch,CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, false);
        }
        if ($overrideCA) {
            curl_setopt($ch,CURLOPT_CAINFO, $sslCA);
            curl_setopt($ch,CURLOPT_CAPATH, $sslCA);
        }
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch,CURLOPT_URL, $url);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
}
