<?php

namespace Standout\WpOnderhoud\Includes\Endpoints;

use Standout\WpOnderhoud\Includes\Traits\DebugTrait;
use WP_REST_Request;
use WP_Automatic_Updater;
use Automatic_Upgrader_Skin;
use Core_Upgrader;
use Exception;

class CoreEndpoints
{

    use DebugTrait;

    private $log;
    private $updated = [];
    protected $update_results = array();

    public function __construct()
    {
        $this->register_routes();
    }

    /**
     * Register the routes
     */
    public function register_routes()
    {
        add_action('rest_api_init', function () {
            register_rest_route('standout-onderhoud/v1', 'core', array(
                'methods' => 'GET',
                'callback' => array($this, 'getCore'),
            ));
            register_rest_route('standout-onderhoud/v1', 'core/update', array(
                'methods' => 'GET',
                'callback' => array($this, 'doUpdateCore'),
            ));
        });
    }

    /**
     * Captures core update results from hook, only way to get them
     *
     * @param $results
     */
    public function capture_core_update_results($results)
    {
        $this->update_results = $results;
    }

    /**
     * Get the core 
     */
    public function getCore(WP_REST_Request $request)
    {

        if (!function_exists('get_plugins') || !function_exists('get_core_updates')) {
            require_once ABSPATH . 'wp-admin/includes/update.php';
        }

        // Clear existing caches.
        wp_clean_update_cache();

        wp_version_check();  // Check for core updates.
        wp_update_themes();  // Check for theme updates.
        wp_update_plugins(); // Check for plugin updates.

        $coreUpdates = get_core_updates();
        $coreNeedsUpdate = false;
        if ($coreUpdates) {
            $coreUpdates = $coreUpdates[0];
            if ($coreUpdates->response !== 'latest') {
                $coreNeedsUpdate = true;
            }
        }

        wp_send_json_success(
            [
                'ts' => time(),
                'needsUpdate' => $coreNeedsUpdate,
                'core' => $coreUpdates
            ]
        );
        wp_die();
    }

    /**
     * Handler for updating the core
     */
    public function doUpdateCore()
    {

        $this->enableWPDebugMode();

        $this->updateCore();

        wp_send_json_success(
            [
                'ts' => time(),
                'log' => $this->log,
                'updated' => $this->updated
            ]
        );
        wp_die();
    }

    /**
     * Update the core
     */
    private function updateCore()
    {
        try {

            include_once(ABSPATH . 'wp-admin/includes/update.php');
            include_once(ABSPATH . 'wp-admin/includes/class-wp-upgrader.php');
            include_once(ABSPATH . 'wp-admin/includes/class-wp-automatic-updater.php');
            include_once(ABSPATH . 'wp-admin/includes/file.php');

            add_action('automatic_updates_complete', array($this, 'capture_core_update_results'));

            add_filter("auto_update_core", '__return_true', 99999); //temporarily allow core autoupdates
            add_filter("allow_major_auto_core_updates", '__return_true', 99999); //temporarily allow core autoupdates
            add_filter("allow_minor_auto_core_updates", '__return_true', 99999); //temporarily allow core autoupdates
            add_filter("auto_update_core", '__return_true', 99999); //temporarily allow core autoupdates
            add_filter("auto_update_theme", '__return_false', 99999);
            add_filter("auto_update_plugin", '__return_false', 99999);

            // For plugin development.
            if (wp_get_environment_type() === 'development') {
                add_filter('automatic_updates_is_vcs_checkout', '__return_false', 1);
            }

            $upgrader = new WP_Automatic_Updater;

            if (!$this->can_update()) {
                $this->disableWPDebugMode();
                return;
            }

            $core_update = find_core_auto_update();
            $result = $upgrader->update('core', $core_update);

            $this->log[] = 'Updated core to version ' . $result;
            $this->updated = $core_update;

            // Finally, process any new translations.
            $language_updates = wp_get_translation_updates();
            if ($language_updates) {
                foreach ($language_updates as $update) {
                    $upgrader->update('translation', $update);
                }

                // Clear existing caches.
                wp_clean_update_cache();

                wp_version_check();  // Check for core updates.
                wp_update_themes();  // Check for theme updates.
                wp_update_plugins(); // Check for plugin updates.
            }

            $this->disableWPDebugMode();
        } catch (Exception $e) {

            $this->log[] = $e->getMessage();
            $this->disableWPDebugMode();

            return;
        }
    }

    private function can_update()
    {
        global $wp_version, $wpdb;

        $upgrader = new WP_Automatic_Updater;

        // Check if auto updates are enabled.
        if ($upgrader->is_disabled() || (defined('WP_AUTO_UPDATE_CORE') && false === WP_AUTO_UPDATE_CORE)) {
            $this->log[] = 'Automatic core updates are disabled via define or filter';
            return false;
        }

        // Check if WP_Filesystem allows unattended updates.
        $skin = new Automatic_Upgrader_Skin;
        if (!$skin->request_filesystem_credentials(false, ABSPATH, false)) {
            $this->log[] = 'Could not access filesystem';
            return false;
        }

        // Check if the wordpress install is under version control, git and other systems.
        if ($upgrader->is_vcs_checkout(ABSPATH)) {
            $this->log[] = 'Automatic core updates are disabled when WordPress is checked out from version control.';
            return false;
        }

        // Check for Core updates
        wp_version_check();
        $updates = get_site_transient('update_core');
        if (!$updates || empty($updates->updates)) {
            return false;
        }

        $auto_update = false;
        foreach ($updates->updates as $update) {
            if ('autoupdate' != $update->response) {
                continue;
            }

            if (!$auto_update || version_compare($update->current, $auto_update->current, '>')) {
                $auto_update = $update;
            }
        };

        if (!$auto_update) {
            $this->log[] = 'No WordPress core updates appear available';
            return false;
        }

        // Check if PHP and MYSQL verison are compatible with updates.
        $php_compat = version_compare(phpversion(), $auto_update->php_version, '>=');
        if (file_exists(WP_CONTENT_DIR . '/db.php') && empty($wpdb->is_mysql)) {
            $mysql_compat = true;
        } else {
            $mysql_compat = version_compare($wpdb->db_version(), $auto_update->mysql_version, '>=');
        }

        if (!$php_compat || !$mysql_compat) {
            $this->log[] = 'The new version of WordPress is incompatible with your PHP ar MYSQL version.';
            return false;
        }

        // Check if auto updates are enabled.
        if (!Core_Upgrader::should_update_to_version($auto_update->current)) {
            $this->log[] = 'Automatic core updates are disabled via define or filter';
            return false;
        }

        // We should be able to update.
        return true;
    }
}
