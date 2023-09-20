<?php

class StandoutOnderhoudUpdater {

    public $plugin_slug;
    public $version;
    public $cache_key;
    public $cache_allowed;

    public function __construct()
    {
        $this->plugin_slug = plugin_basename( __DIR__ );
        $this->version = '1.0';
        $this->cache_key = 'misha_custom_upd';
        $this->cache_allowed = false;

        add_filter( 'plugins_api', array( $this, 'info' ), 20, 3 );
        add_filter( 'site_transient_update_plugins', array( $this, 'update' ) );
        add_action( 'upgrader_process_complete', array( $this, 'purge' ), 10, 2 );
        
        
    }

}