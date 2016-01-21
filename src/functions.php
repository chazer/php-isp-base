<?php
/**
 * functions.php
 *
 * @author: chazer
 * @created: 21.01.16 14:02
 */

if (!function_exists('getenvall')) {
    /**
     * Gets the array of an all environment variables
     *
     * @return array
     */
    function getenvall()
    {
        $keys = array_unique(array_merge(array_keys($_SERVER), array_keys($_ENV)));
        $out = [];
        foreach ($keys as $key) {
            if (false !== ($env = getenv($key))) {
                $out[$key] = $env;
            }
        }
        return $out;
    }
}
