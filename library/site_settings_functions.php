<?php
/*
 * Site settings used by the pages (Scodezy base feature, gen3 1.0.58) - edited on Administrator > Website Configuration.
 *
 * Every value lives in the configurations table and has a default equal to the text the pages showed before, so a
 * missing row (e.g. before the migration ran) changes nothing on screen. Output helpers escape what they print.
 */

/**
 * Defaults of the sign-in pages: page => [ tab title, heading, sub-heading ]
 */
define( 'SITE_SIGNIN_PAGES', array(
    'login'  => array( 'label' => 'Login',           'title' => 'Sign In',         'heading' => 'Sign In',          'subheading' => 'Account Management Panel' ),
    'signup' => array( 'label' => 'Sign Up',         'title' => 'Sign Up',         'heading' => 'Sign Up',          'subheading' => 'Register to access the Administrator Panel' ),
    'forgot' => array( 'label' => 'Forgot Password', 'title' => 'Forgot Password', 'heading' => 'Forgot Password ?', 'subheading' => 'Enter your User ID to reset your password.' ),
    'reset'  => array( 'label' => 'Password Reset',  'title' => 'Password Reset',  'heading' => 'Password Reset',   'subheading' => 'Set a new password of your choice' )
) );

define( 'SITE_DEFAULT_FOOTER_LINKS', '[{"label":"About","url":"#"},{"label":"Support","url":"#"}]' );   // Footer links before 1.0.58
define( 'SITE_DEFAULT_TAB_TITLE_FORMAT', '{page} | {site}' );                                          // Admin panel browser tab
define( 'SITE_DEFAULT_DASHBOARD_HEADING', 'Administrator Panel' );                                     // Toolbar title of the dashboard
define( 'SITE_DEFAULT_MAINTENANCE_MESSAGE', 'We are making some improvements and will be back shortly.' );

/**
 * What can appear next to the profile picture (1.0.61): option => label
 */
define( 'SITE_DISPLAY_NAME_OPTIONS', array(
    'full_name'  => 'First and last name',
    'fname'      => 'First name',
    'lname'      => 'Last name',
    'nickname'   => 'Nickname',
    'email'      => 'Email',
    'user_id'    => 'User ID'
) );
define( 'SITE_AVATAR_THEME_DIR', 'assets/media/svg/avatars' );                                        // Cartoon avatars of the theme
define( 'SITE_AVATAR_UPLOAD_DIR', 'data/uploads/avatars' );                                           // Own pictures (git-ignored)
define( 'SITE_META_AVATAR', '_avatar' );                                                              // user_meta: initials | theme:<file> | upload:<path>
define( 'SITE_META_DISPLAY_NAME', '_display_name_field' );                                            // user_meta: a SITE_DISPLAY_NAME_OPTIONS key
define( 'SITE_META_TOKENS_VALID_AFTER', '_tokens_valid_after' );                                      // user_meta: sign-ins older than this (ms) are refused

/**
 * @description A configurations value, or the default when the row does not exist
 * @param {string} $key - config_key
 * @param {string} $default - Fallback
 * @returns {string}
 */
function siteSetting( $key, $default = '' ){
    $value = getConfigurationValue( escape_string( $key ), null );
    return ( $value === null ) ? (string) $default : (string) $value;
}

/**
 * @description Texts and logo of a sign-in page
 * @param {string} $page - login, signup, forgot or reset
 * @returns {array} title, heading, subheading, logo (the page's own logo, else the common login logo)
 */
function siteSigninPage( $page ){
    $d = isset( SITE_SIGNIN_PAGES[ $page ] ) ? SITE_SIGNIN_PAGES[ $page ] : SITE_SIGNIN_PAGES[ 'login' ];
    $logo = siteSetting( 'signin_' . $page . '_logo', '' );
    return array(
        'title'      => siteSetting( 'signin_' . $page . '_title', $d[ 'title' ] ),
        'heading'    => siteSetting( 'signin_' . $page . '_heading', $d[ 'heading' ] ),
        'subheading' => siteSetting( 'signin_' . $page . '_subheading', $d[ 'subheading' ] ),
        'logo'       => ( $logo !== '' ) ? $logo : siteSetting( 'login_page_logo', '' )
    );
}

/**
 * @description CSS typed for the footer copyright / links, made safe to print inside a <style> element
 * @returns {string}
 */
function siteFooterCss(){
    return str_replace( '<', '', siteSetting( 'footer_custom_css', '' ) );   // "<" is never needed in CSS; it could close the style element
}

/**
 * @description Admin panel footer links
 * @returns {array} [ { label, url } ] (empty = no links shown)
 */
function siteFooterLinks(){
    $links = json_decode( siteSetting( 'footer_links', SITE_DEFAULT_FOOTER_LINKS ), true );
    $clean = array();
    if( is_array( $links ) ){
        foreach( $links as $link ){
            if( is_array( $link ) && isset( $link[ 'label' ], $link[ 'url' ] ) && trim( $link[ 'label' ] ) !== '' ){
                $clean[] = array( 'label' => (string) $link[ 'label' ], 'url' => (string) $link[ 'url' ] );
            }
        }
    }
    return $clean;
}

/**
 * @description Browser tab title of an admin panel page
 * @param {string} $page_title - Title of the open page ('' on the dashboard)
 * @returns {string} Not escaped
 */
function siteAdminTabTitle( $page_title ){
    $site = getSiteConfig();
    $page = ( $page_title !== '' ) ? $page_title : siteDashboardHeading();
    $format = siteSetting( 'admin_tab_title_format', SITE_DEFAULT_TAB_TITLE_FORMAT );
    return str_replace( array( '{page}', '{site}' ), array( $page, (string) $site->site_name ), $format );
}

/**
 * @description Toolbar title of the dashboard
 * @returns {string} Not escaped
 */
function siteDashboardHeading(){
    return siteSetting( 'dashboard_heading', SITE_DEFAULT_DASHBOARD_HEADING );
}

/**
 * @description Whether maintenance mode is on
 * @returns {bool}
 */
function siteMaintenanceOn(){
    return siteSetting( 'maintenance_mode', '0' ) === '1';
}

/**
 * @description Text shown while maintenance mode is on
 * @returns {string} Not escaped
 */
function siteMaintenanceMessage(){
    $message = trim( siteSetting( 'maintenance_message', '' ) );
    return ( $message !== '' ) ? $message : SITE_DEFAULT_MAINTENANCE_MESSAGE;
}

/**
 * @description Whether a role may work during maintenance (it can open Website Configuration, i.e. Administrators)
 * @param {string|int} $role_id - roles.role_id
 * @returns {bool}
 */
function siteRoleBypassesMaintenance( $role_id ){
    $e_role_id = escape_string( (string) $role_id );
    $result_set = selectQuery( "SELECT rf.role_id FROM functionalities f, roles_functionalities rf
                                WHERE f.functionality_name = 'website_configuration' AND f.functionality_id = rf.functionality_id AND rf.role_id = '$e_role_id'" );
    return $result_set && mysqli_num_rows( $result_set ) > 0;
}

// ── Sign-in page output ─────────────────────────────────────────

/**
 * @description Whether this website has the animated gradient files (data/custom) (1.0.62)
 * @returns {bool}
 */
function siteGradientAvailable(){
    return is_file( BASE_PATH . '/data/custom/js/gradient-mesh.js' ) && is_file( BASE_PATH . '/data/custom/css/gradient-mesh.css' );
}

/**
 * @description Background of the sign-in pages: gradient, color, image, or none (the page's built-in background).
 *              Websites without the gradient files default to none (1.0.62)
 * @returns {string}
 */
function siteSigninBackgroundType(){
    $type = siteSetting( 'signin_background_type', siteGradientAvailable() ? 'gradient' : 'none' );
    if( $type === 'gradient' && !siteGradientAvailable() ){ $type = 'none'; }
    return in_array( $type, array( 'gradient', 'color', 'image', 'none' ), true ) ? $type : 'none';
}

/**
 * @description <head> part of the sign-in background + footer CSS
 * @returns {void}
 */
function siteSigninHead(){
    $type = siteSigninBackgroundType();
    if( $type === 'none' ){
        // The page's built-in background: nothing to add
    }
    else if( $type === 'gradient' ){
        echo '<link href="data/custom/css/gradient-mesh.css" rel="stylesheet" type="text/css" />' . "\n";
    }
    else{
        $color = siteSetting( 'signin_background_color', '#0b0b0c' );
        if( !preg_match( '/^#[0-9a-fA-F]{6}$/', $color ) ){ $color = '#0b0b0c'; }
        $image = siteSetting( 'signin_background_image', '' );
        $css = 'body.auth-bg{background-color:' . $color . ' !important;';
        if( $type === 'image' && $image !== '' ){
            $css .= 'background-image:url("' . htmlspecialchars( $image, ENT_QUOTES ) . '") !important;background-size:cover;background-position:center;background-attachment:fixed;';
        }
        echo '<style>' . $css . '}</style>' . "\n";
    }
    $footer_css = siteFooterCss();
    if( $footer_css !== '' ){ echo '<style>' . $footer_css . '</style>' . "\n"; }
}

/**
 * @description Start of <body>: the canvas of the animated gradient (only for the gradient background)
 * @returns {void}
 */
function siteSigninBodyTop(){
    if( siteSigninBackgroundType() === 'gradient' ){ echo '<canvas id="view"></canvas>' . "\n"; }
}

/**
 * @description End of <body>: the gradient animation script (only for the gradient background)
 * @returns {void}
 */
function siteSigninBodyBottom(){
    if( siteSigninBackgroundType() === 'gradient' ){ echo '<script src="data/custom/js/gradient-mesh.js"></script>' . "\n"; }
}

/**
 * @description Maintenance notice on the sign-in pages (only while maintenance mode is on)
 * @returns {void}
 */
function siteSigninMaintenanceNotice(){
    if( !siteMaintenanceOn() ){ return; }
    echo '<div class="alert alert-warning d-flex align-items-start text-start mt-6 mb-0 p-4">'
       . '<i class="fa-solid fa-person-digging fs-2 text-warning me-3 mt-1"></i>'
       . '<div><div class="fw-bold mb-1">Down for maintenance</div><div class="fs-7">' . htmlspecialchars( siteMaintenanceMessage() ) . ' Only administrators can sign in now.</div></div></div>';
}

/**
 * @description "Need help?" line with the support contact (only when one is set)
 * @returns {void}
 */
function siteSupportLine(){
    $email = siteSetting( 'support_email', '' );
    $phone = siteSetting( 'support_phone', '' );
    if( $email === '' && $phone === '' ){ return; }
    $parts = array();
    if( $email !== '' ){ $parts[] = '<a href="mailto:' . htmlspecialchars( $email ) . '" class="link-primary">' . htmlspecialchars( $email ) . '</a>'; }
    if( $phone !== '' ){ $parts[] = '<a href="tel:' . htmlspecialchars( preg_replace( '/[^0-9+]/', '', $phone ) ) . '" class="link-primary">' . htmlspecialchars( $phone ) . '</a>'; }
    echo '<div class="site-support text-gray-500 fw-semibold fs-7 mb-2">Need help? ' . implode( ' &middot; ', $parts ) . '</div>';
}

// ── Users: display name, avatar, sign-in validity (1.0.61) ─────

/**
 * @description The field a user shows next to the profile picture: their own choice (when users may choose), else the
 *              site default from Website Configuration
 * @param {string} $user_id - users.user_id
 * @returns {string} A SITE_DISPLAY_NAME_OPTIONS key
 */
function siteUserDisplayField( $user_id ){
    $default = siteSetting( 'profile_display_name_default', 'full_name' );
    if( !isset( SITE_DISPLAY_NAME_OPTIONS[ $default ] ) ){ $default = 'full_name'; }
    if( siteSetting( 'profile_display_name_user_choice', '1' ) === '1' ){
        $own = (string) getUserMetaValue( escape_string( $user_id ), SITE_META_DISPLAY_NAME );
        if( isset( SITE_DISPLAY_NAME_OPTIONS[ $own ] ) ){ return $own; }
    }
    return $default;
}

/**
 * @description Text shown next to the profile picture (falls back to the full name, then the User ID, when the
 *              chosen field is empty)
 * @param {array} $user - users row (user_id, fname, lname, nickname, email)
 * @returns {string} Not escaped
 */
function siteUserDisplayName( $user ){
    $field = siteUserDisplayField( $user[ 'user_id' ] );
    $full = trim( (string) $user[ 'fname' ] . ' ' . (string) $user[ 'lname' ] );
    $value = ( $field === 'full_name' ) ? $full : trim( (string) $user[ $field ] );
    if( $value === '' ){ $value = ( $full !== '' ) ? $full : (string) $user[ 'user_id' ]; }
    return $value;
}

/**
 * @description Initials of a user (first letters of first and last name, else of the User ID)
 * @param {array} $user - users row
 * @returns {string}
 */
function siteUserInitials( $user ){
    $f = trim( (string) $user[ 'fname' ] );
    $l = trim( (string) $user[ 'lname' ] );
    $initials = ( $f !== '' ? mb_substr( $f, 0, 1 ) : '' ) . ( $l !== '' ? mb_substr( $l, 0, 1 ) : '' );
    if( $initials === '' ){ $initials = mb_substr( (string) $user[ 'user_id' ], 0, 2 ); }
    return mb_strtoupper( $initials );
}

/**
 * @description Profile picture of a user
 * @param {string} $user_id - users.user_id
 * @returns {array} type (initials | theme | upload), value (theme file / upload path), url ('' for initials)
 */
function siteUserAvatar( $user_id ){
    $value = (string) getUserMetaValue( escape_string( $user_id ), SITE_META_AVATAR );
    if( preg_match( '/^theme:([0-9a-z\-]+\.svg)$/', $value, $m ) && is_file( BASE_PATH . '/' . SITE_AVATAR_THEME_DIR . '/' . $m[ 1 ] ) ){
        return array( 'type' => 'theme', 'value' => $m[ 1 ], 'url' => SITE_AVATAR_THEME_DIR . '/' . $m[ 1 ] );
    }
    if( preg_match( '#^upload:(' . preg_quote( SITE_AVATAR_UPLOAD_DIR, '#' ) . '/[A-Za-z0-9_\-.]+)$#', $value, $m ) && is_file( BASE_PATH . '/' . $m[ 1 ] ) ){
        return array( 'type' => 'upload', 'value' => $m[ 1 ], 'url' => $m[ 1 ] );
    }
    return array( 'type' => 'initials', 'value' => '', 'url' => '' );
}

/**
 * @description The theme's cartoon avatars, by gender (from the file names: boy / girl)
 * @returns {array} [ { file, gender } ]
 */
function siteThemeAvatars(){
    $list = array();
    foreach( (array) glob( BASE_PATH . '/' . SITE_AVATAR_THEME_DIR . '/*.svg' ) as $path ){
        $file = basename( $path );
        if( strpos( $file, 'blank' ) === 0 ){ continue; }
        $list[] = array( 'file' => $file, 'gender' => ( strpos( $file, 'girl' ) !== false ) ? 'female' : 'male' );
    }
    return $list;
}

/**
 * @description Whether a user's access token was issued before "sign out other devices" (then it is refused)
 * @param {array|false} $payload - Access token payload
 * @returns {bool} true = the token is still allowed
 */
function siteUserTokenStillAllowed( $payload ){
    static $checked = array();                                 // user_id => valid-after time, for this request
    if( !is_array( $payload ) || !isset( $payload[ SESSION_USER_ID ] ) || isset( $payload[ 'app_id' ] ) ){ return true; }
    $user_id = (string) $payload[ SESSION_USER_ID ];
    if( !isset( $checked[ $user_id ] ) ){
        $checked[ $user_id ] = (float) getUserMetaValue( escape_string( $user_id ), SITE_META_TOKENS_VALID_AFTER );
    }
    return !isset( $payload[ 'iat' ] ) || (float) $payload[ 'iat' ] >= $checked[ $user_id ];
}

/**
 * @description <link> tags of one favicon file, with a version tag so a changed icon is fetched again (1.0.63)
 * @param {string} $path - Path on this site or full address
 * @param {array} $rels - rel values to print for it
 * @returns {string} HTML
 */
function siteFaviconTags( $path, $rels ){
    $types = array( 'ico' => 'image/x-icon', 'png' => 'image/png', 'svg' => 'image/svg+xml', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'webp' => 'image/webp', 'gif' => 'image/gif' );
    $ext = strtolower( pathinfo( (string) parse_url( $path, PHP_URL_PATH ), PATHINFO_EXTENSION ) );
    $local = !preg_match( '#^https?://#i', $path );
    $version = $local ? (string) @filemtime( BASE_PATH . '/' . $path ) : substr( md5( $path ), 0, 8 );   // Changes when the icon changes
    $href = htmlspecialchars( $path . ( strpos( $path, '?' ) === false ? '?' : '&' ) . 'v=' . $version );
    $type = isset( $types[ $ext ] ) ? ' type="' . $types[ $ext ] . '"' : '';
    $html = '';
    foreach( $rels as $rel ){
        $html .= '<link rel="' . $rel . '" href="' . $href . '"' . $type . ( $rel === 'shortcut icon' ? '' : ' sizes="256x256"' ) . ' />' . "\n";
    }
    return $html;
}
?>
