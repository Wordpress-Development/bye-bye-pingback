<?php
/*
Plugin Name:        BYE BYE Pingback
Plugin URI:         https://github.com/Wordpress-Development/bye-bye-pingback/
Description:        Banishment of wordpress pingback
Version:            1.0.0
Author:             bryanwillis
Author URI:         https://github.com/bryanwillis/
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Htaccess directive block xmlrcp for extra security.
 * Here are some rewrite examples:
 *   404 - RewriteRule xmlrpc\.php$ - [R=404,L]
 *   301 - RewriteRule ^xmlrpc\.php$ index.php [R=301]
 * If you want custom 404 make sure your server is finding it by also adding this 'ErrorDocument 404 /index.php?error=404' or 'ErrorDocument 404 /wordpress/index.php?error=404' for sites in subdirectory.
 */ 
add_filter('mod_rewrite_rules', 'noxmlrpc_mod_rewrite_rules'); // should we put this inside wp_loaded or activation hook
function noxmlrpc_mod_rewrite_rules($rules) {
  $insert = "RewriteRule xmlrpc\.php$ - [F,L]";
  $rules = preg_replace('!RewriteRule!', "$insert\n\nRewriteRule", $rules, 1);
  return $rules;
}

register_activation_hook(__FILE__, 'noxmlrpc_htaccess_activate');
function noxmlrpc_htaccess_activate() {
  flush_rewrite_rules(true);
}

register_deactivation_hook(__FILE__, 'noxmlrpc_htaccess_deactivate');
function noxmlrpc_htaccess_deactivate() {
  remove_filter('mod_rewrite_rules', 'noxmlrpc_mod_rewrite_rules');
  flush_rewrite_rules(true);
}


add_action('wp', function(){
    // Remove rsd_link from filters- link rel="EditURI"
    remove_action('wp_head', 'rsd_link');
    // Windows Live Writer
    // <link rel="wlwmanifest" type="application/wlwmanifest+xml" href="/wp-includes/wlwmanifest.xml" /> 
    remove_action('wp_head', 'wlwmanifest_link');
}, 9);


// Remove pingback from head (link rel="pingback")
if (!is_admin()) {      
    function link_rel_buffer_callback($buffer) {
        $buffer = preg_replace('/(<link.*?rel=("|\')pingback("|\').*?href=("|\')(.*?)("|\')(.*?)?\/?>|<link.*?href=("|\')(.*?)("|\').*?rel=("|\')pingback("|\')(.*?)?\/?>)/i', '', $buffer);
                return $buffer;
    }
    function link_rel_buffer_start() {
        ob_start("link_rel_buffer_callback");
    }
    function link_rel_buffer_end() {
        ob_flush();
    }
    add_action('template_redirect', 'link_rel_buffer_start', -1);
    add_action('get_header', 'link_rel_buffer_start');
    add_action('wp_head', 'link_rel_buffer_end', 999);
}


// Hijack pingback_url for get_bloginfo (<link rel="pingback" />).
add_filter('bloginfo_url', function($output, $property){
    return ($property == 'pingback_url') ? null : $output;
}, 11, 2);


add_filter( 'xmlrpc_enabled', '__return_false' ); // https://github.com/WordPress/WordPress/blob/77e365efbf2e499e2ed11d29c101ea466cf1ceed/wp-includes/class-wp-xmlrpc-server.php#L255
add_filter( 'pre_update_option_enable_xmlrpc', '__return_false' );
add_filter( 'pre_option_enable_xmlrpc', '__return_zero' ); // https://github.com/WordPress/WordPress/blob/77e365efbf2e499e2ed11d29c101ea466cf1ceed/wp-includes/class-wp-xmlrpc-server.php#L241
add_filter( 'pings_open', '__return_false', 10, 2 );



add_filter( 'rewrite_rules_array', function( $rules ) {
	foreach( $rules as $rule => $rewrite ) {
		if( preg_match( '/trackback\/\?\$$/i', $rule ) ) {
			unset( $rules[$rule] );
		}
	}
	return $rules;
});


// Disable X-Pingback HTTP Header.
add_filter('wp_headers', function($headers, $wp_query){
    if(isset($headers['X-Pingback'])){
        unset($headers['X-Pingback']);
    }
    return $headers;
}, 11, 2);


/*----------------------------------------------------------------------------*
 * XML-RCP Methods
 *----------------------------------------------------------------------------*/
 
// https://github.com/WordPress/WordPress/blob/77e365efbf2e499e2ed11d29c101ea466cf1ceed/wp-includes/class-wp-xmlrpc-server.php#L170
add_filter( 'xmlrpc_methods', function($methods){
    unset( $methods['pingback.ping'] );
    unset( $methods['pingback.extensions.getPingbacks'] );
    unset( $methods['wp.getUsersBlogs'] ); // Block brute force discovery of existing users
    unset( $methods['system.multicall'] );
    unset( $methods['system.listMethods'] );
    unset( $methods['system.getCapabilities'] );
    return $methods;
});


// Just disable pingback.ping functionality while leaving XMLRPC intact?
add_action('xmlrpc_call', function($method){
    if($method != 'pingback.ping') return;
    wp_die(
        'This site does not have pingback.',
        'Pingback not Enabled!',
        array('response' => 403)
    );
});
