<?php

/*
 * Website Configuration page (Scodezy base feature, gen3 1.0.56) - Administrator > Website Configuration.
 *
 * One page for the site-wide settings that were spread over the site_config row, the configurations table and text
 * typed into includes/*.php: identity (name, tagline, copyright, domain, protocol), logos and favicon, sign-in
 * options, maintenance (log retention, PHP errors) and the keys of the social sign-in providers (future scope:
 * stored only, nothing signs in with them yet).
 *
 * Secrets (social client secrets) are never sent back to the browser: the page only learns whether one is saved.
 *
 * 1.0.58: tabs; sign-in pages (tab title, heading, sub-heading, own logo, background), admin panel (footer links +
 * CSS, browser tab title format, dashboard heading), support contact, maintenance mode. The pages read these through
 * library/site_settings_functions.php.
 */

/**
 * Settings kept in configurations, with their defaults (also the values shown before the migration ran)
 */
define( 'SCODEZY_WC_CONFIG_DEFAULTS', array(
    'login_page_tagline' => 'Your support system for APPSTV Products',
    'copyright_year'     => '2024',
    'copyright_owner'    => 'APPSTV',
    'copyright_url'      => 'https://appstv.com.sg'
) );

/**
 * Logos and favicon: configurations key => label
 */
define( 'SCODEZY_WC_IMAGES', array(
    'login_page_logo'      => 'Sign-in pages logo',
    'admin_page_logo'      => 'Left menu logo',
    'site_favicon'         => 'Favicon',
    '_email_template_logo' => 'Email logo'
) );

/**
 * Optional images (empty = the page falls back): configurations key => label (1.0.58)
 */
define( 'SCODEZY_WC_OPTIONAL_IMAGES', array(
    'signin_login_logo'       => 'Login page own logo',
    'signin_signup_logo'      => 'Sign Up page own logo',
    'signin_forgot_logo'      => 'Forgot Password page own logo',
    'signin_reset_logo'       => 'Password Reset page own logo',
    'signin_background_image' => 'Sign-in background image'
) );

/**
 * Social sign-in providers (future scope)
 */
define( 'SCODEZY_WC_SOCIAL', array(
    'google'    => 'Google',
    'facebook'  => 'Facebook',
    'instagram' => 'Instagram',
    'whatsapp'  => 'WhatsApp'
) );

define( 'SCODEZY_WC_UPLOAD_DIR', 'data/uploads/site' );        // Uploaded logos (relative to the site root, git-ignored)
define( 'SCODEZY_WC_UPLOAD_MAX', 2 * 1024 * 1024 );            // 2 MB per image

/**
 * @description Value of a configurations key, or its default
 * @param {string} $key - config_key
 * @param {string} $default - Used when the row does not exist
 * @returns {string}
 */
function scodezyWcConfig( $key, $default = '' ){
    $value = getConfigurationValue( escape_string( $key ), null );
    return ( $value === null ) ? (string) $default : (string) $value;
}

/**
 * @description Saves a configurations key (insert or update)
 * @param {string} $key - config_key
 * @param {string} $value - Raw value (escaped here)
 * @returns {void}
 */
function scodezyWcSetConfig( $key, $value ){
    $e_key = escape_string( $key );
    $e_value = escape_string( (string) $value );
    insertQuery( "INSERT INTO configurations ( config_key, config_value ) VALUES ( '$e_key', '$e_value' )
                  ON DUPLICATE KEY UPDATE config_value = '$e_value'" );
}

/**
 * @description Log retention settings in configurations ('_..._logs_storage_days'): key => label
 * @returns {array}
 */
function scodezyWcRetentionKeys(){
    $keys = array( '_webservice_logs_storage_days' => 'Webservice logs' );
    $result_set = selectQuery( "SELECT config_key FROM configurations WHERE config_key LIKE '\\_%\\_logs\\_storage\\_days' ORDER BY config_key" );
    while( ( $row = mysqli_fetch_assoc( $result_set ) ) !== null ){
        if( isset( $keys[ $row[ 'config_key' ] ] ) ){ continue; }
        $name = ucfirst( str_replace( '_', ' ', preg_replace( '/^_(.*)_logs_storage_days$/', '$1', $row[ 'config_key' ] ) ) );
        $keys[ $row[ 'config_key' ] ] = $name . ' logs';
    }
    return $keys;
}

/**
 * @description Reads a 0/1 switch from the request; exits with a JSON error when invalid
 * @param {string} $name - Request parameter
 * @param {string} $fn - Action name
 * @returns {string} '0' or '1'
 */
function scodezyWcReadSwitch( $name, $fn ){
    $value = (string) request( $name );
    if( !in_array( $value, array( '0', '1' ), true ) ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, $fn, "Invalid value for " . $name . "." );
        exit();
    }
    return $value;
}

/**
 * @description Reads a whole number from the request within limits; exits with a JSON error when invalid
 * @param {string} $name - Request parameter
 * @param {int} $min - Smallest allowed
 * @param {int} $max - Largest allowed
 * @param {string} $label - Field name for the message
 * @param {string} $fn - Action name
 * @returns {int}
 */
function scodezyWcReadNumber( $name, $min, $max, $label, $fn ){
    $value = trim( (string) request( $name ) );
    if( !preg_match( '/^\d{1,9}$/', $value ) || (int) $value < $min || (int) $value > $max ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, $fn, $label . " must be a whole number from " . $min . " to " . $max . "." );
        exit();
    }
    return (int) $value;
}

/**
 * @description Ends the request with a JSON error
 * @param {string} $fn - Action name
 * @param {string} $message - Shown to the user
 * @returns {void}
 */
function scodezyWcFail( $fn, $message ){
    echo createJSONMessage( GENERAL_ERROR_MESSAGE, $fn, $message );
    exit();
}


/**
 * @description Sign-in pages for the page: texts (defaults filled in) and their own logo (may be empty)
 * @returns {array}
 */
function scodezyWcSigninPagesData(){
    $pages = array();
    foreach( SITE_SIGNIN_PAGES as $id => $d ){
        $pages[] = array(
            'id'         => $id,
            'label'      => $d[ 'label' ],
            'title'      => siteSetting( 'signin_' . $id . '_title', $d[ 'title' ] ),
            'heading'    => siteSetting( 'signin_' . $id . '_heading', $d[ 'heading' ] ),
            'subheading' => siteSetting( 'signin_' . $id . '_subheading', $d[ 'subheading' ] ),
            'logo'       => siteSetting( 'signin_' . $id . '_logo', '' ),
            'defaults'   => $d
        );
    }
    return $pages;
}

/**
 * Everything the page shows (secrets only as "saved" flags)
 */
function scodezy_get_website_configuration(){
    checkAuthorizationForFunction( __FUNCTION__ );
    $site = getSiteConfig();

    $images = array();
    foreach( SCODEZY_WC_IMAGES as $key => $label ){
        $images[] = array( 'key' => $key, 'label' => $label, 'value' => scodezyWcConfig( $key ) );
    }
    $retention = array();
    foreach( scodezyWcRetentionKeys() as $key => $label ){
        $retention[] = array( 'key' => $key, 'label' => $label, 'value' => scodezyWcConfig( $key, '30' ) );
    }
    $social = array();
    foreach( SCODEZY_WC_SOCIAL as $id => $label ){
        $social[] = array(
            'id'           => $id,
            'label'        => $label,
            'enabled'      => scodezyWcConfig( 'social_login_' . $id . '_enabled', '0' ) === '1',
            'client_id'    => scodezyWcConfig( 'social_login_' . $id . '_client_id' ),
            'secret_saved' => scodezyWcConfig( 'social_login_' . $id . '_client_secret' ) !== ''
        );
    }

    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, array(
        'message' => 'Website configuration.',
        'data'    => array(
            'identity'  => array(
                'site_name'       => (string) $site->site_name,
                'tagline'         => scodezyWcConfig( 'login_page_tagline', SCODEZY_WC_CONFIG_DEFAULTS[ 'login_page_tagline' ] ),
                'copyright_year'  => scodezyWcConfig( 'copyright_year', SCODEZY_WC_CONFIG_DEFAULTS[ 'copyright_year' ] ),
                'copyright_owner' => scodezyWcConfig( 'copyright_owner', SCODEZY_WC_CONFIG_DEFAULTS[ 'copyright_owner' ] ),
                'copyright_url'   => scodezyWcConfig( 'copyright_url', SCODEZY_WC_CONFIG_DEFAULTS[ 'copyright_url' ] ),
                'domain_name'     => (string) $site->domain_name,
                'protocol'        => (string) $site->protocol
            ),
            'images'    => $images,
            'signin'    => array(
                'allow_forgot_password'   => (string) $site->allow_forgot_password === '1',
                'is_registration_open'    => (string) $site->is_registration_open === '1',
                'allow_email_credentials' => (string) $site->allow_email_credentials === '1',
                'activation_required'     => scodezyWcConfig( _NEW_ACCOUNT_ACTIVATION_REQUIRED, _NEW_ACCOUNT_ACTIVATION_REQUIRED_DEFAULT ) === '1',
                'activation_minutes'      => (int) round( (int) scodezyWcConfig( _NEW_ACCOUNT_ACTIVATION_CODE_VALIDITY, '1800000' ) / 60000 )
            ),
            'maintenance' => array(
                'retention'          => $retention,
                'display_php_errors' => scodezyWcConfig( '_display_php_errors', '0' ) === '1'
            ),
            'social'    => $social,
            'support'   => array(
                'email' => siteSetting( 'support_email', '' ),
                'phone' => siteSetting( 'support_phone', '' )
            ),
            'signin_pages' => scodezyWcSigninPagesData(),
            'background'   => array(
                'type'  => siteSigninBackgroundType(),
                'gradient_available' => siteGradientAvailable(),
                'color' => siteSetting( 'signin_background_color', '#0b0b0c' ),
                'image' => siteSetting( 'signin_background_image', '' )
            ),
            'admin'     => array(
                'footer_links'      => siteFooterLinks(),
                'footer_css'        => siteSetting( 'footer_custom_css', '' ),
                'tab_title_format'  => siteSetting( 'admin_tab_title_format', SITE_DEFAULT_TAB_TITLE_FORMAT ),
                'dashboard_heading' => siteDashboardHeading()
            ),
            'profiles'  => array(
                'display_default'     => siteSetting( 'profile_display_name_default', 'full_name' ),
                'display_user_choice' => siteSetting( 'profile_display_name_user_choice', '1' ) === '1',
                'display_options'     => SITE_DISPLAY_NAME_OPTIONS,
                'avatar_upload'       => siteSetting( 'profile_avatar_upload', '1' ) === '1',
                'email_method'        => ( siteSetting( 'email_change_method', 'link' ) === 'code' ) ? 'code' : 'link',
                'email_code_minutes'  => (int) siteSetting( 'email_change_code_minutes', '15' )
            ),
            'maintenance_mode' => array(
                'on'      => siteMaintenanceOn(),
                'message' => siteSetting( 'maintenance_message', '' ),
                'default' => SITE_DEFAULT_MAINTENANCE_MESSAGE
            )
        )
    ) );
}


/**
 * Saves one section of the page: identity, signin, maintenance or social
 */
function scodezy_save_website_configuration(){
    checkAuthorizationForFunction( __FUNCTION__ );
    $fn = __FUNCTION__;
    $section = (string) request( 'section' );

    if( $section === 'identity' ){
        $site_name = trim( (string) request( 'site_name' ) );
        $tagline   = trim( (string) request( 'tagline' ) );
        $year      = trim( (string) request( 'copyright_year' ) );
        $owner     = trim( (string) request( 'copyright_owner' ) );
        $url       = trim( (string) request( 'copyright_url' ) );
        $domain    = trim( (string) request( 'domain_name' ) );
        $protocol  = (string) request( 'protocol' );

        if( $site_name === '' || mb_strlen( $site_name ) > 100 ){ scodezyWcFail( $fn, "Site name is required (up to 100 characters)." ); }
        if( mb_strlen( $tagline ) > 200 ){ scodezyWcFail( $fn, "Tagline: up to 200 characters." ); }
        if( !preg_match( '/^(19|20)\d{2}$/', $year ) ){ scodezyWcFail( $fn, "Copyright year: 4 digits, e.g. 2025." ); }
        if( $owner === '' || mb_strlen( $owner ) > 100 ){ scodezyWcFail( $fn, "Copyright owner is required (up to 100 characters)." ); }
        if( $url !== '' && ( !preg_match( '#^https?://#i', $url ) || filter_var( $url, FILTER_VALIDATE_URL ) === false ) ){ scodezyWcFail( $fn, "Copyright link: a full address starting with http:// or https://, or leave it empty." ); }
        if( !preg_match( '#^[A-Za-z0-9.\-]+(:\d{1,5})?(/[A-Za-z0-9._\-/]*)?$#', $domain ) ){ scodezyWcFail( $fn, "Domain: the address without http(s)://, e.g. gen3.estv.cloud" ); }
        if( !in_array( $protocol, array( 'http', 'https' ), true ) ){ scodezyWcFail( $fn, "Protocol must be http or https." ); }

        $e_site_name = escape_string( $site_name );
        $e_domain = escape_string( rtrim( $domain, '/' ) );
        updateQuery( "UPDATE site_config SET site_name = '$e_site_name', domain_name = '$e_domain', protocol = '$protocol' LIMIT 1" );
        scodezyWcSetConfig( 'login_page_tagline', $tagline );
        scodezyWcSetConfig( 'copyright_year', $year );
        scodezyWcSetConfig( 'copyright_owner', $owner );
        scodezyWcSetConfig( 'copyright_url', $url );

        // Support contact (1.0.58): shown on the sign-in pages when set
        $support_email = trim( (string) request( 'support_email' ) );
        $support_phone = trim( (string) request( 'support_phone' ) );
        if( $support_email !== '' && filter_var( $support_email, FILTER_VALIDATE_EMAIL ) === false ){ scodezyWcFail( $fn, "Support email is not a valid address." ); }
        if( $support_phone !== '' && !preg_match( '/^[0-9+()\-\s.]{5,30}$/', $support_phone ) ){ scodezyWcFail( $fn, "Support phone: digits, spaces and + ( ) - only." ); }
        scodezyWcSetConfig( 'support_email', $support_email );
        scodezyWcSetConfig( 'support_phone', $support_phone );
    }
    else if( $section === 'profiles' ){
        // My Profile options (1.0.61)
        $display = (string) request( 'display_default' );
        if( !isset( SITE_DISPLAY_NAME_OPTIONS[ $display ] ) ){ scodezyWcFail( $fn, "Choose what appears next to the profile picture." ); }
        $method = (string) request( 'email_method' );
        if( !in_array( $method, array( 'link', 'code' ), true ) ){ scodezyWcFail( $fn, "Choose how an email change is confirmed." ); }
        $minutes = scodezyWcReadNumber( 'email_code_minutes', 5, 120, "Code valid for (minutes)", $fn );
        scodezyWcSetConfig( 'profile_display_name_default', $display );
        scodezyWcSetConfig( 'profile_display_name_user_choice', scodezyWcReadSwitch( 'display_user_choice', $fn ) );
        scodezyWcSetConfig( 'profile_avatar_upload', scodezyWcReadSwitch( 'avatar_upload', $fn ) );
        scodezyWcSetConfig( 'email_change_method', $method );
        scodezyWcSetConfig( 'email_change_code_minutes', (string) $minutes );
    }
    else if( $section === 'signin_pages' ){
        foreach( SITE_SIGNIN_PAGES as $id => $d ){
            $title      = trim( (string) request( $id . '_title' ) );
            $heading    = trim( (string) request( $id . '_heading' ) );
            $subheading = trim( (string) request( $id . '_subheading' ) );
            if( $title === '' || mb_strlen( $title ) > 60 ){ scodezyWcFail( $fn, $d[ 'label' ] . ": tab title is required (up to 60 characters)." ); }
            if( $heading === '' || mb_strlen( $heading ) > 80 ){ scodezyWcFail( $fn, $d[ 'label' ] . ": heading is required (up to 80 characters)." ); }
            if( mb_strlen( $subheading ) > 200 ){ scodezyWcFail( $fn, $d[ 'label' ] . ": sub-heading up to 200 characters." ); }
            scodezyWcSetConfig( 'signin_' . $id . '_title', $title );
            scodezyWcSetConfig( 'signin_' . $id . '_heading', $heading );
            scodezyWcSetConfig( 'signin_' . $id . '_subheading', $subheading );
        }
        $type  = (string) request( 'background_type' );
        $color = trim( (string) request( 'background_color' ) );
        if( !in_array( $type, array( 'none', 'gradient', 'color', 'image' ), true ) ){ scodezyWcFail( $fn, "Unknown background." ); }
        if( $type === 'gradient' && !siteGradientAvailable() ){ scodezyWcFail( $fn, "This website has no animated gradient files." ); }
        if( !preg_match( '/^#[0-9a-fA-F]{6}$/', $color ) ){ scodezyWcFail( $fn, "Background colour: #RRGGBB, e.g. #0b0b0c." ); }
        if( $type === 'image' && siteSetting( 'signin_background_image', '' ) === '' ){ scodezyWcFail( $fn, "Upload the background image first." ); }
        scodezyWcSetConfig( 'signin_background_type', $type );
        scodezyWcSetConfig( 'signin_background_color', strtolower( $color ) );
    }
    else if( $section === 'admin' ){
        $links = json_decode( (string) request( 'footer_links' ), true );
        if( !is_array( $links ) || count( $links ) > 4 ){ scodezyWcFail( $fn, "Footer links: up to 4." ); }
        $clean = array();
        foreach( $links as $link ){
            $label = trim( (string) ( isset( $link[ 'label' ] ) ? $link[ 'label' ] : '' ) );
            $link_url = trim( (string) ( isset( $link[ 'url' ] ) ? $link[ 'url' ] : '' ) );
            if( $label === '' && $link_url === '' ){ continue; }
            if( $label === '' || mb_strlen( $label ) > 40 ){ scodezyWcFail( $fn, "Footer link: a label is required (up to 40 characters)." ); }
            $ok_url = preg_match( '#^(https?://[^\s"<>]+|mailto:[^\s"<>]+|tel:[0-9+]+|\#|[A-Za-z0-9_\-./?=&]+)$#i', $link_url ) === 1;
            if( !$ok_url || mb_strlen( $link_url ) > 300 ){ scodezyWcFail( $fn, "Footer link '" . $label . "': use an https:// address, mailto:, tel:, # or a page of this site." ); }
            $clean[] = array( 'label' => $label, 'url' => $link_url );
        }
        $css = (string) request( 'footer_css' );
        if( mb_strlen( $css ) > 5000 ){ scodezyWcFail( $fn, "Footer CSS: up to 5000 characters." ); }
        if( strpos( $css, '<' ) !== false ){ scodezyWcFail( $fn, "Footer CSS cannot contain '<'." ); }
        $format = trim( (string) request( 'tab_title_format' ) );
        if( mb_strlen( $format ) > 100 || strpos( $format, '{page}' ) === false ){ scodezyWcFail( $fn, "Tab title format must contain {page}, e.g. {page} | {site}" ); }
        $heading = trim( (string) request( 'dashboard_heading' ) );
        if( $heading === '' || mb_strlen( $heading ) > 80 ){ scodezyWcFail( $fn, "Dashboard heading is required (up to 80 characters)." ); }
        scodezyWcSetConfig( 'footer_links', json_encode( $clean ) );
        scodezyWcSetConfig( 'footer_custom_css', $css );
        scodezyWcSetConfig( 'admin_tab_title_format', $format );
        scodezyWcSetConfig( 'dashboard_heading', $heading );
    }
    else if( $section === 'signin' ){
        $forgot     = scodezyWcReadSwitch( 'allow_forgot_password', $fn );
        $register   = scodezyWcReadSwitch( 'is_registration_open', $fn );
        $email      = scodezyWcReadSwitch( 'allow_email_credentials', $fn );
        $activation = scodezyWcReadSwitch( 'activation_required', $fn );
        $minutes    = scodezyWcReadNumber( 'activation_minutes', 5, 10080, "Activation link validity (minutes)", $fn );
        updateQuery( "UPDATE site_config SET allow_forgot_password = $forgot, is_registration_open = $register, allow_email_credentials = $email LIMIT 1" );
        scodezyWcSetConfig( _NEW_ACCOUNT_ACTIVATION_REQUIRED, $activation );
        scodezyWcSetConfig( _NEW_ACCOUNT_ACTIVATION_CODE_VALIDITY, (string) ( $minutes * 60000 ) );
    }
    else if( $section === 'maintenance' ){
        foreach( scodezyWcRetentionKeys() as $key => $label ){
            $days = scodezyWcReadNumber( $key, 1, 3650, $label . " (days)", $fn );
            scodezyWcSetConfig( $key, (string) $days );
        }
        scodezyWcSetConfig( '_display_php_errors', scodezyWcReadSwitch( 'display_php_errors', $fn ) );

        // Maintenance mode (1.0.58): people whose role cannot open Website Configuration see a maintenance page and
        // cannot sign in; TVs, OPS servers and apps are not affected
        $mode = scodezyWcReadSwitch( 'maintenance_mode', $fn );
        $message = trim( (string) request( 'maintenance_message' ) );
        if( mb_strlen( $message ) > 300 ){ scodezyWcFail( $fn, "Maintenance message: up to 300 characters." ); }
        scodezyWcSetConfig( 'maintenance_mode', $mode );
        scodezyWcSetConfig( 'maintenance_message', $message );
    }
    else if( $section === 'social' ){
        foreach( SCODEZY_WC_SOCIAL as $id => $label ){
            $enabled   = scodezyWcReadSwitch( $id . '_enabled', $fn );
            $client_id = trim( (string) request( $id . '_client_id' ) );
            $secret    = trim( (string) request( $id . '_client_secret' ) );     // Empty = keep the saved one
            $clear     = (string) request( $id . '_clear_secret' ) === '1';
            if( mb_strlen( $client_id ) > 500 || mb_strlen( $secret ) > 500 ){ scodezyWcFail( $fn, $label . ": key too long." ); }
            if( $enabled === '1' && $client_id === '' ){ scodezyWcFail( $fn, $label . ": enter the App / Client ID before enabling it." ); }
            scodezyWcSetConfig( 'social_login_' . $id . '_enabled', $enabled );
            scodezyWcSetConfig( 'social_login_' . $id . '_client_id', $client_id );
            if( $clear ){ scodezyWcSetConfig( 'social_login_' . $id . '_client_secret', '' ); }
            else if( $secret !== '' ){ scodezyWcSetConfig( 'social_login_' . $id . '_client_secret', $secret ); }
        }
    }
    else{
        scodezyWcFail( $fn, "Unknown section." );
    }

    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, array( 'message' => "Saved." ) );
}


/**
 * Uploads a logo / favicon (PNG, JPG, WEBP, GIF or ICO, up to 2 MB) and makes it the current one; or, with 'path',
 * points the setting to an existing file / address
 */
function scodezy_upload_website_image(){
    checkAuthorizationForFunction( __FUNCTION__ );
    $fn = __FUNCTION__;
    $key = (string) request( 'image_key' );
    $images = array_merge( SCODEZY_WC_IMAGES, SCODEZY_WC_OPTIONAL_IMAGES );   // Every image setting (1.0.58)
    if( !isset( $images[ $key ] ) ){ scodezyWcFail( $fn, "Unknown image." ); }

    // A typed path / address instead of a file
    $path = trim( (string) request( 'path' ) );
    if( !isset( $_FILES[ 'image' ] ) && $path === '' && isset( SCODEZY_WC_OPTIONAL_IMAGES[ $key ] ) ){
        // Optional images can be removed: the page then uses its fallback (1.0.58)
        if( $key === 'signin_background_image' && siteSigninBackgroundType() === 'image' ){
            scodezyWcFail( $fn, "The background is set to Image: choose another background first." );
        }
        scodezyWcSetConfig( $key, '' );
        echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, $fn, array( 'message' => $images[ $key ] . " removed.", 'value' => '' ) );
        return;
    }
    if( !isset( $_FILES[ 'image' ] ) ){
        $is_url  = preg_match( '#^https?://[^\s"\'<>]+$#i', $path ) === 1;
        $is_path = preg_match( '#^[A-Za-z0-9_\-][A-Za-z0-9_\-./]*\.(png|jpe?g|webp|gif|ico|svg)$#i', $path ) === 1 && strpos( $path, '..' ) === false;
        if( !$is_url && !$is_path ){ scodezyWcFail( $fn, "Enter a file path on this site (e.g. images/logo.png) or a full https:// address." ); }
        if( $key === '_email_template_logo' && !$is_url ){ scodezyWcFail( $fn, "The email logo needs a full https:// address (emails are read outside this site)." ); }
        scodezyWcSetConfig( $key, $path );
        echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, $fn, array( 'message' => $images[ $key ] . " saved.", 'value' => $path ) );
        return;
    }

    $file = $_FILES[ 'image' ];
    if( !is_array( $file ) || (int) $file[ 'error' ] !== UPLOAD_ERR_OK || !is_uploaded_file( $file[ 'tmp_name' ] ) ){ scodezyWcFail( $fn, "The file could not be uploaded." ); }
    if( (int) $file[ 'size' ] > SCODEZY_WC_UPLOAD_MAX ){ scodezyWcFail( $fn, "The file is larger than 2 MB." ); }

    // The type is read from the file itself, not from its name (SVG is refused: it can carry script)
    $types = array( 'image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp', 'image/gif' => 'gif',
                    'image/x-icon' => 'ico', 'image/vnd.microsoft.icon' => 'ico' );
    $finfo = new finfo( FILEINFO_MIME_TYPE );
    $mime = (string) $finfo->file( $file[ 'tmp_name' ] );
    if( !isset( $types[ $mime ] ) ){ scodezyWcFail( $fn, "Use a PNG, JPG, WEBP, GIF or ICO image." ); }

    $dir = dirname( __DIR__, 3 ) . '/' . SCODEZY_WC_UPLOAD_DIR;
    if( !is_dir( $dir ) && !@mkdir( $dir, 0755, true ) ){ scodezyWcFail( $fn, "The upload folder could not be created." ); }
    $name = trim( str_replace( '_', '-', $key ), '-' ) . '-' . currentTimeMilliseconds() . '.' . $types[ $mime ];
    if( !move_uploaded_file( $file[ 'tmp_name' ], $dir . '/' . $name ) ){ scodezyWcFail( $fn, "The file could not be saved." ); }

    $value = SCODEZY_WC_UPLOAD_DIR . '/' . $name;
    if( $key === '_email_template_logo' ){
        // Emails need a full address
        $site = getSiteConfig();
        $value = ( $site->protocol === 'https' ? 'https' : 'http' ) . '://' . rtrim( (string) $site->domain_name, '/' ) . '/' . $value;
    }
    scodezyWcSetConfig( $key, $value );
    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, $fn, array( 'message' => $images[ $key ] . " uploaded.", 'value' => $value ) );
}
?>
