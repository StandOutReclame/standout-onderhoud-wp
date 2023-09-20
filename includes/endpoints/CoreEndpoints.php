<?php
 
class CoreEndpoints {

    use \includes\traits\DebugTrait;

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
        add_action( 'rest_api_init', function () {
            register_rest_route( 'standout-onderhoud/v1', 'core', array(
                'methods' => 'GET',
                'callback' => array($this, 'getCore'),
            ) );
            register_rest_route( 'standout-onderhoud/v1', 'core/update', array(
                'methods' => 'GET',
                'callback' => array($this, 'doUpdateCore'),
            ) );
        } );
    }

    /**
	 * Captures core update results from hook, only way to get them
	 *
	 * @param $results
	 */
	public function capture_core_update_results( $results ) {
		$this->update_results = $results;
	}

    /**
     * Get the core 
     */
    public function getCore(WP_REST_Request $request)
    {

        if ( ! function_exists( 'get_plugins' ) || ! function_exists('get_core_updates') ) {
            require_once ABSPATH . 'wp-admin/includes/update.php';
        }

         // Clear existing caches.
         wp_clean_update_cache();

         wp_version_check();  // Check for core updates.
         wp_update_themes();  // Check for theme updates.
         wp_update_plugins(); // Check for plugin updates.

        $coreUpdates = get_core_updates();
        $coreNeedsUpdate = false;
        if($coreUpdates) {
            $coreUpdates = $coreUpdates[0];
            if($coreUpdates->response !== 'latest') {
                $coreNeedsUpdate = true;
            }
        }

        wp_send_json_success(
            [
                'needsUpdate' => $coreNeedsUpdate,
                'core' => $coreUpdates
            ]
        );
        wp_die();
    }

    /**
     * Handler for updating the core
     */
    public function doUpdateCore() {

        $this->enableWPDebugMode();

        $this->updateCore();

        wp_send_json_success(
            [
                'log' => $this->log,
                'updated' => $this->updated
            ]
        );
        wp_die();

    }

    /**
     * Update the core
     */
    private function updateCore() {

        global $wp_version, $wpdb;

        try {

            include_once( ABSPATH . 'wp-admin/includes/update.php' );
            include_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );
            include_once( ABSPATH . 'wp-admin/includes/class-wp-automatic-updater.php' );
            include_once( ABSPATH . 'wp-admin/includes/file.php' );

            add_action( 'automatic_updates_complete', array( $this, 'capture_core_update_results' ) );

            add_filter( "auto_update_core", '__return_true', 99999 ); //temporarily allow core autoupdates
            add_filter( "allow_major_auto_core_updates", '__return_true', 99999 ); //temporarily allow core autoupdates
            add_filter( "allow_minor_auto_core_updates", '__return_true', 99999 ); //temporarily allow core autoupdates
            add_filter( "auto_update_core", '__return_true', 99999 ); //temporarily allow core autoupdates
            add_filter( "auto_update_theme", '__return_false', 99999 );
            add_filter( "auto_update_plugin", '__return_false', 99999 );

            $core_update = find_core_auto_update();
            $upgrader = new WP_Automatic_Updater;
            $result = $upgrader->update('core', $core_update);

            $this->logs[] = 'Updated core to version '. $result;
            $this->updated = $core_update;

            // Finally, process any new translations.
            $language_updates = wp_get_translation_updates();
            if ( $language_updates ) {
                foreach ( $language_updates as $update ) {
                    $upgrader->update( 'translation', $update );
                }

                // Clear existing caches.
                wp_clean_update_cache();

                wp_version_check();  // Check for core updates.
                wp_update_themes();  // Check for theme updates.
                wp_update_plugins(); // Check for plugin updates.
            }

            $this->disableWPDebugMode();


        } catch(Exception $e) {

            $this->log[] = $e->getMessage();
            $this->disableWPDebugMode();

            return;
        }

    }
}