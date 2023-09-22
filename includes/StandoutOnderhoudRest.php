<?php

$endpoints = glob( __DIR__.'/endpoints/*.php');

foreach($endpoints as $endpoint) {
    require($endpoint);
}

class StandoutOnderhoudRest {

    public $routes;
    private $user;

    public function __construct()
    {
        $this->register_endpoints();
        // auth middleware routes
        $this->routes = [
            '/standout-onderhoud/v1/core',
            '/standout-onderhoud/v1/plugins',
            '/standout-onderhoud/v1/check'
        ];

        // add basic auth to requests
        add_filter( 'rest_request_before_callbacks', array($this,'authorizeApiRequests'), 10, 3 );

    }

    /**
     * Register the endpoints
     * @return void
     */
    public function register_endpoints()
    {
        // register plugins endpoints
        $plugins = new PluginsEndpoints;
        $core    = new CoreEndpoints;
        $check   = new SiteCheckEndpoints;
    }

    /**
     * Check credentials
     * @param $response
     * @param $handler
     * @param WP_REST_Request $request
     * @return mixed|WP_Error
     */
    public function authorizeApiRequests($response, $handler, WP_REST_Request $request)
    {

        if(!in_array( $request->get_route(), $this->routes )) return $response;

        if ( ! $request->get_header( 'authorization' ) ) {
            return new WP_Error( 'authorization', 'Unauthorized access.', array( 'status' => 403 ) );
        }

        // sign user in
        $this->signUser($request->get_header('authorization'));
        
        if(is_wp_error($this->user)) {
            return new WP_Error( 'forbidden', 'Access forbidden.', array( 'status' => 403 ) );
        }

        // check for admin role
        if (!in_array( 'administrator', $this->user->roles)) {
            return new WP_Error( 'forbidden', 'Access forbidden.', array( 'status' => 403 ) );
        }

        return $response;

    }

    /**
     * Signon user
     * @param $basicAuth
     * @return void|WP_Error
     */
    private function signUser($basicAuth)
    {
        $basicAuth = explode(' ', $basicAuth);
        $credentials = base64_decode($basicAuth[1]);
        $credentials = explode(':',$credentials);

        $this->temp_update_google_auth_plugin($credentials[0], false);

        $this->user = wp_signon(['user_login' => $credentials[0], 'user_password' => $credentials[1]]);

        $this->temp_update_google_auth_plugin($credentials[0], true);

    }

    /**
     * Disable Google auth check for onderhoud plugin
     */
    private function temp_update_google_auth_plugin($wp_user, $enable)
    {

        $user = get_user_by('login', $wp_user);
        if(!$user) return;

        $check_value = 'enabled';
        $update_value = 'disabled';

        if($enable) {
            $check_value = 'disabled';
            $update_value = 'enabled';
        }

        if(get_user_option( 'googleauthenticator_enabled', $user->ID ) == $check_value) {
            update_user_option($user->ID, 'googleauthenticator_enabled', $update_value);
        }

    }

}