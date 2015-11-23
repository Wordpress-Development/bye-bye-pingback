# BYE-BYE PINGBACK!!!

Banish WordPress pingback/XML-RPC once and for all....

    <link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>" />


The Wordpress XML-RCP is extremely vulnerable  to attacks. Here's an example demonstrating how easily it can attack a wordpress based website:
https://github.com/zendoctor/wpbrute-rpc

This plugin aims to block every possible exploit involving pingbacks/trackbacks by disabling it all together and blocking access to the file.

However, be aware by disabling XML-RPC, you may risk breaking some popular plugins. If you have any of the plugins listed below, you may want to do a bit more research:

## The following plugins will no longer work when using this one.

 - WordPress Mobile App 
 - JetPack LibSyn (for podcasts) 
 - BuddyPress 
 - Windows Live Writer 
 - IFTTT

https://blog.sucuri.net/2015/10/brute-force-amplification-attacks-against-wordpress-xmlrpc.html


    UPDATE wp_posts SET ping_status="closed";



    <Files xmlrpc.php>
    Order Deny,Allow
    Deny from all
    </Files> 

    <IfModule mod_alias.c>
    RedirectMatch 403 (?i)/xmlrpc.php
    </IfModule>

    RewriteRule ^xmlrpc.php$ "http://0.0.0.0/" [R=301,L]


