<?php
    require './load-all.php';
    //print_r( $_REQUEST );
    //display_php_errors();
    
    @$what_do_you_want = $_REQUEST[ 'what_do_you_want' ];
    // print_r( $_SESSION );
    // For command Line Input for webservice
    if( ( $what_do_you_want == "" ) || ( $what_do_you_want == NULL ) ){
        $what_do_you_want = @$argv[ 1 ];
    }
    // Do the Logging function here

    /*
     * Funcitons Performed By Webservice
     *
     */
    $webservice_fns = array();
    $special_fns = array();
    $sql = "SELECT functionality_name FROM functionalities";
    $result_set = selectQuery( $sql );
    while( ( $val = mysqli_fetch_object( $result_set ) ) != NULL ){
        array_push( $webservice_fns, $val->functionality_name );
    }
    array_push( $webservice_fns, "login" );
    array_push( $webservice_fns, "scodezy_login" );
    array_push( $webservice_fns, "logout" );
    array_push( $webservice_fns, "get_all_validation_constants" );
    array_push( $webservice_fns, "scodezy_validate_user_session" );
    array_push( $webservice_fns, "scodezy_extend_session" );
    array_push( $webservice_fns, "scodezy_sign_out" );
    array_push( $webservice_fns, "scodezy_get_new_access_token" );
    array_push( $webservice_fns, "scodezy_verify_self_email_update" );
    array_push( $webservice_fns, "scodezy_authenticate_app" );
    array_push( $webservice_fns, "install_shopify_app" );
    array_push( $webservice_fns, "shopify_webhook_response" );
    array_push( $webservice_fns, "test_hmac" );
    array_push( $webservice_fns, "simulate_shopify_webhook_response" );
    array_push( $webservice_fns, "test_quickbooks_api" );
    array_push( $webservice_fns, "callback_quickbooks_api" );
    array_push( $webservice_fns, "authenticate_quickbooks_app" );
    array_push( $webservice_fns, "generate_quickbooks_tokens" );
    array_push( $webservice_fns, "test_quickbooks_api_call" );
    array_push( $webservice_fns, "refresh_tokens_before_they_expire_quickbooks" );
    array_push( $webservice_fns, "test_sparse_update_invoice" );
    //array_push( $webservice_fns, "generate_shopify_app_token" );
    
    array_push( $special_fns, "login" );
    array_push( $special_fns, "scodezy_login" );
    array_push( $special_fns, "logout" );
    array_push( $special_fns, "get_all_validation_constants" );
    array_push( $special_fns, "scodezy_validate_user_session" );
    array_push( $special_fns, "scodezy_extend_session" );
    array_push( $special_fns, "scodezy_sign_out" );
    array_push( $special_fns, "scodezy_get_new_access_token" );
    array_push( $special_fns, "scodezy_verify_self_email_update" );
    array_push( $special_fns, "scodezy_authenticate_app" );
    array_push( $special_fns, "install_shopify_app" );
    array_push( $special_fns, "shopify_webhook_response" );
    array_push( $special_fns, "test_hmac" );
    array_push( $special_fns, "simulate_shopify_webhook_response" );
    array_push( $special_fns, "test_quickbooks_api" );
    array_push( $special_fns, "callback_quickbooks_api" );
    array_push( $special_fns, "authenticate_quickbooks_app" );
    array_push( $special_fns, "generate_quickbooks_tokens" );
    array_push( $special_fns, "test_quickbooks_api_call" );
    array_push( $special_fns, "refresh_tokens_before_they_expire_quickbooks" );
    array_push( $special_fns, "test_sparse_update_invoice" );
    //array_push( $special_fns, "generate_shopify_app_token" );

    $pluginsDirPath = PLU_PATH . "/";
    $pluginsDirectory = scandir( $pluginsDirPath );
    $includedDirectories = array( "webservice" );
    for( $i = 0 ; $i < count( $pluginsDirectory ) ; $i++ ){
        if( ($pluginsDirectory[ $i ] == "..") || ($pluginsDirectory[ $i ] == ".") ){
            continue;
        }

        if( is_dir( $pluginsDirPath . $pluginsDirectory[ $i ] ) ){
            $pluginsDirContent = scandir( $pluginsDirPath . $pluginsDirectory[ $i ] );        
            foreach ( $includedDirectories as $dirToInclude ) {
                if( in_array( $dirToInclude, $pluginsDirContent ) ){
                    $dirPath = $pluginsDirPath . $pluginsDirectory[ $i ] . FILE_SEPARATOR . $dirToInclude . FILE_SEPARATOR;
                    //echo $dirPath . "\n<br />";
                    if( is_dir( $dirPath ) ){
                        $phpFiles = scandir( $dirPath );
                        for( $j = 0 ; $j < count( $phpFiles ) ; $j++ ){
                            $path = $dirPath . $phpFiles[ $j ];
                            if( is_dir( $path ) || str_ends_with( $path, "backup" ) ){
                                continue;
                            }
                            include_once $dirPath . $phpFiles[ $j ];
                        }
                    }
                }
            }
        }
    }
    
    // Fetch Tokens into 2 global unique variables to be used across the entire webservice.php files
    $globalAccessToken  = NULL;
    $globalRefreshToken = NULL;
    if( isset( $_COOKIE[ TOKEN_NAME ] ) ){
        $globalAccessToken = $_COOKIE[ TOKEN_NAME ];
    }
    else if( isset( $_REQUEST[ TOKEN_NAME ] ) ){
        $globalAccessToken = $_REQUEST[ TOKEN_NAME ];
    }
    if( isset( $_COOKIE[ REFRESH_TOKEN_NAME ] ) ){
        $globalRefreshToken = $_COOKIE[ REFRESH_TOKEN_NAME ];
    }
    else if( isset( $_REQUEST[ REFRESH_TOKEN_NAME ] ) ){
        $globalRefreshToken = $_REQUEST[ REFRESH_TOKEN_NAME ];
    }
    

    // Skip Token checking for the following API calls
    //print_r( $special_fns );
    if( !in_array( $what_do_you_want, $special_fns ) ){
        // Check Tokens Here
        //if( !isset( $_COOKIE[ TOKEN_NAME ] ) ){
        if( $globalAccessToken === NULL ){
            echo createJSONMessage( GENERAL_ERROR_MESSAGE, "webservice", "You are not allowed to use the API !!" );
            return;
        }
        //$jwt = $_COOKIE[ TOKEN_NAME ];
        if( !isValidJWT( $globalAccessToken ) ){
            echo createJSONMessage( GENERAL_ERROR_MESSAGE, "webservice", "Your access token is either expired or invalid !" );
            return;
        }
        if( isAccessTokenExpired( $globalAccessToken ) ){
            echo createJSONMessage( GENERAL_TOKEN_MESSAGE, "webservice", "Your access token has been expired !" );
            return;
        }
    }

    //print_r( $_REQUEST );
    if( ( $what_do_you_want == '' ) || ( $what_do_you_want == NULL ) ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, "webservice", "Webservice does not perform such an action, Sorry !!" );
    }
    else if( in_array( $what_do_you_want, $webservice_fns ) ){
        $what_do_you_want();
    }
    else{
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, "webservice", "Webservice does not perform such an action, Sorry !!" );
    }
	
    
    /* 1 */
    function login(){
        $user_id        = $_REQUEST[ 'user_id' ];
        $password       = $_REQUEST[ 'password' ];

        validateEmptyString( $user_id, __FUNCTION__, "User ID is required to sign in !" );
        validateEmptyString( $password, __FUNCTION__, "Password is required to sign in !" );
        
        validate( $user_id, __FUNCTION__, getValidationRegex( "VLDTN_USER_ID" ), "User ID entered is invalid !" );
        validate( $password, __FUNCTION__, getValidationRegex( "VLDTN_PASSWORD" ), "Password entered is invalid !" );
        
        $e_user_id      = escape_string( $user_id );
        $e_password     = escape_string( $password );
        
        $hash_password  = hashPassword( $e_password ); //password_hash( $e_password, PASSWORD_BCRYPT );    // Blowfish

        
        if( mysqli_num_rows( $result_set ) == 0 ){
            echo createJSONMessage( GENERAL_ERROR_MESSAGE, "login", "Invalid username/password" );
            return;
        }
        
        $value = mysqli_fetch_assoc( $result_set );
        if( ( $value[ 'user_id' ] == $e_user_id ) && ( password_verify( $value[ 'password' ], $hash_password ) ) ){
            
            $_SESSION[ SESSION_AUTHORIZATION ] = $value[ "role_id" ];
            $_SESSION[ SESSION_USER_ID ] = $user_id;
            $_SESSION[ 'fname' ] = $value[ 'fname' ];
            $_SESSION[ 'lname' ] = $value[ 'lname' ];
            $_SESSION[ 'nickname' ] = $value[ 'nickname' ];
            $_SESSION[ 'email' ] = $value[ 'email' ];

            // Generate a session token

            //$_SESSION[ SESSION_ROLE_ID ] = $value[ 'role_id' ];
            
            $data = array(
                "adminpanel_url" => WEBSITE_ADMINPANEL_URL,
                "info" => "You have been logged in successfully !"
            );

            echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $data );
            return;
        }
        else{
            echo createJSONMessage( GENERAL_ERROR_MESSAGE, "login", "Invalid username/password" );
            return;
        }
        
    }
    
    function scodezy_login(){
        $user_id        = @$_REQUEST[ 'user_id' ];
        $password       = @$_REQUEST[ 'password' ];

        validateEmptyString( $user_id, __FUNCTION__, "User ID is required to sign in !" );
        validateEmptyString( $password, __FUNCTION__, "Password is required to sign in !" );
        
        validate( $user_id, __FUNCTION__, getValidationRegex( "VLDTN_USER_ID" ), "User ID entered is invalid !" );
        validate( $password, __FUNCTION__, getValidationRegex( "VLDTN_PASSWORD" ), "Password entered is invalid !" );
        
        $e_user_id      = escape_string( $user_id );
        $e_password     = escape_string( $password );
        
        // Check if the user_id is activated in user_meta table
        $activation_status = getUserMetaValue( $e_user_id, _NEW_ACCOUNT_ACTIVATION_STATUS );
        if( $activation_status !== NULL ){
            if( $activation_status == _NEW_ACCOUNT_ACTIVATION_STATUS_PENDING_ACTIVATION ){
                echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Your account is pending activation. Kindly verify and activate your account by using the verification link sent to your registered Email ID" );
                return;
            }
        }
        
        
        $sql = "Select * from users where user_id='$e_user_id'";
        $result_set = selectQuery( $sql );
        if( mysqli_num_rows( $result_set ) == 0 ){
            echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Invalid username/password !" );
            return;
        }
        
        $value = mysqli_fetch_assoc( $result_set );
        
        if( ( $value[ 'user_id' ] == $e_user_id ) && ( password_verify( $e_password, $value[ 'password' ] ) ) ){
            //sessionRevalidate( true );
            /*
            @session_regenerate_id();
            
            $_SESSION[ SESSION_AUTHORIZATION ] = $value[ "role_id" ];
            $_SESSION[ SESSION_USER_ID ] = $user_id;
            $_SESSION[ SESSION_FIRST_NAME ] = $value[ 'fname' ];
            $_SESSION[ SESSION_LAST_NAME ] = $value[ 'lname' ];
            $_SESSION[ SESSION_ID_GENERATE_TIMESTAMP ] = currentTimeMilliseconds();
            $_SESSION[ 'HTTP_USER_AGENT' ] = $_SERVER[ 'HTTP_USER_AGENT' ];
            $_SESSION[ SESSION_NICK_NAME ] = $value[ 'nickname' ];
            $_SESSION[ SESSION_EMAIL ] = $value[ 'email' ];
             * 
             */
            
            // Generate a session token
            $payload = array(
                SESSION_AUTHORIZATION => $value[ "role_id" ],
                SESSION_USER_ID => $user_id,
                SESSION_FIRST_NAME => $value[ 'fname' ],
                SESSION_LAST_NAME => $value[ 'lname' ],
                SESSION_NICK_NAME => $value[ 'nickname' ],
                SESSION_EMAIL => $value[ 'email' ],
                'HTTP_USER_AGENT' => $_SERVER[ 'HTTP_USER_AGENT' ],
                'iat' => currentTimeMilliseconds(),
                'exp' => TOKEN_EXPIRY_DURATION
            );
            $refresh_payload = array(
                "type" => "user",
                'id' => $user_id,
                'iat' => currentTimeMilliseconds(),
                'exp' => REFRESH_TOKEN_EXPIRY
            );
            $jwt = createStandardJWT( $payload );
            //echo $value[ 'public_key' ];
            $rjwt = createStandardRefreshToken( $value[ 'public_key' ], $refresh_payload );
            setcookie( TOKEN_NAME, $jwt, 0, "/" );
            //setcookie( REFRESH_TOKEN_NAME, $rjwt );
            
            // Create a DB entry for app_tokens and refresh_tokens
            $tokenID = storeTokenInDB( $rjwt, REFRESH_TOKEN_EXPIRY, $jwt, 0 );
            if( $tokenID !== FALSE ){
                storeUserIDTokenIDPair( $e_user_id, $tokenID );
            }

            //$_SESSION[ SESSION_ROLE_ID ] = $value[ 'role_id' ];
            
            $data = array(
                "adminpanel_url" => WEBSITE_ADMINPANEL_URL,
                "token_name" => TOKEN_NAME,
                "refresh_token_name" => REFRESH_TOKEN_NAME,
                TOKEN_NAME => $jwt,
                REFRESH_TOKEN_NAME => $rjwt,
                "info" => "You have been signed in successfully !"
            );

            echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $data );
            return;
        }
        else{
            echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Invalid username/password" );
            return;
        }
        
    }
    
    function scodezy_get_new_access_token(){
        $jwt = $_COOKIE[ TOKEN_NAME ];
        //print_r($jwt);
        //$rjwt = request( REFRESH_TOKEN_NAME );
        $rjwt = request( 'refresh_token' );
        //echo $jwt;
        
        if( $rjwt === NULL ){
            echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Refresh token is required !" );
            return;
        }
        
        // Validate the Access Token for its genuineness/checksum
        if( !isValidJWT( $jwt ) ){
            echo createJSONMessage( GENERAL_ERROR_MESSAGE, "webservice", "Access token is invalid !" );
            return;
        }
        
        $e_rjwt = escape_string( $rjwt );
        
        // Check if the Refresh Token exist in the DB table refresh_tokens
        $sql = "SELECT * FROM refresh_tokens WHERE token='$e_rjwt'";
        $result_set = selectQuery( $sql );
        if( mysqli_num_rows( $result_set ) == 0 ){
            // Sign Out the user if the Refresh Token does not exist in the DB
            scodezy_sign_out();
            return;
        }
        
        // When the Refresh Token is found in the DB, check if it has been set as is_expired=1
        $val = mysqli_fetch_object( $result_set );
        $is_expired = $val->is_expired;
        // If the Refresh Token has been set as is_expired=1, 
        // It means that someone is trying to use an expired token, in this case, set the entire chain of linked Refresh Tokens with this user_id to is_expired=1
        if( $is_expired === "1" ){
            // Get token_id of this Refresh Token
            $token_id = $val->token_id;
            $sql = "SELECT user_id FROM user_tokens WHERE token_id='$token_id'";
            $result_set = selectQuery( $sql );
            if( mysqli_num_rows( $result_set ) == 0 ){
                //echo "here";
                // Something went wrong
                scodezy_sign_out();
                exit();
            }
            $val = mysqli_fetch_object( $result_set );
            $user_id = $val->user_id;
            
            // Get all token_id from user_tokens for $user_id
            $sql = "SELECT * FROM user_tokens WHERE user_id='$user_id'";
            //echo $sql;
            $result_set = selectQuery( $sql );
            $tokenIDs = "";
            while( ( $val = mysqli_fetch_object( $result_set ) ) !== NULL ){
                $tokenIDs .= $val->token_id . ',';
            }
            $tokenIDs = rtrim( $tokenIDs, "," );
            //echo "aa: " . $tokenIDs;
            // For all the $tokenIDs, set is_expired=1 in the refresh_tokens table
            if( $tokenIDs !== "" ){
                $sql = "UPDATE refresh_tokens SET is_expired='1' WHERE token_id IN ($tokenIDs)";
                updateQuery( $sql );
            }
            
            // Sign out the user, unset all cookies and clear localStorage on front end
            scodezy_sign_out();
            
            /*
            $sql = "UPDATE refresh_tokens SET is_expired='1' WHERE token='$rjwt'";
            updateQuery( $sql );
            scodezy_sign_out();
             * 
             */
            exit();
        }
        
        // Refresh Token exist in the DB and is_expired=0
        $payload = getJWTPayload( $jwt );
        // Retrieve the user_id from the JWT
        $user_id = $payload[ 'user_id' ];
        
        // Decrypt the Refresh Token
        $sql = "SELECT public_key, private_key FROM users WHERE user_id='$user_id'";
        $result_set = selectQuery( $sql );
        if( mysqli_num_rows( $result_set ) == 0 ){
            //echo createJSONMessage( GENERAL_ERROR_MESSAGE, "webservice", "Refresh token is invalid !" );
            scodezy_sign_out();
            return;
        }
        $val = mysqli_fetch_object( $result_set );
        $private_key = $val->private_key;
        $public_key = $val->public_key;
        $decryptedRefreshToken = decryptData( $private_key, $rjwt );
        
        // Check if the Refresh Token is expired (using its iat and exp)
        if( isAccessTokenExpired( $decryptedRefreshToken ) ){
            $sql = "UPDATE refresh_tokens SET is_expired='1' WHERE token='$rjwt'";
            updateQuery( $sql );
            scodezy_sign_out();
            return;
        }
        // Refresh token is not expired (using its iat and exp)
        // Create new access token and new refresh token
        $new_payload = array(
            SESSION_AUTHORIZATION => $payload[ SESSION_AUTHORIZATION ],
            SESSION_USER_ID => $user_id,
            SESSION_FIRST_NAME => $payload[ 'fname' ],
            SESSION_LAST_NAME => $payload[ 'lname' ],
            SESSION_NICK_NAME => $payload[ 'nickname' ],
            SESSION_EMAIL => $payload[ 'email' ],
            'HTTP_USER_AGENT' => $_SERVER[ 'HTTP_USER_AGENT' ],
            'iat' => currentTimeMilliseconds(),
            'exp' => TOKEN_EXPIRY_DURATION
        );
        $new_refresh_payload = array(
            "type" => "user",
            'id' => $user_id,
            'iat' => currentTimeMilliseconds(),
            'exp' => REFRESH_TOKEN_EXPIRY
        );
        $new_jwt = createStandardJWT( $new_payload );
        //echo $value[ 'public_key' ];
        $new_rjwt = createStandardRefreshToken( $public_key, $new_refresh_payload );

        // Create a DB entry for app_tokens and refresh_tokens
        $tokenID = storeTokenInDB( $new_rjwt, REFRESH_TOKEN_EXPIRY, $new_jwt, 0 );
        if( $tokenID !== FALSE ){
            storeUserIDTokenIDPair( $user_id, $tokenID );
        }

        setcookie( TOKEN_NAME, "", time()-3600, "/" );
        //setcookie( REFRESH_TOKEN_NAME, "", time()-3600, "/" );
        setcookie( TOKEN_NAME, "", 1, "/");     // or we can try this too to unset the cookie
        unset( $_COOKIE[ TOKEN_NAME ] );
        //unset( $_COOKIE[ REFRESH_TOKEN_NAME ] );

        setcookie( TOKEN_NAME, $new_jwt, 0, "/" );
        //setcookie( REFRESH_TOKEN_NAME, $new_rjwt );
        
        // Update previous refresh token in DB is_expired=1
        $sql = "UPDATE refresh_tokens SET is_expired='1' WHERE token='$rjwt'";
        updateQuery( $sql );
        
        $data = array(
            "adminpanel_url" => WEBSITE_ADMINPANEL_URL,
            "token_name" => TOKEN_NAME,
            "refresh_token_name" => REFRESH_TOKEN_NAME,
            TOKEN_NAME => $jwt,
            REFRESH_TOKEN_NAME => $new_rjwt,
            "info" => "Your token has been refreshed !"
        );
        
        return $data;
        
    }
	
    /* 2 */
    function logout(){
        //sessionRevalidate( true );
        @session_destroy();
        @session_start();
        session_regenerate_id();
        //echo "here:";
        //print_r( $_SESSION );
        redirect( PAGE_LOGIN );
    }

    /* 3 */
    function get_all_validation_constants(){
        getAllValidationConstants();
    }
    
    function scodezy_validate_user_session(){
        $data = array(
            "login_url" => "login.php",
            "session_status" => "1",
            "info" => "Session is active"
        );
        
        // If the user has been signed out in another tab, then no choice, we have to forcefully sign them out
        if( !isLoggedIn() ){
            $data[ 'session_status' ] = "0";
            $data[ 'info' ] = "Your session has been timed out. Kindly sign in again to continue using the system";
            echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $data );
            return;
        }
        
        // Check if less than 180 seconds are remaining for logout
        $jwt = $_COOKIE[ TOKEN_NAME ];
        $jwtData = getJWTPayload( $jwt );
        $iat = $jwtData[ 'iat' ];
        $exp = $jwtData[ 'exp' ];
        $currentMillis = currentTimeMilliseconds();
        $expires_at = $iat + $exp;
        
        $difference = $expires_at - $currentMillis;
        if( $difference <= 180000 ){
            $data[ 'session_status' ] = "1";
            //$data[ 'info' ] = "Your session is going to expire soon. Do you want to continue to use the system ? " . ($check_millis/1000) . " - " . ($difference/1000);
            $data[ 'info' ] = "Your session is going to expire in <b></b>. Do you want to continue to use the system ? ";
            $data[ 'remaining_time' ] = $difference;
            echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $data );
            return;
        }
        
        $data[ 'session_status' ] = "2";
        $data[ 'info' ] = "Your session is active $difference";

        echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $data );
    }
    
    
    function scodezy_validate_user_session1(){
        $data = array(
            "login_url" => "login.php",
            "session_status" => "1",
            "info" => "Session is active"
        );
        
        // If the user has been signed out in another tab, then no choice, we have to forcefully sign them out
        if( !isLoggedIn() ){
            $data[ 'session_status' ] = "0";
            $data[ 'info' ] = "Your session has been timed out. Kindly sign in again to continue using the system";
            echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $data );
            return;
        }
        
        // Check if Auto-Logout timeout is coming near to its expiry
        $session_timestamp = $_SESSION[ SESSION_ID_GENERATE_TIMESTAMP ];
        $currentMillis = currentTimeMilliseconds();
        $difference = $currentMillis - $session_timestamp; 
        
        $check_millis = intval( SESSION_EXPIRY_DURATION ) - 180000;     // Show warning to user 3 minutes prior to actual expiry of the session, so the user can take action
        if( ($difference > 0) && ($difference >= $check_millis) ){
            $data[ 'session_status' ] = "1";
            //$data[ 'info' ] = "Your session is going to expire soon. Do you want to continue to use the system ? " . ($check_millis/1000) . " - " . ($difference/1000);
            $data[ 'info' ] = "Your session is going to expire in <b></b>. Do you want to continue to use the system ? ";
            $data[ 'remaining_time' ] = $check_millis;
            echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $data );
            return;
        }
            
        $data[ 'session_status' ] = "2";
        $data[ 'info' ] = "Your session is active $difference";

        echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $data );
    }
    
    // Token based time extension
    function scodezy_extend_session(){
        $data = scodezy_get_new_access_token();
        $data[ 'info' ] = "Session has been extended ";
        
        echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $data );
    }
    
    // Session based time extension
    function scodezy_extend_session1(){
        @session_start();
        
        @session_regenerate_id();
        $_SESSION[ SESSION_ID_GENERATE_TIMESTAMP ]  = currentTimeMilliseconds();
        
        echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, "Session has been extended " );
    }
    
    
    
    // API Call for sign out
    function scodezy_sign_out(){
        //sessionRevalidate( true );
        //@session_destroy();
        //@session_start();
        //session_regenerate_id();
        sign_out();
        
        
        //echo "here:";
        //print_r( $_SESSION );
        $data = array(
            "login_url" => "login.php",
            "info" => "Signed out successfully"
        );
        echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $data );
    }
?>