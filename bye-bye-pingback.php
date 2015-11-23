<?php
/*
Plugin Name:        BYE BYE Pingback
Plugin URI:         http://wordpress.stackexchange.com/posts/158992/
Description:        Banishment of wordpress pingback
Version:            1.0.0
Author:             bryanwillis
Author URI:         https://github.com/bryanwillis/
*/

defined( 'WPINC' ) or die;

register_activation_hook( __FILE__, 'block_pingback_xmlrcp_activation_hook' );
function block_pingback_xmlrcp_activation_hook() {
    add_filter('mod_rewrite_rules', 'my_htaccess_contents');
}

// Htaccess add on plugin activation hook
function xmlrcp_blocked_htaccess( $rules ) {
$my_content = <<<EOD
\n # BEGIN Pingback Block
# Block xmlrpc.php access
<files xmlrpc.php>
    order allow,deny
    deny from all
</files>
# END Pingback Block\n
EOD;
    return $my_content . $rules;
}



// Remove Pingback html from <head>
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
