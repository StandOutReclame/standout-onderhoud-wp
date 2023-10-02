<?php

namespace Standout\WpOnderhoud\Includes\Endpoints;

use Standout\WpOnderhoud\Includes\Traits\DebugTrait;
use WP_REST_Request;
use WP_Ajax_Upgrader_Skin;
use Plugin_Upgrader;
use Exception;

class PluginsEndpoints
{

    use DebugTrait;

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
            register_rest_route('standout-onderhoud/v1', 'plugins', array(
                'methods' => 'GET',
                'callback' => array($this, 'getPlugins'),
            ));
            register_rest_route('standout-onderhoud/v1', 'plugins/update', array(
                'methods' => 'GET',
                'callback' => array($this, 'updatePlugins'),
            ));
        });
    }

    /**
     * Get the plugins
     */
    public function getPlugins(WP_REST_Request $request)
    {

        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        // Force refresh of plugin update information.
        wp_clean_plugins_cache();

        wp_update_plugins(); // Checks for available updates to plugins based on the latest versions hosted on WordPress.org.

        $plugins = get_plugins();
        $outOfDatePlugins = get_site_transient('update_plugins');

        wp_send_json_success(
            [
                'ts' => time(),
                'plugins' => $plugins,
                'needsUpdate' => $outOfDatePlugins
            ]
        );
        wp_die();
    }

    /**
     * 
     */
    public function updatePlugins(WP_REST_Request $request)
    {

        $log = [];

        $this->enableWPDebugMode();

        try {

            // For plugins_api/themes_api..
            include_once(ABSPATH . 'wp-admin/includes/class-wp-upgrader.php');
            include_once(ABSPATH . 'wp-admin/includes/plugin-install.php');
            include_once(ABSPATH . 'wp-admin/includes/theme-install.php');
            include_once(ABSPATH . 'wp-admin/includes/plugin.php');
            include_once(ABSPATH . 'wp-admin/includes/theme.php');
            include_once(ABSPATH . 'wp-admin/includes/file.php');

            wp_update_plugins();  // Checks for available updates to plugins based on the latest versions hosted on WordPress.org.

            $outOfDatePlugins = get_site_transient('update_plugins');
            $outOfDatePluginNames = array_keys($outOfDatePlugins->response);
            $updated = [];

            // update the plugins
            foreach ($outOfDatePluginNames as $outOfDatePlugin) {

                $plugin_data = get_plugin_data(ABSPATH . 'wp-content/plugins/' . $outOfDatePlugin);

                $skin = new WP_Ajax_Upgrader_Skin();
                $result = false;
                $success = false;
                $activate = is_plugin_active($outOfDatePlugin);

                $upgrader = new Plugin_Upgrader($skin);
                $result = $upgrader->upgrade($outOfDatePlugin);

                if ($activate) activate_plugin($outOfDatePlugin, false, false, true);

                $log[$outOfDatePlugin] = $skin->get_upgrade_messages();

                if ($result) {
                    $updated[] = [
                        'name' => $plugin_data['Name'],
                        'description' => $plugin_data['Description'],
                        'oldversion' => $plugin_data['Version'],
                        'newversion' => $outOfDatePlugins->response[$outOfDatePlugin]->new_version
                    ];
                }
            }

            // Force refresh of plugin update information.
            wp_clean_plugins_cache();

            $this->disableWPDebugMode();

            wp_send_json_success(
                [
                    'ts' => time(),
                    'log' => $log,
                    'updated' => $updated
                ]
            );
            wp_die();
        } catch (Exception $e) {
            $this->disableWPDebugMode();
            wp_send_json_error(
                [
                    'ts' => time(),
                    'message' => $e->getMessage()
                ]
            );
            wp_die();
        }
    }
}
