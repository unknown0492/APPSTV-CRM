<?php

/*
 * My Profile (Scodezy base, gen3 1.0.61) - the signed-in user's own profile: names, display name, avatar, email change
 * (verified by link or 6-digit code, chosen on Website Configuration), password change (optionally signing out the
 * other devices, with an email notice). Mobile number keeps its existing OTP actions (scodezy_user_profile.php).
 *
 * Every action works on the user of the access token - never on a user_id sent by the browser.
 */

define( 'PROFILE_META_EMAIL_CODE_HASH', '_email_update_code_hash' );          // sha256 of the 6-digit code (code method)
define( 'PROFILE_META_EMAIL_CODE_ATTEMPTS', '_email_update_code_attempts' );  // Wrong codes typed for the pending change
define( 'PROFILE_META_EMAIL_METHOD', '_email_update_method' );                // link | code, as when the change was requested
define( 'PROFILE_EMAIL_CODE_MAX_ATTEMPTS', 5 );                               // After this, the change must be requested again
define( 'PROFILE_AVATAR_MAX', 2 * 1024 * 1024 );                              // 2 MB per picture

/**
 * @description user_id of the signed-in user (from the access token)
 * @returns {string} '' when unknown
 */
function profileUserId(){
    global $globalAccessToken;
    $payload = getJWTPayload( $globalAccessToken );
    return ( is_array( $payload ) && isset( $payload[ SESSION_USER_ID ] ) ) ? (string) $payload[ SESSION_USER_ID ] : '';
}

/**
 * @description users row of the signed-in user; ends the request with a JSON error when not found
 * @param {string} $fn - Action name
 * @returns {array}
 */
function profileUser( $fn ){
    $e_user_id = escape_string( profileUserId() );
    $result_set = selectQuery( "SELECT * FROM users WHERE user_id = '$e_user_id' LIMIT 1" );
    $user = $result_set ? mysqli_fetch_assoc( $result_set ) : null;
    if( !$user ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, $fn, "Your session is not valid any more. Please sign in again." );
        exit();
    }
    return $user;
}

/**
 * @description Ends the request with a JSON error
 * @param {string} $fn - Action name
 * @param {string} $message - Shown to the user
 * @returns {void}
 */
function profileFail( $fn, $message ){
    echo createJSONMessage( GENERAL_ERROR_MESSAGE, $fn, $message );
    exit();
}

/**
 * @description Masks an email for display (an***@example.com)
 * @param {string} $email - Address
 * @returns {string}
 */
function profileMaskEmail( $email ){
    $parts = explode( '@', (string) $email );
    if( count( $parts ) !== 2 ){ return (string) $email; }
    return mb_substr( $parts[ 0 ], 0, 2 ) . str_repeat( '*', max( 1, mb_strlen( $parts[ 0 ] ) - 2 ) ) . '@' . $parts[ 1 ];
}

/**
 * @description Sends an account email in the common design (templates/email/account_notice.php)
 * @param {string} $to - Address
 * @param {string} $subject - Subject
 * @param {string} $heading - Big title in the email
 * @param {string} $intro - Text under the title (plain text)
 * @param {array} $action - Optional: [ 'button' => text, 'url' => link ] or [ 'code' => '123456' ]
 * @param {string} $note - Small text at the bottom (plain text)
 * @returns {bool} Sent
 */
function profileSendMail( $to, $subject, $heading, $intro, $action, $note ){
    $site = getSiteConfig();
    $site_name = (string) $site->site_name;
    $action_html = '';
    if( isset( $action[ 'url' ] ) ){
        $url = htmlspecialchars( $action[ 'url' ], ENT_QUOTES );
        $action_html = '<table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center"><tr><td align="center" bgcolor="#1b84ff" style="border-radius:10px;">'
                     . '<a href="' . $url . '" target="_blank" style="display:inline-block; padding:15px 34px; font-size:16px; font-weight:600; color:#ffffff; text-decoration:none; border-radius:10px;">'
                     . htmlspecialchars( $action[ 'button' ] ) . '</a></td></tr></table>'
                     . '<div style="font-size:12px; line-height:1.6; color:#99a1b7; margin-top:18px;">Button not working? Copy this address into your browser:<br>'
                     . '<span style="color:#1b84ff; word-break:break-all;">' . $url . '</span></div>';
    }
    else if( isset( $action[ 'code' ] ) ){
        $action_html = '<div style="display:inline-block; padding:14px 26px; border-radius:10px; background-color:#f4f7fc; font-family:Consolas, \'Courier New\', monospace;'
                     . ' font-size:30px; font-weight:700; letter-spacing:8px; color:#1e2a4a;">' . htmlspecialchars( $action[ 'code' ] ) . '</div>';
    }
    $message = (string) @file_get_contents( BASE_PATH . '/templates/email/account_notice.php' );
    $message = str_replace(
        array( '{{site_name}}', '{{heading}}', '{{intro}}', '{{action}}', '{{note}}' ),
        array( htmlspecialchars( $site_name ), htmlspecialchars( $heading ), nl2br( htmlspecialchars( $intro ) ), $action_html, nl2br( htmlspecialchars( $note ) ) ),
        $message );

    $mail = sendMailObject();
    $mail->isHTML( true );
    $mail->setFrom( EMAIL_NOREPLY, $site_name );
    $mail->AddAddress( $to );
    $mail->Subject = $subject . ' | ' . $site_name;
    $mail->Body = $message;
    try { return (bool) $mail->Send(); } catch( Throwable $e ){ return false; }
}

/**
 * @description Clears a pending email change
 * @param {string} $e_user_id - Escaped user_id
 * @returns {void}
 */
function profileClearEmailChange( $e_user_id ){
    foreach( array( _EMAIL_UPDATE_NEW_EMAIL, _EMAIL_UPDATE_VERIFICATION_CODE, _EMAIL_UPDATE_VERIFICATION_CODE_GENERATION_TIMESTAMP,
                    _EMAIL_UPDATE_VERIFICATION_STATUS, PROFILE_META_EMAIL_CODE_HASH, PROFILE_META_EMAIL_CODE_ATTEMPTS, PROFILE_META_EMAIL_METHOD ) as $key ){
        deleteUserMetaValue( $e_user_id, $key );
    }
}

/**
 * @description How long a pending email change stays valid (ms): link 24 h (or the configured value), code = Website Configuration minutes
 * @param {string} $method - link | code
 * @returns {int}
 */
function profileEmailChangeValidity( $method ){
    if( $method === 'code' ){
        return max( 5, min( 120, (int) siteSetting( 'email_change_code_minutes', '15' ) ) ) * 60000;
    }
    return (int) getConfigurationValue( _EMAIL_UPDATE_VERIFICATION_CODE_EXPIRY_DURATION, _EMAIL_UPDATE_VERIFICATION_CODE_EXPIRY_DURATION_DEFAULT );
}

/**
 * @description Starts (or restarts) the verification of a pending email change and sends the link / code to the new address
 * @param {string} $user_id - users.user_id
 * @param {string} $new_email - The new address
 * @returns {array} [ sent (bool), method ]
 */
function profileSendEmailVerification( $user_id, $new_email ){
    $e_user_id = escape_string( $user_id );
    $method = ( siteSetting( 'email_change_method', 'link' ) === 'code' ) ? 'code' : 'link';
    $now = currentTimeMilliseconds();
    profileClearEmailChange( $e_user_id );
    setUserMetaValue( $e_user_id, _EMAIL_UPDATE_NEW_EMAIL, escape_string( $new_email ) );
    setUserMetaValue( $e_user_id, _EMAIL_UPDATE_VERIFICATION_CODE_GENERATION_TIMESTAMP, $now );
    setUserMetaValue( $e_user_id, _EMAIL_UPDATE_VERIFICATION_STATUS, _EMAIL_UPDATE_VERIFICATION_STATUS_DEFAULT );
    setUserMetaValue( $e_user_id, PROFILE_META_EMAIL_METHOD, $method );

    if( $method === 'code' ){
        $code = str_pad( (string) random_int( 0, 999999 ), 6, '0', STR_PAD_LEFT );   // Random 6 digits
        setUserMetaValue( $e_user_id, PROFILE_META_EMAIL_CODE_HASH, hash( 'sha256', $user_id . '|' . $code ) );
        setUserMetaValue( $e_user_id, PROFILE_META_EMAIL_CODE_ATTEMPTS, '0' );
        $minutes = (int) ( profileEmailChangeValidity( 'code' ) / 60000 );
        $sent = profileSendMail( $new_email, 'Confirm your new email address', 'Confirm your new email',
            "Type this code on My Profile to confirm that this address belongs to your account (" . $user_id . ").",
            array( 'code' => $code ), "The code is valid for " . $minutes . " minutes. Did not ask for this? Ignore this email: nothing changes." );
        return array( $sent, $method );
    }

    $code = bin2hex( random_bytes( 20 ) );                      // Random, not guessable from the time
    setUserMetaValue( $e_user_id, _EMAIL_UPDATE_VERIFICATION_CODE, $code );
    $site = getSiteConfig();
    $url = ( $site->protocol === 'https' ? 'https' : 'http' ) . '://' . rtrim( (string) $site->domain_name, '/' ) . '/verify-email.php?u=' . rawurlencode( $user_id ) . '&c=' . $code;
    $sent = profileSendMail( $new_email, 'Confirm your new email address', 'Confirm your new email',
        "Open the link below to confirm that this address belongs to your account (" . $user_id . "). Your email changes only after you confirm.",
        array( 'button' => 'Confirm my email', 'url' => $url ), "The link works once and expires in 24 hours. Did not ask for this? Ignore this email: nothing changes." );
    return array( $sent, $method );
}

/**
 * @description Applies a verified email change: updates users.email, clears the pending change, tells the old address
 * @param {string} $user_id - users.user_id
 * @returns {string} '' on success, else the reason
 */
function profileApplyEmailChange( $user_id ){
    $e_user_id = escape_string( $user_id );
    $new_email = (string) getUserMetaValue( $e_user_id, _EMAIL_UPDATE_NEW_EMAIL );
    if( $new_email === '' ){ return "There is no email change waiting for confirmation."; }
    $e_new_email = escape_string( $new_email );
    if( mysqli_num_rows( selectQuery( "SELECT user_id FROM users WHERE email = '$e_new_email' AND user_id <> '$e_user_id'" ) ) > 0 ){
        profileClearEmailChange( $e_user_id );
        return "This email is now used by another account. Please choose another address.";
    }
    $old_rs = selectQuery( "SELECT email FROM users WHERE user_id = '$e_user_id' LIMIT 1" );
    $old_row = $old_rs ? mysqli_fetch_assoc( $old_rs ) : null;
    updateQuery( "UPDATE users SET email = '$e_new_email' WHERE user_id = '$e_user_id'" );
    profileClearEmailChange( $e_user_id );
    if( $old_row && (string) $old_row[ 'email' ] !== '' && strcasecmp( (string) $old_row[ 'email' ], $new_email ) !== 0 ){
        profileSendMail( (string) $old_row[ 'email' ], 'Your email address was changed', 'Your email was changed',
            "The email address of your account (" . $user_id . ") was changed to " . profileMaskEmail( $new_email ) . ".",
            array(), "If you did not do this, contact your administrator straight away." );
    }
    return '';
}

/**
 * @description Checks a pending change is still valid; clears it when expired
 * @param {string} $user_id - users.user_id
 * @returns {string} '' when valid, else the reason
 */
function profilePendingEmailValid( $user_id ){
    $e_user_id = escape_string( $user_id );
    $created = (float) getUserMetaValue( $e_user_id, _EMAIL_UPDATE_VERIFICATION_CODE_GENERATION_TIMESTAMP );
    $method = (string) getUserMetaValue( $e_user_id, PROFILE_META_EMAIL_METHOD );
    if( getUserMetaValue( $e_user_id, _EMAIL_UPDATE_NEW_EMAIL ) === null || $created <= 0 ){ return "There is no email change waiting for confirmation."; }
    if( currentTimeMilliseconds() - $created > profileEmailChangeValidity( $method === 'code' ? 'code' : 'link' ) ){
        profileClearEmailChange( $e_user_id );
        return "The confirmation has expired. Please change your email again.";
    }
    return '';
}


// ── Actions ─────────────────────────────────────────────────────

/**
 * Everything My Profile shows for the signed-in user
 */
function scodezy_get_my_profile(){
    checkAuthorizationForFunction( __FUNCTION__ );
    $user = profileUser( __FUNCTION__ );
    $e_user_id = escape_string( $user[ 'user_id' ] );

    $pending = null;                                           // Email change waiting for confirmation
    if( profilePendingEmailValid( $user[ 'user_id' ] ) === '' ){
        $method = (string) getUserMetaValue( $e_user_id, PROFILE_META_EMAIL_METHOD );
        $pending = array(
            'email'  => (string) getUserMetaValue( $e_user_id, _EMAIL_UPDATE_NEW_EMAIL ),
            'method' => ( $method === 'code' ) ? 'code' : 'link'
        );
    }
    $own_display = (string) getUserMetaValue( $e_user_id, SITE_META_DISPLAY_NAME );
    $phone_pending = getUserMetaValue( $e_user_id, _PHONE_UPDATE_NEW_PHONE );

    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, array(
        'message' => 'Profile.',
        'data'    => array(
            'user_id'      => $user[ 'user_id' ],
            'fname'        => (string) $user[ 'fname' ],
            'lname'        => (string) $user[ 'lname' ],
            'nickname'     => (string) $user[ 'nickname' ],
            'email'        => (string) $user[ 'email' ],
            'country_code' => (string) $user[ 'country_code' ],
            'phone'        => (string) $user[ 'phone' ],
            'phone_pending'=> ( $phone_pending !== null && $phone_pending !== '' ),
            'initials'     => siteUserInitials( $user ),
            'avatar'       => siteUserAvatar( $user[ 'user_id' ] ),
            'display'      => array(
                'current'     => siteUserDisplayField( $user[ 'user_id' ] ),
                'own'         => isset( SITE_DISPLAY_NAME_OPTIONS[ $own_display ] ) ? $own_display : '',
                'default'     => siteSetting( 'profile_display_name_default', 'full_name' ),
                'user_choice' => siteSetting( 'profile_display_name_user_choice', '1' ) === '1',
                'options'     => SITE_DISPLAY_NAME_OPTIONS,
                'text'        => siteUserDisplayName( $user )
            ),
            'avatar_upload_allowed' => siteSetting( 'profile_avatar_upload', '1' ) === '1',
            'theme_avatars' => siteThemeAvatars(),
            'email_pending' => $pending,
            'email_method'  => ( siteSetting( 'email_change_method', 'link' ) === 'code' ) ? 'code' : 'link'
        )
    ) );
}


/**
 * Saves first name, last name, nickname (of the signed-in user only)
 */
function scodezy_update_self_profile_details(){
    checkAuthorizationForFunction( __FUNCTION__ );
    $user = profileUser( __FUNCTION__ );                        // Never a user_id from the browser (1.0.61)
    $first_name = trim( (string) request( 'fname' ) );
    $last_name  = trim( (string) request( 'lname' ) );
    $nick_name  = trim( (string) request( 'nickname' ) );

    validateEmptyString( $first_name, __FUNCTION__, "First Name is required !" );
    validateEmptyString( $last_name, __FUNCTION__, "Last Name is required !" );
    validate( $first_name, __FUNCTION__, getValidationRegex( "VLDTN_FIRST_NAME" ), getValidationErrMsg( "VLDTN_FIRST_NAME" ) );
    validate( $last_name, __FUNCTION__, getValidationRegex( "VLDTN_LAST_NAME" ), getValidationErrMsg( "VLDTN_LAST_NAME" ) );
    if( $nick_name !== '' ){
        validate( $nick_name, __FUNCTION__, getValidationRegex( "VLDTN_LAST_NAME" ), getValidationErrMsg( "VLDTN_NICK_NAME" ) );
    }

    $e_user_id = escape_string( $user[ 'user_id' ] );
    updateQuery( "UPDATE users SET fname = '" . escape_string( $first_name ) . "', lname = '" . escape_string( $last_name ) . "', nickname = '" . escape_string( $nick_name ) . "' WHERE user_id = '$e_user_id'" );
    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, array(
        'message' => "Profile details saved.",
        'data'    => array( 'fname' => $first_name, 'lname' => $last_name, 'nickname' => $nick_name )
    ) );
}


/**
 * Chooses the profile picture: initials, a theme avatar, or an uploaded picture
 */
function scodezy_set_self_avatar(){
    checkAuthorizationForFunction( __FUNCTION__ );
    $fn = __FUNCTION__;
    $user = profileUser( $fn );
    $e_user_id = escape_string( $user[ 'user_id' ] );
    $type = (string) request( 'type' );
    $previous = siteUserAvatar( $user[ 'user_id' ] );

    if( $type === 'initials' ){
        setUserMetaValue( $e_user_id, SITE_META_AVATAR, 'initials' );
    }
    else if( $type === 'theme' ){
        $file = (string) request( 'file' );
        $valid = false;
        foreach( siteThemeAvatars() as $a ){ if( $a[ 'file' ] === $file ){ $valid = true; } }
        if( !$valid ){ profileFail( $fn, "Unknown avatar." ); }
        setUserMetaValue( $e_user_id, SITE_META_AVATAR, escape_string( 'theme:' . $file ) );
    }
    else if( $type === 'upload' ){
        if( siteSetting( 'profile_avatar_upload', '1' ) !== '1' ){ profileFail( $fn, "Uploading own pictures is turned off on this website." ); }
        $file = isset( $_FILES[ 'image' ] ) ? $_FILES[ 'image' ] : null;
        if( !is_array( $file ) || (int) $file[ 'error' ] !== UPLOAD_ERR_OK || !is_uploaded_file( $file[ 'tmp_name' ] ) ){ profileFail( $fn, "The picture could not be uploaded." ); }
        if( (int) $file[ 'size' ] > PROFILE_AVATAR_MAX ){ profileFail( $fn, "The picture is larger than 2 MB." ); }
        $types = array( 'image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp' );   // Read from the file itself; SVG refused
        $finfo = new finfo( FILEINFO_MIME_TYPE );
        $mime = (string) $finfo->file( $file[ 'tmp_name' ] );
        if( !isset( $types[ $mime ] ) ){ profileFail( $fn, "Use a PNG, JPG or WEBP picture." ); }
        $dir = BASE_PATH . '/' . SITE_AVATAR_UPLOAD_DIR;
        if( !is_dir( $dir ) && !@mkdir( $dir, 0755, true ) ){ profileFail( $fn, "The upload folder could not be created." ); }
        $name = preg_replace( '/[^A-Za-z0-9_\-]/', '_', $user[ 'user_id' ] ) . '-' . currentTimeMilliseconds() . '.' . $types[ $mime ];
        if( !move_uploaded_file( $file[ 'tmp_name' ], $dir . '/' . $name ) ){ profileFail( $fn, "The picture could not be saved." ); }
        setUserMetaValue( $e_user_id, SITE_META_AVATAR, escape_string( 'upload:' . SITE_AVATAR_UPLOAD_DIR . '/' . $name ) );
    }
    else{
        profileFail( $fn, "Unknown choice." );
    }

    // The previous uploaded picture is not used any more
    if( $previous[ 'type' ] === 'upload' ){
        $now = siteUserAvatar( $user[ 'user_id' ] );
        if( $now[ 'value' ] !== $previous[ 'value' ] ){ @unlink( BASE_PATH . '/' . $previous[ 'value' ] ); }
    }
    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, $fn, array( 'message' => "Profile picture saved.", 'data' => siteUserAvatar( $user[ 'user_id' ] ) ) );
}


/**
 * Chooses what appears next to the profile picture (when the website lets users choose)
 */
function scodezy_set_self_display_name(){
    checkAuthorizationForFunction( __FUNCTION__ );
    $user = profileUser( __FUNCTION__ );
    if( siteSetting( 'profile_display_name_user_choice', '1' ) !== '1' ){ profileFail( __FUNCTION__, "The website decides what is shown; it cannot be changed here." ); }
    $field = (string) request( 'field' );
    $e_user_id = escape_string( $user[ 'user_id' ] );
    if( $field === '' ){
        deleteUserMetaValue( $e_user_id, SITE_META_DISPLAY_NAME );          // Back to the website default
    }
    else{
        if( !isset( SITE_DISPLAY_NAME_OPTIONS[ $field ] ) ){ profileFail( __FUNCTION__, "Unknown choice." ); }
        setUserMetaValue( $e_user_id, SITE_META_DISPLAY_NAME, $field );
    }
    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, array( 'message' => "Saved.", 'data' => array( 'text' => siteUserDisplayName( $user ) ) ) );
}


/**
 * Requests an email change: checks the password and sends a link or a 6-digit code to the new address
 */
function scodezy_update_self_email(){
    checkAuthorizationForFunction( __FUNCTION__ );
    $fn = __FUNCTION__;
    $user = profileUser( $fn );
    $password = (string) request( 'password' );
    $email = trim( (string) request( 'email' ) );

    validateEmptyString( $email, $fn, "Enter the new email address." );
    validateEmptyString( $password, $fn, "Enter your password." );
    validate( $email, $fn, getValidationRegex( "VLDTN_EMAIL" ), "The email address is not valid." );
    if( !password_verify( escape_string( $password ), (string) $user[ 'password' ] ) ){ profileFail( $fn, "The password is incorrect." ); }
    if( strcasecmp( $email, (string) $user[ 'email' ] ) === 0 ){ profileFail( $fn, "This is already your email address." ); }
    $e_email = escape_string( $email );
    if( mysqli_num_rows( selectQuery( "SELECT user_id FROM users WHERE email = '$e_email'" ) ) > 0 ){ profileFail( $fn, "This email is already used by another account." ); }

    list( $sent, $method ) = profileSendEmailVerification( $user[ 'user_id' ], $email );
    if( !$sent ){
        profileClearEmailChange( escape_string( $user[ 'user_id' ] ) );
        profileFail( $fn, "The email to " . $email . " could not be sent. Please check the address or try later." );
    }
    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, $fn, array(
        'message' => ( $method === 'code' ) ? "A 6-digit code was sent to " . $email . ". Type it below to confirm."
                                            : "A confirmation link was sent to " . $email . ". Your email changes after you open it.",
        'data'    => array( 'email' => $email, 'method' => $method )
    ) );
}


/**
 * Sends the link / code of the pending email change again
 */
function scodezy_resend_self_email_verification(){
    checkAuthorizationForFunction( __FUNCTION__ );
    $user = profileUser( __FUNCTION__ );
    $e_user_id = escape_string( $user[ 'user_id' ] );
    $new_email = (string) getUserMetaValue( $e_user_id, _EMAIL_UPDATE_NEW_EMAIL );
    if( $new_email === '' ){ profileFail( __FUNCTION__, "There is no email change waiting for confirmation." ); }
    $created = (float) getUserMetaValue( $e_user_id, _EMAIL_UPDATE_VERIFICATION_CODE_GENERATION_TIMESTAMP );
    if( currentTimeMilliseconds() - $created < 60000 ){ profileFail( __FUNCTION__, "Please wait a minute before asking again." ); }
    list( $sent, $method ) = profileSendEmailVerification( $user[ 'user_id' ], $new_email );
    if( !$sent ){ profileFail( __FUNCTION__, "The email could not be sent. Please try later." ); }
    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, array(
        'message' => ( $method === 'code' ) ? "A new code was sent to " . $new_email . "." : "A new link was sent to " . $new_email . ".",
        'data'    => array( 'email' => $new_email, 'method' => $method )
    ) );
}


/**
 * Cancels the pending email change
 */
function scodezy_cancel_self_email_update(){
    checkAuthorizationForFunction( __FUNCTION__ );
    $user = profileUser( __FUNCTION__ );
    profileClearEmailChange( escape_string( $user[ 'user_id' ] ) );
    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, array( 'message' => "The email change was cancelled." ) );
}


/**
 * Confirms the pending email change with the 6-digit code (code method)
 */
function scodezy_confirm_self_email_code(){
    checkAuthorizationForFunction( __FUNCTION__ );
    $fn = __FUNCTION__;
    $user = profileUser( $fn );
    $e_user_id = escape_string( $user[ 'user_id' ] );
    $code = trim( (string) request( 'code' ) );
    if( !preg_match( '/^\d{6}$/', $code ) ){ profileFail( $fn, "Type the 6-digit code from the email." ); }
    $reason = profilePendingEmailValid( $user[ 'user_id' ] );
    if( $reason !== '' ){ profileFail( $fn, $reason ); }
    $hash = (string) getUserMetaValue( $e_user_id, PROFILE_META_EMAIL_CODE_HASH );
    if( $hash === '' ){ profileFail( $fn, "This change is confirmed with the link sent by email." ); }

    if( !hash_equals( $hash, hash( 'sha256', $user[ 'user_id' ] . '|' . $code ) ) ){
        $attempts = (int) getUserMetaValue( $e_user_id, PROFILE_META_EMAIL_CODE_ATTEMPTS ) + 1;
        if( $attempts >= PROFILE_EMAIL_CODE_MAX_ATTEMPTS ){
            profileClearEmailChange( $e_user_id );
            profileFail( $fn, "Too many wrong codes. The change was cancelled; please change your email again." );
        }
        setUserMetaValue( $e_user_id, PROFILE_META_EMAIL_CODE_ATTEMPTS, (string) $attempts );
        profileFail( $fn, "The code is not correct (" . ( PROFILE_EMAIL_CODE_MAX_ATTEMPTS - $attempts ) . " tries left)." );
    }
    $error = profileApplyEmailChange( $user[ 'user_id' ] );
    if( $error !== '' ){ profileFail( $fn, $error ); }
    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, $fn, array( 'message' => "Your email address was changed." ) );
}


/**
 * Changes the password; optionally signs out every other device; emails a notice
 */
function scodezy_update_self_password(){
    checkAuthorizationForFunction( __FUNCTION__ );
    $fn = __FUNCTION__;
    $user = profileUser( $fn );
    $current = (string) request( 'current_password' );
    $new = (string) request( 'new_password' );
    $confirm = (string) request( 'confirm_password' );
    $sign_out_others = (string) request( 'sign_out_others' ) === '1';

    validateEmptyString( $current, $fn, "Enter your current password." );
    validateEmptyString( $new, $fn, "Enter the new password." );
    validate( $new, $fn, getValidationRegex( "VLDTN_PASSWORD" ), getValidationErrMsg( "VLDTN_PASSWORD" ) );
    if( $new !== $confirm ){ profileFail( $fn, "The new passwords do not match." ); }
    if( !password_verify( escape_string( $current ), (string) $user[ 'password' ] ) ){ profileFail( $fn, "The current password is incorrect." ); }
    if( $current === $new ){ profileFail( $fn, "The new password must be different from the current one." ); }

    $e_user_id = escape_string( $user[ 'user_id' ] );
    $hash = hashPassword( escape_string( $new ) );
    if( updateQuery( "UPDATE users SET password = '$hash' WHERE user_id = '$e_user_id'" ) <= 0 ){ profileFail( $fn, "The password could not be saved. Please try again." ); }
    $now = currentTimeMilliseconds();
    setUserMetaValue( $e_user_id, _PASSWORD_UPDATE_TIMESTAMP, $now );

    $data = array();
    if( $sign_out_others ){
        // Every older sign-in is refused, and their refresh tokens stop working
        setUserMetaValue( $e_user_id, SITE_META_TOKENS_VALID_AFTER, $now );
        updateQuery( "UPDATE refresh_tokens SET is_expired = '1' WHERE token_id IN ( SELECT token_id FROM user_tokens WHERE user_id = '$e_user_id' )" );

        // This browser gets a fresh sign-in (same contents, issued now)
        $payload = array(
            SESSION_AUTHORIZATION => $user[ 'role_id' ],
            SESSION_USER_ID       => $user[ 'user_id' ],
            SESSION_FIRST_NAME    => $user[ 'fname' ],
            SESSION_LAST_NAME     => $user[ 'lname' ],
            SESSION_NICK_NAME     => $user[ 'nickname' ],
            SESSION_EMAIL         => $user[ 'email' ],
            'HTTP_USER_AGENT'     => isset( $_SERVER[ 'HTTP_USER_AGENT' ] ) ? $_SERVER[ 'HTTP_USER_AGENT' ] : '',
            'iat'                 => $now + 1,
            'exp'                 => TOKEN_EXPIRY_DURATION
        );
        $jwt = createStandardJWT( $payload );
        $rjwt = createStandardRefreshToken( $user[ 'public_key' ], array( 'type' => 'user', 'id' => $user[ 'user_id' ], 'iat' => $now + 1, 'exp' => REFRESH_TOKEN_EXPIRY ) );
        setcookie( TOKEN_NAME, $jwt, 0, "/" );
        $token_id = storeTokenInDB( $rjwt, REFRESH_TOKEN_EXPIRY, $jwt, 0 );
        if( $token_id !== false ){ storeUserIDTokenIDPair( $e_user_id, $token_id ); }
        $data[ 'refresh_token_name' ] = REFRESH_TOKEN_NAME;
        $data[ 'refresh_token' ] = $rjwt;
    }

    if( (string) $user[ 'email' ] !== '' ){
        profileSendMail( (string) $user[ 'email' ], 'Your password was changed', 'Your password was changed',
            "The password of your account (" . $user[ 'user_id' ] . ") was changed on " . gmdate( 'j M Y, H:i' ) . " (UTC)."
            . ( $sign_out_others ? "\nAll other devices were signed out." : "" ),
            array(), "If you did not do this, reset your password with Forgot Password and contact your administrator." );
    }
    $data[ 'message' ] = "Password changed." . ( $sign_out_others ? " Other devices were signed out." : "" );
    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, $fn, $data );
}
?>
