# bye-bye-pingback
Completely disable Pingback / XML RCP in Wordpress

Forbidden Rewrite:
`RewriteRule xmlrpc\.php$ - [F,L]`


Custom 404 Rewrite:
`ErrorDocument 404 /index.php?error=404`
`RewriteRule xmlrpc\.php$ - [R=404,L]`

If your wordpress is in a subdirectory: ErrorDocument 404 /wordpress/index.php?error=404

301 Redirect to homepage:
`RewriteRule ^xmlrpc\.php$ index.php [R=301]`

