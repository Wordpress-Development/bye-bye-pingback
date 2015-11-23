<?php
/*
Plugin Name:        BYE BYE Pingback
Plugin URI:         http://wordpress.stackexchange.com/posts/158992/
Description:        Banishment of wordpress pingback
Version:            1.0.0
Author:             bryanwillis
Author URI:         https://github.com/bryanwillis/
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

register_activation_hook( __FILE__, 'block_pingback_xmlrcp_activation_hook' );
function block_pingback_xmlrcp_activation_hook() {
   
}

/**
 * Htaccess directive block xmlrcp for extra security.
 * This runs when permalink structure is updated. Delete directive manually for added security on accidental plugin deactivation.
 */
function xmlrcp_blocked_htaccess( $rules ) {
$xmlrcp_rule = <<<EOD
\n# BEGIN Pingback Block
# Block xmlrpc.php access
<files xmlrpc.php>
    order allow,deny
    deny from all
</files>
# END Pingback Block\n
EOD;
    return $xmlrcp_rule . $rules;
}
add_filter('mod_rewrite_rules', 'xmlrcp_blocked_htaccess');


// Remove rsd_link from filters (<link rel="EditURI" />).
add_action('wp', function(){
    remove_action('wp_head', 'rsd_link');
}, 9);


// Remove pingback html from frontend
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


// Disable xmlrcp/pingback
add_filter( 'xmlrpc_enabled', '__return_false' );
add_filter( 'pre_update_option_enable_xmlrpc', '__return_false' );
add_filter( 'pre_option_enable_xmlrpc', '__return_zero' );

function remove_pingback_methods( $methods ) {
   unset( $methods['pingback.ping'] );
   unset( $methods['pingback.extensions.getPingbacks'] );
   unset( $methods['wp.getUsersBlogs'] );
   return $methods;
}
add_filter( 'xmlrpc_methods', 'remove_pingback_methods' );


// Stop pingback headers from being sent
function filter_wp_headers_unset_pingback( $headers ) {
    if( isset( $headers['X-Pingback'] ) ) {
        unset( $headers['X-Pingback'] );
    }
    return $headers;
}
add_filter( 'wp_headers', 'filter_wp_headers_unset_pingback', 10, 1 );


//Block access to pingback page
function xmlrpc_pingbacks_not_allowed_redirect( $action ) {
    if( 'pingback.ping' === $action ) {
        wp_die( 
            'Pingbacks are not supported', 
            'Not Allowed!', 
            array( 'response' => 403 )
        );
    }
}
add_action( 'xmlrpc_call', 'xmlrpc_pingbacks_not_allowed_redirect' );
