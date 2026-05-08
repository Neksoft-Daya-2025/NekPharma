<?php

namespace App\Helper;

class Common
{

    /** Return success response
     * @param $date
     * @return string
     */

    public static function dateColor($date, $past=true): string
    {
        if (is_null($date)) {
            return '--';
        }

        $formattedDate = $date->translatedFormat(company()->date_format);
        $todayText = __('app.today');

        if ($date->setTimezone(company()->timezone)->isToday()) {
            return '<span class="text-success">' . $todayText . '</span>';
        }

        if ($date->endOfDay()->isPast() && $past ) {
            return '<span class="text-danger">' . $formattedDate . '</span>';
        }

        return '<span>' . $formattedDate . '</span>';
    }

    public static function active(): string
    {
        return '<i class="fa fa-circle mr-1 text-light-green f-10"></i>' . __('app.active');
    }

    public static function inactive(): string
    {
        return '<i class="fa fa-circle mr-1 text-red f-10"></i>' . __('app.inactive');
    }

    public static function encryptDecrypt($string, $action = 'encrypt')
    {

        // DO NOT CHANGE IT. CHANGING IT WILL AFFECT THE APPLICATION
        $secret_key = 'worksuite'; // User define private key
        $secret_iv = 'froiden'; // User define secret key

        $encryptMethod = 'AES-256-CBC';
        $key = hash('sha256', $secret_key);
        $iv = substr(hash('sha256', $secret_iv), 0, 16); // sha256 is hash_hmac_algo

        if ($action == 'encrypt') {
            $output = openssl_encrypt($string, $encryptMethod, $key, 0, $iv);

            return base64_encode($output);
        }

        if ($action == 'decrypt') {
            return openssl_decrypt(base64_decode($string), $encryptMethod, $key, 0, $iv);
        }

        throw new \Exception('No action provided for Common::encryptDecrypt');

    }

    /**
     * Sanitize string for use in SQL LIKE queries
     * Escapes special characters that could break SQL queries
     *
     * @param string|null $string
     * @return string
     */
    public static function safeString($string): string
    {
        if (is_null($string)) {
            return '';
        }

        // Remove HTML tags and escape special characters for SQL LIKE queries
        $string = strip_tags($string);
        $string = addslashes($string);
        
        // Escape SQL LIKE wildcards
        $string = str_replace(['%', '_'], ['\%', '\_'], $string);
        
        return $string;
    }

    /**
     * Haversine distance in meters between two lat/long points (SRS 3.2.5 DCR GPS).
     *
     * @param float $lat1
     * @param float $lon1
     * @param float $lat2
     * @param float $lon2
     * @return float
     */
    public static function distanceInMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // meters
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }

}
