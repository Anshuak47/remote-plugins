<?php
/*
 * Plugin Name: 		Remote Plugins
 * Plugin URI: 			https://anshukushwaha.com
 * Description: 		Provides REST API endpoints different plugin operation functionalities
 * Version:				1.0.0
 * Author: 				Anshu Kushwaha
 * Author URI: 			https://anshukushwaha.com
 * Text Domain: 		rp
 * Requires at least: 	6.0
 * Requires PHP:      	7.4
 * 
 */


// Prevent direct access
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register custom REST endpoints
 * 
 */

add_action('rest_api_init', 'rp_list_plugin_endpoint');

add_action('rest_api_init', 'rp_activate_plugin_endpoint');


define('MY_TOKEN', 'WxScDzJOYXEAcCmDSSDuieM48WpTeZGC');

function rp_list_plugin_endpoint(){

	// Register route for list plugins
	register_rest_route('kd/v1', '/listplugins', array(
        'methods' => 'GET',
        'callback' => 'rp_remote_plugins_list',
        'permission_callback' => 'rp_verify_token',
        
    ));

	register_rest_route('kd/v1', '/deactivate', array(
        'methods' 	=> 'POST',
        'callback' 	=> 'rp_remote_plugins_deactivate',
 		'permission_callback' =>'rp_verify_token',
        'args' => array(
            'plugin' => array(
                'required' => true,
             
            ),
        ),
    ));

   

  
}

/**
 *  Register Endpoint to activate plugin
 */

function rp_activate_plugin_endpoint(){

	 register_rest_route('kd/v1', '/activate', array(
        'methods' 	=> 'POST',
        'callback' 	=> 'rp_remote_plugins_activate',
 		'permission_callback' =>'rp_verify_token',
        'args' => array(
            'plugin' => array(
                'required' => true,
             
            ),
        ),
    ));
}


function rp_verify_token( $request ){

    
   $auth_header = $request->get_header('authorization');
    
    if ( empty($auth_header) || !preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) {
        return false;
    }

    $token = $matches[1];

    return hash_equals( MY_TOKEN, $token);

}

/**
 * Call-back to list plugin of the site
 * @return REST route
 */

function rp_remote_plugins_list(){

	// Load the plugins.php core file 
	if ( ! function_exists( 'get_plugins' ) ) {

        require_once ABSPATH . 'wp-admin/includes/plugin.php';

    }

    // Get the plugins
    $plugins = get_plugins();

    if( !empty( $plugins ) ){
    	
    	$single_plugin = array();

	    foreach( $plugins as $plugin_key => $plugin_description ){

	    	$single_plugin[] = array(
	    		'path'			=> $plugin_key,
	    		'name'        	=> $plugin_description['Name'],
	            'description' 	=> $plugin_description['Description'],
	            'version'     	=> $plugin_description['Version'],
	            'status'      	=> is_plugin_active( $plugin_key ) ? 'Active' : 'Inactive',
	    	); 

	    }

    }else{
    	$single_plugin[] = array(

    		'No plugins found'
    	);

    }

    // return REST Route
	return new WP_REST_Response( $plugins, 200 );
  
}

/**
 * Function callback of REST route to deactivate plugin
 */

function rp_remote_plugins_deactivate( WP_REST_Request $request ){

	// Load the plugins.php core file 
	if ( ! function_exists( 'get_plugins' ) ) {

        require_once ABSPATH . 'wp-admin/includes/plugin.php';

    }

    // Get the value of the parameter given to the endpoint 
    $plugin_name = $request->get_param('plugin');

    
    // Check if 'name' parameter is not provided
  	if( !empty( $plugin_name ) ){

    	$plugin_path = rp_get_plugin_path( $plugin_name );
	 	
	}else{
    	return new WP_REST_Response( array( 'error' => 'Plugin path not found' ), 400 );

	}

	if( !empty( $plugin_path ) ){

    	deactivate_plugins( $plugin_path );

    }

    if ( is_plugin_active( $plugin_path ) ) {

    	/**
    	 * If plugin is still active then return error message
    	 * @return WP_Rest_Response
    	 */
    	return new WP_REST_Response( array( 'error' => 'Plugin deactivation failed' ), 500 );
    }

    /**
     * Return the successfull message if plugin is deactivated
     * @return WP_Rest_Response
     */
    return new WP_REST_Response( array( 'success' => 'Plugin deactivated successfully.' ), 200 );

}


/**
 * Activate plugin using the REST endpoint
 */

function rp_remote_plugins_activate( WP_REST_Request $request ){

	// Load the plugins.php core file 
	if ( ! function_exists( 'get_plugins' ) ) {

        require_once ABSPATH . 'wp-admin/includes/plugin.php';

    }


    // Get the value of the parameter given to the endpoint 
    $plugin_name = $request->get_param('plugin');

    
    // Check if 'name' parameter is not provided
  	if( !empty( $plugin_name ) ){

    	$plugin_path = rp_get_plugin_path( $plugin_name );
	 	
	}else{
    	return new WP_REST_Response( array( 'error' => 'Plugin path not found' ), 400 );

	}


    if ( is_plugin_active( $plugin_path ) ) {

    	/**
    	 * If plugin is still active then return error message
    	 * @return WP_Rest_Response
    	 */
    	return new WP_REST_Response( array( 'error' => 'Plugin already active' ), 200 );
    }

	// Activate the plugin

	if( !empty( $plugin_path ) ){

    	activate_plugins( $plugin_path );

    }

    /**
     * Return the successfull message if plugin is deactivated
     * @return WP_Rest_Response
     */
    return new WP_REST_Response( array( 'success' => 'Plugin activated successfully.' ), 200 );

}	

// Get plugin path
function rp_get_plugin_path( $query ){

	if ( ! function_exists( 'get_plugins' ) ) {

        require_once ABSPATH . 'wp-admin/includes/plugin.php';

    }

	$plugins_list = get_plugins();

	$plugin_result = array();

    foreach( $plugins_list as $plugin_key => $plugin_description ){


    	if( stripos( $plugin_description['Name'], $query ) !== false ){

    		// Get the found plugin name and path
	    	$plugin_result[] = array(
	    		'path'			=> $plugin_key,
	    		'name'        	=> $plugin_description['Name'],
	    	);

    	}

    }

    $plugin_path = '';
    if( !empty( $plugin_result ) && count( $plugin_result ) == 1  ){

    	$plugin_path = $plugin_result[0]['path'];
    	
	}

	return $plugin_path;
}

add_action('wp_footer', function(){

	if ( ! function_exists( 'get_plugins' ) ) {

        require_once ABSPATH . 'wp-admin/includes/plugin.php';

    }

	$plugins_list = get_plugins();

	$plugin_path = '';

	$query = 'contact-form-7';
    foreach( $plugins_list as $plugin_key => $plugin_description ){

    	// Get the found plugin name and path
    		
	    if( $plugin_description['TextDomain'] === $query ){

	    	$plugin_path = $plugin_key;
	    	break;
	    }
    }

    if( !empty( $plugin_path ) )
    	
		echo $plugin_path;

});