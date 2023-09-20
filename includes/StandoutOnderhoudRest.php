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
        // add_filter( 'rest_request_before_callbacks', array($this,'authorizeApiRequests'), 10, 3 );

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
            return new WP_Error( 'authorization', 'Unauthorized access.', array( 'status' => 401 ) );
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

        $this->user = wp_signon(['user_login' => $credentials[0], 'user_password' => $credentials[1]]);

    }

}