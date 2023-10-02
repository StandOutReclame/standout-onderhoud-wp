<?php

namespace Standout\WpOnderhoud\Includes\Traits;

trait DebugTrait
{
    /**
     * Enable the WP Debug mode
     */
    private function enableWPDebugMode()
    {

        $debug_mode = WP_DEBUG;

        if (!defined('WP_DEBUG_LOG') || !WP_DEBUG_LOG) {
            $this->log[] = '<span style="color: red"><b>Error: WP_DEBUG_LOG is not defined in wp-config</b></span>';
            return false;
        }

        if (!$debug_mode) {
            // enable debug mode;
            $filepath = ABSPATH . 'wp-config.php';
            $file = file_get_contents($filepath);

            $file = str_replace(
                array("define( 'WP_DEBUG', false )"),
                array("define( 'WP_DEBUG', true )"),
                $file
            );

            file_put_contents($filepath, $file);
        }

        $this->clearCacheKinsta();

        return true;
    }

    /**
     * Disable the WP Debug mode
     */
    private function disableWPDebugMode()
    {

        $debug_mode = WP_DEBUG;

        if ($debug_mode) {
            // disable debug mode;
            $filepath = ABSPATH . 'wp-config.php';
            $file = file_get_contents($filepath);

            $file = str_replace(
                array("define( 'WP_DEBUG', true )"),
                array("define( 'WP_DEBUG', false )"),
                $file
            );

            file_put_contents($filepath, $file);
        }

        $this->clearCacheKinsta();

        return true;
    }

    /**
     * Clear the site cache
     */
    private function clearCacheKinsta()
    {
        // clear the caches
        $ch = curl_init();

        // set URL and other appropriate options
        curl_setopt($ch, CURLOPT_URL, get_site_url() . 'kinsta-clear-cache-all/');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // grab URL and pass it to the browser
        curl_exec($ch);

        // close cURL resource, and free up system resources
        curl_close($ch);
    }
}
