<?php

function scodezy_create_app(){
    checkAuthorizationForFunction(__FUNCTION__ );
    
    //print_r( $_REQUEST );
    $alias          = request( 'alias' );
    $role_id        = request( 'role_id' );
    //$allowed_url    = request( 'allowed_url' );        // The base URL from which the call to this App will only be allowed (Future Scope)
    
    validateEmptyString( $alias, __FUNCTION__, "Please provide a name for the app" );
    validateEmptyDigitString( $role_id, __FUNCTION__, "Please provide a role for the app" );
    
    validate( $alias, __FUNCTION__, getValidationRegex( "VLDTN_ALPHANUMERIC_SPACE" ), "App name should only contain alphanumeric characters" );
    validate( $role_id, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), "Role is invalid" );
    
    $e_role_id = escape_string( $role_id );
    $e_alias   = escape_string( $alias );
    
    // Check if the role_id exist
    $sql = "SELECT * FROM roles WHERE role_id='$role_id'";
    $result_set = selectQuery( $sql );
    if( mysqli_num_rows( $result_set ) === 0 ){
        send_json_mime_type_header();
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "No such role !" );
        return;
    }
    
    check_again:
    $check_count = 0;
    $max_check_count = 5;
    $app_id         = generateUniqueID( "a" );
    $app_secret     = generateUniqueAppSecret();
    
    // Check if the app_id is unique
    $sql = "SELECT app_id FROM apps WHERE app_id='$app_id'";
    $result_set = selectQuery( $sql );
    if( mysqli_num_rows( $result_set ) !== 0 ){
        if( $check_count === $max_check_count ){
            send_json_mime_type_header();
            echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Failed to create the app. Please report to the administrator " );
            return;
        }
        $check_count++;
        goto check_again;
    }
    
    // Generate public-private key pair for this user
    $keys           = generateAsymmetricKeyPair();
    $public_key     = $keys[ 'publicKey' ];
    $private_key    = $keys[ 'privateKey' ];
    
    $createdOn = currentTimeMilliseconds();
    
    $sql = "INSERT INTO apps( `app_id`, `app_secret`, `alias`, `role_id`, `public_key`, `private_key`, `created_on` ) "
            . "VALUES( '$app_id', '$app_secret', '$alias', '$role_id', '$public_key', '$private_key', '$createdOn' )";
    $rows = insertQuery( $sql );
    if( $rows > 0 ){
        send_json_mime_type_header();
        echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, "App created successfully !" );
        return;
    }
    send_json_mime_type_header();
    echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "App creation failed !" );
}

/**
 * Authenticate the app by sending the app_id and app_secret and then generating an access_token and refresh_token
 * for future API access requests.
 * 
 */
function scodezy_authenticate_app(){
    //checkAuthorizationForFunction(__FUNCTION__ ); // must not authorize this function as this function is used to generate AT and RT, without it, this function will throw error 
    
    $app_id         = request( "app_id" );
    $app_secret     = request( "app_secret" );
    
    validateEmptyString( $app_id, __FUNCTION__, "App ID is required" );
    validateEmptyString( $app_secret, __FUNCTION__, "App Secret is required" );
    
    $e_app_id       = escape_string( $app_id );
    $e_app_secret   = escape_string( $app_secret );
    
    // Check if app_id exist in the DB
    $sql = "SELECT app_id, app_secret, is_blocked, role_id, public_key, private_key FROM apps WHERE app_id='$e_app_id'";
    $result_set = selectQuery( $sql );
    if( mysqli_num_rows( $result_set ) === 0 ){
        send_json_mime_type_header();
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "App ID is invalid" );
        return;
    }
    
    $val              = mysqli_fetch_object( $result_set );
    $v_app_id         = $val->app_id;
    $v_app_secret     = $val->app_secret;
    $v_is_blocked     = $val->is_blocked;
    $v_role_id        = $val->role_id;
    $v_private_key    = $val->private_key;
    
    // Check if the app has been blocked
    if( $v_is_blocked === 0 ){
        send_json_mime_type_header();
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "App has been blocked" );
        return;
    }
    
    // Check the authentication failure attempts count
    $failure_attempt_count  = getAppMetaValue( $e_app_id, _APP_VERIFICATION_FAILURE_ATTEMPT );
    $currentMillis          = currentTimeMilliseconds();
    if( $failure_attempt_count === NULL ){
        $failure_attempt_count = 0;
    }
    else{
        $failure_attempt_count = intval( $failure_attempt_count );
    }
    
    if( $failure_attempt_count >= _APP_VERIFICATION_FAILURE_MAX_ATTEMPTS ){
        // Check if the suspension time has been surpassed
        $last_failure_timestamp = intval( getAppMetaValue( $e_app_id, _APP_VERIFICATION_FAILURE_SUSPENSION_TIMESTAMP ) );
        $difference = $currentMillis - $last_failure_timestamp;
        if( $difference < _APP_VERIFICATION_FAILURE_SUSPENSION_DURATION ){
            $minutes_passed     = ceil( $difference/(60 * 1000));
            $minutes_left       = ceil(_APP_VERIFICATION_FAILURE_SUSPENSION_DURATION/(60 * 1000)) - $minutes_passed;
            send_json_mime_type_header();
            // Suspend the authentication for 4 hours (320 minutes) after 5 failed authentication attempts
            echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "App authentication failure attempts have been exceeded. Please try again after $minutes_left minutes" );
            return;
        }
        else{
            $failure_attempt_count = 0;
            setAppMetaValue( $e_app_id, _APP_VERIFICATION_FAILURE_ATTEMPT, $failure_attempt_count );
            setAppMetaValue( $e_app_id, _APP_VERIFICATION_FAILURE_SUSPENSION_TIMESTAMP, $currentMillis );
        }
    }
    
    // Check if the app_secret matches for the app_id
    //echo $e_app_secret . "," . $v_app_secret;
    if( ($e_app_secret !== $v_app_secret) ){        
        // Increase the failure_attempt_count
        $failure_attempt_count++;
        setAppMetaValue( $e_app_id, _APP_VERIFICATION_FAILURE_ATTEMPT, $failure_attempt_count );
        setAppMetaValue( $e_app_id, _APP_VERIFICATION_FAILURE_SUSPENSION_TIMESTAMP, $currentMillis );
        
        send_json_mime_type_header();
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "App authentication failed attempt $failure_attempt_count/" . _APP_VERIFICATION_FAILURE_MAX_ATTEMPTS );
        return;
    }
    
    // Check if access_token has already been created for this app which is not expired
    $response = array();
    $sql = "SELECT rt.*, at.app_id FROM refresh_tokens rt, app_tokens at "
            . "WHERE (rt.token_id = at.token_id) AND (at.app_id='$e_app_id') AND (rt.is_expired=0) "
            . "ORDER BY rt.token_id DESC";
    $result_set = selectQuery( $sql );
    if( mysqli_num_rows( $result_set ) > 0 ){
        // Fetch the latest token row
        $val1 = mysqli_fetch_object( $result_set );
        $access_token   = $val1->access_token;
        $token          = $val1->token;             // Needs to be decrypted using the private key
        $refresh_token  = decryptData( $v_private_key, $token );
        
        //echo "Access Token Valid: " . intval( isValidJWT( $access_token ) );
        //echo ",Access Token Expired: " . intval( isAccessTokenExpired( $access_token ) );
        //echo ",";
        //echo "Refresh Token Valid: " . intval( isValidJWT( $refresh_token ) );
        //echo ",Refresh Token Expired: " . intval( isAccessTokenExpired( $refresh_token ) );
        //echo ",";
        
        // Check if the access_token is valid or expired or close to expiry, then generate new tokens
        if( isValidJWT( $access_token ) && !isAccessTokenExpired( $access_token ) ){
            //echo "here";
            // Check if the refresh_token is valid or expired or close to expiry, then generate new tokens
            if( isValidJWT( $refresh_token ) && !isAccessTokenExpired( $refresh_token ) ){
                $response[ 'access_token' ] = $access_token;
                $response[ 'refresh_token' ] = $token;
                $response[ 'message' ] = "Tokens have been generated";

                send_json_mime_type_header();
                echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $response );
                return;
            }
            else{
                // Set the tokens as expired
                setRefreshTokenAsExpiredInDB( $token );
            }
            
        }
        else{
            // Set the tokens as expired
            setAccessTokenAsExpiredInDB( $access_token );
        }        
    }
    
    // Generate an access token
    $access_token_payload = array(
        SESSION_AUTHORIZATION => $v_role_id,
        "app_id" => $e_app_id,
        'iat' => $currentMillis,
        'exp' => _APP_ACCESS_TOKEN_EXPIRY_DURATION
    );
    $refresh_token_payload = array(
        "type" => "app",
        'id' => $e_app_id,
        'iat' => currentTimeMilliseconds(),
        'exp' => _APP_REFRESH_TOKEN_EXPIRY_DURATION
    );
    $jwt = createStandardJWT( $access_token_payload );
    //echo $value[ 'public_key' ];
    $rjwt = createStandardRefreshToken( $val->public_key, $refresh_token_payload );
    
    // Create a DB entry for app_tokens and refresh_tokens
    $tokenID = storeTokenInDB( $rjwt, _APP_REFRESH_TOKEN_EXPIRY_DURATION, $jwt, 0 );
    if( $tokenID !== FALSE ){
        storeAppIDTokenIDPair( $e_app_id, $tokenID );
    }
    
    $response[ 'access_token' ] = $jwt;
    $response[ 'refresh_token' ] = $rjwt;
    $response[ 'message' ] = "Tokens have been generated";
    
    send_json_mime_type_header();
    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $response );
    return;
    
    
}