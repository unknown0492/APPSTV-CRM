<?php
/*
 * Favicon of every page (Scodezy base; dynamic since gen3 1.0.63) - Administrator > Website Configuration > Branding.
 * Uses the 'site_favicon' setting; without it, the favicon files of assets/scodezy/media/site/favicon. A version tag
 * (file time, or a hash for an outside address) makes browsers fetch a changed icon instead of their cached copy.
 * The tags are built by siteFaviconTags() (library/site_settings_functions.php).
 */

$site_favicon = trim( (string) getConfigurationValue( 'site_favicon', '' ) );   // Website Configuration > Branding
echo "<!-- Favicon - Begin -->\n";
if( $site_favicon !== '' ){
    echo siteFaviconTags( $site_favicon, array( 'shortcut icon', 'icon', 'apple-touch-icon' ) );
}
else{
    // Not set: the website's own favicon files (.ico for old browsers, .png / .svg for the rest)
    $site_favicon_dir = 'assets/scodezy/media/site/favicon/';
    if( is_file( BASE_PATH . '/' . $site_favicon_dir . 'favicon.ico' ) ){ echo siteFaviconTags( $site_favicon_dir . 'favicon.ico', array( 'shortcut icon' ) ); }
    foreach( array( 'favicon.png', 'favicon.svg' ) as $site_favicon_file ){
        if( is_file( BASE_PATH . '/' . $site_favicon_dir . $site_favicon_file ) ){
            echo siteFaviconTags( $site_favicon_dir . $site_favicon_file, array( 'icon', 'apple-touch-icon' ) );
            break;
        }
    }
}
echo "<!-- Favicon - End -->\n";
?>
