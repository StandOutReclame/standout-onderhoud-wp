<?php
 
class SiteCheckEndpoints {

    use \includes\traits\DebugTrait;

    private $log = [];
    private $urls = [];

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
            register_rest_route( 'standout-onderhoud/v1', 'startcheck', array(
                'methods' => 'GET',
                'callback' => array($this, 'startCheck'),
            ) );
        } );
        add_action( 'rest_api_init', function () {
            register_rest_route( 'standout-onderhoud/v1', 'checksite', array(
                'methods' => 'GET',
                'callback' => array($this, 'checkSite'),
            ) );
        } );
    }

    public function startCheck(WP_REST_Request $request) {

        try {
            // first check if the debug log is enabled
            $result = $this->enableWPDebugMode();
            if(!$result) {
                wp_send_json_error( [
                    'ts' => time(),
                    'message' => $this->log
                ] );
                wp_die();
            }
            
            $this->log[] = 'Debug mode enabled. Starting with visiting random URL\'s from the sitemap.xml';
            
            wp_send_json_success( [
                'ts' => time(),
                'message' => implode('<br/>', $this->log)
            ] );
            wp_die();

            // return response
        } catch(Exception $e) {
            wp_send_json_error( [
                'ts' => time(),
                'error' => $this->log
            ] );
            wp_die();
        }
        

    }

    public function checkSite(WP_REST_Request $request) {

        try {

            // Get the sitemap and visit some random urls
            $this->visitRandomUrls();

            // check debug log
            $this->checkDebugLog();

            // disable debug mode
            $this->disableWPDebugMode();


            $visited = 'Visited these URLS:<br/>';
            foreach($this->urls as $url) {
                $visited .= '<a href="'.$url.'" target="_blank">'.$url.'</a><br/>';
            }

            $this->log[] = $visited;
            $this->log[] = '<b>Done with checking site.</b><br/>';

            wp_send_json_success( [
                'ts' => time(),
                'log' => $this->log
            ] );
            wp_die();

            // return response
        } catch(Exception $e) {

            $this->disableWPDebugMode();

            wp_send_json_error( [
                'ts' => time(),
                'error' => $this->log
            ] );
            wp_die();
        }

    }

    /**
     * Check the contents of the debug log
     */
    private function checkDebugLog() {

        $filepath = ABSPATH . 'wp-content/debug.log';
        if(!file_exists($filepath)) {
            $this->log[] = 'Nothing found in the debug.log';
            return true;
        }

        $content = file_get_contents($filepath);
        if(empty($content)) {
            $this->log[] = 'Nothing found in the debug.log<br/>';
            return true;
        }

        $this->log[] = '<p><b>Errors found. Please check log: </b></br>'.nl2br($content).'</p>';

        return true;
    }

    /**
     * Visit some random URL's of the site via cURL
     */
    private function visitRandomUrls() {

        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

        // first check if yoast is enabled
        if(!is_plugin_active('wordpress-seo/wp-seo.php')) {
            $this->log[] = '<b>Yoast SEO plugin not activated. Can\'t retrieve sitemap. Please manually install Yoast SEO and enable the sitemap first.</b>';
            return;
        }

        // next check if the sitemap exists
        $url = get_site_url() . '/sitemap_index.xml';
        $file_headers = @get_headers($url);

        if(!$file_headers || $file_headers[0] == 'HTTP/1.1 404 Not Found') {
            $this->log[] = '<b>Yoast SEO plugin is activated, however the sitemap does not exist. Please manually enable the sitemap first.</b>';
            return;
        }

        // next get some random url's
        $urls = $this->getRandomUrlsFromSitemap();

        foreach($urls as $url) {
            // create a new cURL resource
            $ch = curl_init();

            // set URL and other appropriate options
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            // grab URL and pass it to the browser
            curl_exec($ch);

            // close cURL resource, and free up system resources
            curl_close($ch);
        }

        $this->urls = $urls;

        return true;
    }

    /**
     * Get some random urls from the sitemap
     */
    private function getRandomUrlsFromSitemap() {

        // first get the main sitemap archive
        $sitemap = file_get_contents(get_site_url() . '/sitemap_index.xml');
        $xml = simplexml_load_string($sitemap);

        $sitemap = $xml->sitemap;

        $urls = [];

        foreach($sitemap as $post_type) {
            
            // get some random urls from the post type sitemap
            $post_type_sitemap = file_get_contents($post_type->loc);
            $xml = simplexml_load_string($post_type_sitemap);
            $post_type_urls = [];
            foreach($xml->url as $url) {
                $post_type_urls[] = (string) $url->loc;
            }

            $max = count($post_type_urls) > 2 ? 2 : count($post_type_urls);

            shuffle($post_type_urls);
            $post_type_urls = array_slice($post_type_urls, 0, $max);
            $urls = array_merge($urls, $post_type_urls);
        }

        $urls = array_unique($urls);
        
        return $urls;

    }

}