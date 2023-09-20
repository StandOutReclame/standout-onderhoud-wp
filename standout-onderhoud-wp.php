<?php
/**
 * StandOut Onderhoud WP Plugin
 *
 * StandOut Onderhoud WP Plugin
 *
 * @link              https://standout.nl
 * @since             1.0.0
 * @package           Standout_Onderhoud
 *
 * @wordpress-plugin
 * Plugin Name:       StandOut Onderhoud
 * Plugin URI:        https://standout.nl
 * Description:       StandOut Onderhoud WP Plugin
 * Version:           1.0.0
 * Author:            StandOut B.V.
 * Author URI:        https://standout.nl
 */

/**
 * If this file is called directly, then abort execution.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require 'includes/traits/DebugTrait.php';
require 'includes/StandoutOnderhoudRest.php';

/**
 * Class StandOut Onderhoud
 * singleton
 */
class StandoutOnderhoud {

    public function run()
    {
        // init the rest methods
        $rest = new StandoutOnderhoudRest();

    }

}

$standout_onderhoud = new StandoutOnderhoud;
$standout_onderhoud->run();