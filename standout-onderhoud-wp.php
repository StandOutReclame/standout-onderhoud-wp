<?php

namespace Standout\WpOnderhoud;

use Standout\WpOnderhoud\Includes\StandoutOnderhoudRest;
use Standout\WpOnderhoud\Updates\StandoutOnderhoudUpdater;

/**
 * StandOut Onderhoud WP Plugin
 *
 * StandOut Onderhoud WP Plugin
 *
 * @link              https://standout.nl
 * @package           Standout_Onderhoud
 *
 * @wordpress-plugin
 * Plugin Name:       StandOut Onderhoud
 * Plugin URI:        https://standout.nl
 * Description:       StandOut Onderhoud WP Plugin
 * Version:           1.0.5
 * Author:            StandOut B.V.
 * Author URI:        https://standout.nl
 */

/**
 * Composer setup.
 */
require_once(plugin_dir_path(__FILE__) . 'vendor/autoload.php');

/**
 * If this file is called directly, then abort execution.
 */
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class StandOut Onderhoud
 * singleton
 */
class StandoutOnderhoud
{

    private $updater;

    public function __construct()
    {
        $this->updater = new StandoutOnderhoudUpdater;
    }

    public function run()
    {
        // init the rest methods
        $rest = new StandoutOnderhoudRest;
    }
}

$standout_onderhoud = new StandoutOnderhoud;
$standout_onderhoud->run();
