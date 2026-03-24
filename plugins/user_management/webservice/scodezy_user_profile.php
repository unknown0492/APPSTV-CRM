<?php

function scodezy_get_self_profile_settings(){
    //print_r( $_REQUEST );
    /*
    if( !isLoggedIn() ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Your session has been expired. Please sign out and sign in again" );
        return;
    }
    */
    checkAuthorizationForFunction( __FUNCTION__ );
    
    $jwt = $_COOKIE[ TOKEN_NAME ];
    $payload = getJWTPayload( $jwt );
    
    $user_id = $payload[ SESSION_USER_ID ];
    
    $sql = "SELECT user_id, fname, lname, nickname, email, country_code, phone FROM users WHERE user_id='$user_id'";
    $result_set = selectQuery( $sql );
    if( mysqli_num_rows( $result_set ) == 0 ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Something went wrong. Please contact the administrator" );
        return;
    }
    
    $userData = mysqli_fetch_assoc( $result_set );
    
    $sql = "SELECT * FROM user_meta WHERE (user_id='$user_id') AND (umeta_key IN ( '"._EMAIL_UPDATE_VERIFICATION_STATUS."', '"._EMAIL_UPDATE_NEW_EMAIL."' ))";
    $result_set = selectQuery( $sql );
    if( $result_set !== NULL ){
        if( mysqli_num_rows( $result_set ) > 0 ){
            while( ($val = mysqli_fetch_object( $result_set ) ) != NULL ){
                $userData[ $val->umeta_key ] = $val->umeta_value;
            }
        }
    }
    
    $data = array(
        "info" => "User Profile settings have been retrieved",
        "data" => $userData
    );
    // $data = $userData;
    
    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $data );
    
}


function scodezy_update_self_profile_details(){
    checkAuthorizationForFunction( __FUNCTION__ );
    
    $user_id            = request( 'user_id' );
    $first_name         = request( 'fname' );
    $last_name          = request( 'lname' );
    $nick_name          = request( 'nickname' );
    
    // Validations for emptiness
    validateEmptyString( $user_id, __FUNCTION__, "User ID is required !");
    validateEmptyString( $first_name, __FUNCTION__, "First Name is required !");
    validateEmptyString( $last_name, __FUNCTION__, "Last Name is required !");
    validateEmptyString( $nick_name, __FUNCTION__, "Nickname is required !");
    
    // Validations for REGEX
    validate( $user_id, __FUNCTION__, getValidationRegex( "VLDTN_USER_ID" ), "User ID is invalid" );
    validate( $first_name, __FUNCTION__, getValidationRegex( "VLDTN_FIRST_NAME" ), getValidationErrMsg( "VLDTN_FIRST_NAME" ) );
    validate( $last_name, __FUNCTION__, getValidationRegex( "VLDTN_LAST_NAME" ), getValidationErrMsg( "VLDTN_LAST_NAME" ) );
    validate( $nick_name, __FUNCTION__, getValidationRegex( "VLDTN_LAST_NAME" ), getValidationErrMsg( "VLDTN_NICK_NAME" ) );
    
    // Escape dangerous database characters
    $e_user_id          = escape_string( $user_id );
    $e_first_name       = escape_string( $first_name );
    $e_last_name        = escape_string( $last_name );
    $e_nick_name        = escape_string( $nick_name );
    
    
    // Check if the User ID is valid and exists
    $sql = "SELECT user_id FROM users WHERE user_id = '$e_user_id'";
    $result_set = selectQuery( $sql );
    if ( $result_set != NULL ) {
        if ( mysqli_num_rows( $result_set ) == 0 ) {
            echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "This user does not exist in the system ! ");
            return;
        }
    }
    
    // Update the information in the Database
    $sql = "UPDATE users SET "
            . "fname='$e_first_name', "
            . "lname='$e_last_name', "
            . "nickname='$e_nick_name' "
            . "WHERE user_id='$e_user_id'";
    $rows = updateQuery( $sql );
    if( $rows > 0 ){
        $sql = "SELECT user_id, fname, lname, nickname, email FROM users WHERE user_id='$user_id'";
        $result_set = selectQuery( $sql );
        if( mysqli_num_rows( $result_set ) == 0 ){
            echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Something went wrong. Please contact the administrator" );
            return;
        }

        $userData = mysqli_fetch_assoc( $result_set );
        $data = array(
            "info" => "Profile details have been updated",
            "data" => $userData
        );
        echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__,$data );
        return;
    }
}

function scodezy_update_self_email(){
    checkAuthorizationForFunction( __FUNCTION__ );
    
    //print_r( $_REQUEST );
    $user_id            = request( 'user_id' );
    $password       = request( 'password' );
    $email       = request( 'email' );
    
    // Validations for emptiness
    validateEmptyString( $user_id, __FUNCTION__, "User ID is required !");
    validateEmptyString( $password, __FUNCTION__, "Password is required !" );
    validateEmptyString( $email, __FUNCTION__, "Email is required !" );
    
    // Validations for REGEX
    validate( $user_id, __FUNCTION__, getValidationRegex( "VLDTN_USER_ID" ), "User ID is invalid" );
    validate( $password, __FUNCTION__, getValidationRegex( "VLDTN_PASSWORD" ), "Password entered is invalid !" );
    validate( $email, __FUNCTION__, getValidationRegex( "VLDTN_EMAIL" ), "Email entered is invalid !" );
    
    // Escape dangerous database characters
    $e_user_id          = escape_string( $user_id );
    $e_password     = escape_string( $password );
    $e_email     = escape_string( $email );
    
    // New email should not belong to someone else
    // Check if the Email is already associated with another User ID
    $sql = "SELECT * FROM users WHERE email='$e_email'";
    $result_set = selectQuery( $sql );
    if ( $result_set != NULL ) {
        if ( mysqli_num_rows( $result_set ) > 0 ) {
            echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "This email is already associated with another account");
            return;
        }
    }
    
    
    $sql = "Select * from users where user_id='$e_user_id'";
    $result_set = selectQuery( $sql );
    if( mysqli_num_rows( $result_set ) == 0 ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Authentication Failure !" );
        return;
    }

    $value = mysqli_fetch_assoc( $result_set );
    $existing_email = $value[ 'email' ];

    if( ( $value[ 'user_id' ] == $e_user_id ) && ( password_verify( $e_password, $value[ 'password' ] ) ) ){
        // Send email for verification
        $siteConfig = getSiteConfig();
        
        // Generate an email verification string, store it in the user_meta table
        $timestamp = currentTimeMilliseconds();
        $accountActivationString = sha1( $timestamp );
        setUserMetaValue( $e_user_id, _EMAIL_UPDATE_NEW_EMAIL, $e_email );
        setUserMetaValue( $e_user_id, _EMAIL_UPDATE_VERIFICATION_CODE, $accountActivationString );
        setUserMetaValue( $e_user_id, _EMAIL_UPDATE_VERIFICATION_CODE_GENERATION_TIMESTAMP, $timestamp );
        setUserMetaValue( $e_user_id, _EMAIL_UPDATE_VERIFICATION_STATUS, _EMAIL_UPDATE_VERIFICATION_STATUS_DEFAULT );

        // Generate a private_link for account activation
        $real_link = WEBSITE_PROTOCOL . '://' . $siteConfig->domain_name . "/" . WEBSERVICE_URL . "?what_do_you_want=scodezy_verify_self_email_update&email_verification_code=$accountActivationString&user_id=$e_user_id";
        $private_link = generatePrivateLink( $real_link, "Email Verification Link", 'page', true, 'auto' );
        //echo $private_link;

        $mail = sendMailObject();
        $mail->isHTML( true );
        $mail->setFrom( EMAIL_NOREPLY, 'Email Update | ' . $siteConfig->site_name );
        $mail->AddAddress( $email );
        $mail->Subject = 'Please confirm the update of the email address associated with your account';

        //$private_link = "xxx";
        
        $message = file_get_contents("templates/email/email_update_on_account.php");
        $message = str_replace("{{user_id}}", $user_id, $message);
        $message = str_replace("{{verification_link}}", $private_link, $message );
        
        //echo $message;
        
        $mail->Body = $message;

        if ( !$mail->Send() ) {
            $responseMessage = "Some error occurred, please report to the administrator";

            // Log here about email not sending out for credentials
            
            echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, $responseMessage );
            return;
        }
        else{
            $responseMessage = "A verification link has been sent to `$e_email` with the procedure to update the email associated with your account. Note: Please check your spam/junk in case you do not find the email in your inbox";
            $userMeta = array(
                //_EMAIL_UPDATE_VERIFICATION_STATUS => _EMAIL_UPDATE_VERIFICATION_STATUS_DEFAULT,
                $existing_email => $e_email
            );
            
            $data = array(
                "info" => $responseMessage,
                "data" => $userMeta
            );
            echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $data );
            return;
        }
        
    }
    else{
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "You have entered incorrect password for your account" );
        return;
    }
    
}

function scodezy_verify_self_email_update(){
    checkAuthorizationForFunction( __FUNCTION__ );
    
    $verification_code  = request( 'email_verification_code' );
    $user_id            = request( 'user_id' );
    
    // Escape the code to make it database-safe
    $e_verification_code  = escape_string( $verification_code );
    $e_user_id            = escape_string( $user_id );
    
    // Check if the email_verification_code exist for this user_id
    $sql = "SELECT * FROM user_meta WHERE (user_id='$e_user_id') AND (umeta_key='"._EMAIL_UPDATE_VERIFICATION_CODE."') AND (umeta_value='$e_verification_code')";
    $result_set = selectQuery( $sql );
    if( ($result_set === NULL) || (mysqli_num_rows( $result_set ) == 0) ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "The verification link has either been expired or you have already verified your email" );
        exit();
    }
    
    // Check the if the validity of the email_verification_code is expired
    $expiryDuration = getConfigurationValue( _EMAIL_UPDATE_VERIFICATION_CODE_EXPIRY_DURATION, _EMAIL_UPDATE_VERIFICATION_CODE_EXPIRY_DURATION_DEFAULT );
    //echo $expiryDuration;
    
    // Retreive the timestamp when the Verification code was generated
    $sql = "SELECT * FROM user_meta WHERE (user_id='$e_user_id') AND (umeta_key='"._EMAIL_UPDATE_VERIFICATION_CODE_GENERATION_TIMESTAMP."')";
    //echo $sql;
    $result_set = selectQuery( $sql );
    if( ($result_set === NULL) || (mysqli_num_rows( $result_set ) == 0) ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Account activation link is either invalid or has been expired !" );
        return;
    }
    
    $val = mysqli_fetch_object( $result_set );
    $verification_code_generation_timestamp = $val->umeta_value;
    
    // Check if the verification_code still has validity
    $currentTimestamp = currentTimeMilliseconds();
    $timeDifference   = $currentTimestamp - $verification_code_generation_timestamp;
    
    if( $timeDifference > $expiryDuration ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Email verification link has been expired. Kindly initiate the email update request again !" );
        return;
    }
    
    
    // If all the above constraints have been surpassed, it means that the validation process is successful
    // Check if the new email has not yet been taken by someone else during this meantime
    // Update the email in the users table for the given user_id
    // Remove the Email Update keys from the user_meta table
    $sql = "SELECT * FROM user_meta WHERE (user_id='$e_user_id') AND (umeta_key='"._EMAIL_UPDATE_NEW_EMAIL."')";
    $result_set = selectQuery( $sql );
    if( ($result_set === NULL) || (mysqli_num_rows($result_set) == 0) ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Account activation link is either invalid or has been expired !" );
        return;
    }
    $val = mysqli_fetch_object( $result_set );
    $new_email = $val->umeta_value;
    
    $sql = "SELECT email FROM users WHERE email='$new_email'";
    //echo $sql;
    $result_set = selectQuery( $sql );
    if( ($result_set !== NULL) && (mysqli_num_rows($result_set) > 0) ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "This email is already associated with another user. You may try upating to another email account" );
        return;
    }
    
    $sql = "UPDATE users SET email='$new_email' WHERE user_id='$e_user_id'";
    $rows = updateQuery( $sql );
    if( $rows > 0 ){
        deleteUserMetaValue( $e_user_id, _EMAIL_UPDATE_NEW_EMAIL );
        deleteUserMetaValue( $e_user_id, _EMAIL_UPDATE_VERIFICATION_CODE );
        deleteUserMetaValue( $e_user_id, _EMAIL_UPDATE_VERIFICATION_CODE_GENERATION_TIMESTAMP );
        deleteUserMetaValue( $e_user_id, _EMAIL_UPDATE_VERIFICATION_STATUS );
        echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, "Your email has been successfully verified. You may sign out of the system and sign in again to see your updated email" );
        return;
    }
    
    echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Technical error occured, please report to the administrator " );
    return;
    
}

function scodezy_verify_update_self_phone_otp(){
    checkAuthorizationForFunction( __FUNCTION__ );
    
    $otp = request( 'otp' );
    
    validateEmptyDigitString( $otp, __FUNCTION__, "OTP is required" );
    
    validate( $otp, __FUNCTION__, getValidationRegex( "VLDTN_OTP_NUMERIC" ), "OTP entered is invalid" );
    
    $jwt = $_COOKIE[ TOKEN_NAME ];
    $payload = getJWTPayload( $jwt );
    
    $user_id        = $payload[ 'user_id' ];
    $currentMillis  = currentTimeMilliseconds();
    
    /*
     * 1. Check if the user_id exist in the system
     * 2. Check if the otp generation timestamp exist
     * 3. Check if the otp generation timestamp did not exceeded 5 minutes
     * 4. Check if the OTP exist in the ummeta table
     * 5. Check if the entered OTP matches with the OTP in the umeta table
     * 6. Fetch the _phone_update_new_phone and _phone_update_new_phone_country_code from the umeta table, and update it in the users table for the given user
     * 7. Update _phone_update_verification_status to 1
     * 8. Update _PHONE_UPDATE_TIMESTAMP to currentMillis
     * 9. Delete the _phone_update_otp_request_count, _phone_update_new_phone, _phone_update_new_phone_country_code, _phone_update_otp, _phone_update_otp_generation_timestamp
     */
    
    
    // 1. Check if the user_id exist in the system
    $sql = "Select * from users where user_id='$user_id'";
    $result_set = selectQuery( $sql );
    if( mysqli_num_rows( $result_set ) == 0 ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "User ID is invalid !" );
        return;
    }
    $val = mysqli_fetch_object( $result_set );
    
    // 2. Check if the otp generation timestamp exist
    $last_otp_generation_timestamp = getUserMetaValue( $user_id, _PHONE_UPDATE_OTP_GENERATION_TIMESTAMP );
    if( $last_otp_generation_timestamp === NULL ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Something went wrong, please request new OTP and try again" );
        return;
    }
    
    // 3. Check if the otp generation timestamp did not exceeded 5 minutes
    $last_otp_generation_timestamp = intval( $last_otp_generation_timestamp );
    $difference = $currentMillis - $last_otp_generation_timestamp;
    if( $difference > _PHONE_UPDATE_OTP_VALIDITY ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "The OTP has been expired, please request new OTP and try again" );
        return;
    }
    
    // 4. Check if the OTP exist in the ummeta table
    $otp_in_system = getUserMetaValue( $user_id, _PHONE_UPDATE_OTP );
    if( $otp_in_system === NULL ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "OTP has not been generated, please request an OTP and try again" );
        return;
    }
    
    // 5. Check if the entered OTP matches with the OTP in the umeta table
    $otp_in_system  = intval( $otp_in_system );
    $otp            = intval( $otp );
    
    if( $otp_in_system !== $otp ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "OTP entered is incorrect, please check if you are entering the correct OTP" );
        return;
    }
    
    // 6. Fetch the _phone_update_new_phone and _phone_update_new_phone_country_code from the umeta table, and update it in the users table for the given user
    $phone          = getUserMetaValue( $user_id, _PHONE_UPDATE_NEW_PHONE );
    $country_code   = getUserMetaValue( $user_id, _PHONE_UPDATE_NEW_PHONE_COUNTRY_CODE );
    $sql = "UPDATE users SET phone='$phone', country_code='$country_code' WHERE user_id='$user_id'";
    $rows = updateQuery( $sql );
    if( $rows === 0 ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Failed to validate the OTP. Please re enter the OTP and try again" );
        return;
    }
    
    // 7. Update _phone_update_verification_status to 1
    setUserMetaValue( $user_id, _PHONE_UPDATE_VERIFICATION_STATUS, _PHONE_UPDATE_VERIFICATION_STATUS_VERIFIED );
    
    // 8. Update _PHONE_UPDATE_TIMESTAMP to currentMillis
    setUserMetaValue( $user_id, _PHONE_UPDATE_TIMESTAMP, $currentMillis );
    
    // 9. Delete the _phone_update_otp_request_count, _phone_update_new_phone, _phone_update_new_phone_country_code, _phone_update_otp, _phone_update_otp_generation_timestamp
    deleteUserMetaValue( $user_id, _PHONE_UPDATE_OTP_REQUEST_COUNT );
    deleteUserMetaValue( $user_id, _PHONE_UPDATE_NEW_PHONE );
    deleteUserMetaValue( $user_id, _PHONE_UPDATE_NEW_PHONE_COUNTRY_CODE );
    deleteUserMetaValue( $user_id, _PHONE_UPDATE_OTP );
    deleteUserMetaValue( $user_id, _PHONE_UPDATE_OTP_GENERATION_TIMESTAMP );
    
    $data = [
        "message" => "OTP has been verified. Your mobile number has been updated successfully",
        "data" => [
            "phone" => $phone,
            "country_code" => $country_code,
            _PHONE_UPDATE_VERIFICATION_STATUS => _PHONE_UPDATE_VERIFICATION_STATUS_VERIFIED
        ]
        
    ];
    
    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $data );
}

function scodezy_update_self_phone_request_otp(){
    // For re request of OTP, the User Meta table must have last OTP generation timestamp less than 5 minutes ago
    checkAuthorizationForFunction( __FUNCTION__ );
    
    $jwt = $_COOKIE[ TOKEN_NAME ];
    $payload = getJWTPayload( $jwt );
    
    $user_id        = $payload[ 'user_id' ];
    
    $currentMillis  = currentTimeMilliseconds();
    
    // Check the existence of user_id
    $sql = "Select * from users where user_id='$user_id'";
    $result_set = selectQuery( $sql );
    if( mysqli_num_rows( $result_set ) == 0 ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "User ID is invalid !" );
        return;
    }
    $val = mysqli_fetch_object( $result_set );
    $email = $val->email;
    
    // Check the timestamp of the last generated OTP
    $lastOtpGenerationTimestamp = getUserMetaValue( $user_id, _PHONE_UPDATE_OTP_GENERATION_TIMESTAMP );
    if( $lastOtpGenerationTimestamp === NULL ){
        // This means that the OTP was not generated at all using password authentication mechanism, so throw an error
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Please update your mobile number from the profile settings page and try again" );
        return;
    }
    
    // Last OTP Generated Timestamp should be within 5 minutes
    $lastOtpGenerationTimestamp = intval( $lastOtpGenerationTimestamp );
    $difference = $currentMillis - $lastOtpGenerationTimestamp;
    if( $difference > _PHONE_UPDATE_OTP_VALIDITY ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Your request to regenrate OTP has timed out. Please update your mobile number from the profile settings page and try again" );
        return;
    }
    
    // Check if this user has already updated his phone within 30 days
    $last_phone_update_timestamp = getUserMetaValue( $user_id, _PHONE_UPDATE_TIMESTAMP );
    if( $last_phone_update_timestamp !== NULL ){
        $last_phone_update_timestamp = intval( $last_phone_update_timestamp );
        $difference = $currentMillis - $last_phone_update_timestamp;
        if( $difference < _PHONE_UPDATE_FREQUENCY_VALUE ){
            echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Your contact number was updated less than " . (_PHONE_UPDATE_FREQUENCY_VALUE/(24 * 60 * 1000)) . " days ago. You can only update your mobile number after this period" );
            return;
        }
    }
    
    // Check if the daily OTP request limit has been exhausted
    $phoneUpdateOtpRequestCount = getUserMetaValue( $user_id, _PHONE_UPDATE_OTP_REQUEST_COUNT );
    if( $phoneUpdateOtpRequestCount === NULL ){
        setUserMetaValue( $user_id, _PHONE_UPDATE_OTP_REQUEST_COUNT, 1 );     // This is the first attempt to request the OTP for phone update
    }
    else{
        $phoneUpdateOtpRequestCount = intval( $phoneUpdateOtpRequestCount );
        if( $phoneUpdateOtpRequestCount === _PHONE_UPDATE_OTP_REQUEST_COUNT_MAX_LIMIT ){
            // Check if 24 hours has passed to reset this limit
            $otpGenerationTimestamp = getUserMetaValue( $user_id, _PHONE_UPDATE_OTP_GENERATION_TIMESTAMP );
            if( $otpGenerationTimestamp !== NULL ){
                $otpGenerationTimestamp = intval( $otpGenerationTimestamp );
                $difference = $currentMillis - $otpGenerationTimestamp;
                if( $difference < _PHONE_UPDATE_OTP_REQUEST_COUNT_MAX_LIMIT_RESET_DURATION ){
                    echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "You have exhausted all the attempts to request for an OTP. Please try again after " . ceil(_PHONE_UPDATE_OTP_REQUEST_COUNT_MAX_LIMIT_RESET_DURATION/(60 * 60 * 1000)) . " hours" );
                    return;
                }
            }
            // If the OTP request limit duration has passed, then reset this limit
            setUserMetaValue( $user_id, _PHONE_UPDATE_OTP_REQUEST_COUNT, 1 );     // Reset the attempt count to 1
            deleteUserMetaValue( $user_id, _PHONE_UPDATE_OTP_GENERATION_TIMESTAMP );
        }
        else{
            $phoneUpdateOtpRequestCount++;
            setUserMetaValue( $user_id, _PHONE_UPDATE_OTP_REQUEST_COUNT, $phoneUpdateOtpRequestCount );     // Increment by 1
        }            
    }
    
    // All criteria validated, re generate the OTP and send a success message
    // Send email for verification
    $siteConfig = getSiteConfig();

    // Generate an OTP, store it in the user_meta table
    $otp            = generateOTP();

    setUserMetaValue( $user_id, _PHONE_UPDATE_OTP_GENERATION_TIMESTAMP, $currentMillis );
    setUserMetaValue( $user_id, _PHONE_UPDATE_OTP, $otp );

    $mail = sendMailObject();
    $mail->isHTML( true );
    $mail->setFrom( EMAIL_NOREPLY, 'Phone Update | ' . $siteConfig->site_name );
    $mail->AddAddress( $email );
    $mail->Subject = 'Please confirm the update of the mobile number associated with your account';

    //$message = file_get_contents("templates/email/email_update_on_account.php");
    //$message = str_replace("{{user_id}}", $user_id, $message);
    //$message = str_replace("{{verification_link}}", $private_link, $message );
    $message = "The OTP to verify your mobile number is $otp";

    $mail->Body = $message;

    if ( !$mail->Send() ) {
        $responseMessage = "Some error occurred, please report to the administrator";

        // Log here about email not sending out for credentials

        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, $responseMessage );
        return;
    }
    else{
        $responseMessage = "An OTP has been sent to `$email` with the procedure to update the mobile number associated with your account. Note: Please check your spam/junk in case you do not find the email in your inbox";
        $userMeta = array();

        $data = array(
            "info" => $responseMessage,
            "data" => $userMeta
        );
        echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $data );
        return;
    }
    
}

function scodezy_update_self_password(){
    checkAuthorizationForFunction( __FUNCTION__ );
    //print_r( $_REQUEST );
    $jwt                = $_COOKIE[ TOKEN_NAME ];
    $payload            = getJWTPayload( $jwt );
    
    $user_id            = $payload[ 'user_id' ];
    $current_password   = request( 'current_password' );
    $new_password       = request( 'new_password' );
    $confirm_password   = request( 'confirm_password' );
    
    // Validations for emptiness
    validateEmptyString( $current_password, __FUNCTION__, "Current Password is required !" );
    validateEmptyString( $new_password, __FUNCTION__, "New Password is required !" );
    validateEmptyString( $confirm_password, __FUNCTION__, "Confirm new password is required !" );
    
    // Validations for REGEX
    validate( $new_password, __FUNCTION__, getValidationRegex( "VLDTN_PASSWORD" ), getValidationErrMsg( "VLDTN_PASSWORD" ) );
    validate( $confirm_password, __FUNCTION__, getValidationRegex( "VLDTN_PASSWORD" ), getValidationErrMsg( "VLDTN_PASSWORD" ) );
    
    if( $new_password !== $confirm_password ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "New password and confirm password does not match" );
        return;
    }
    
    // Escape dangerous database characters
    $e_current_password     = escape_string( $current_password );
    $e_new_password         = escape_string( $new_password );
    $e_confirm_password     = escape_string( $confirm_password );
    
    $currentMillis  = currentTimeMilliseconds();
    
    /**
     * 1. Check if the user_id exist in the system
     * 2. Check if the entered password matches for the given user_id
     * 3. Update new password in the users table
     * 4. Update _PASSWORD_UPDATE_TIMESTAMP in user_meta table
     * 
     */
    
    // 1. Check if the user_id exist in the system
    $sql = "Select * from users where user_id='$user_id'";
    $result_set = selectQuery( $sql );
    if( mysqli_num_rows( $result_set ) == 0 ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Authentication Failure !" );
        return;
    }
    
    // 2. Check if the entered password matches for the given user_id
    $val = mysqli_fetch_assoc( $result_set );    
    if( !(( $val[ 'user_id' ] == $user_id ) && ( password_verify( $e_current_password, $val[ 'password' ] ) )) ){        
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Current password is incorrect" );
        return;
    }
    
    // 3. Update new password in the users table
    $password_hash = hashPassword( $e_new_password );
    $sql = "UPDATE users SET password='$password_hash' WHERE user_id='$user_id'";
    $rows = updateQuery( $sql );
    if( $rows == 0){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Failed to update the password. Please try again later" );
        return;
    }
    
    // 4. Update _PASSWORD_UPDATE_TIMESTAMP in user_meta table
    setUserMetaValue( $user_id, _PASSWORD_UPDATE_TIMESTAMP, $currentMillis );
        
    $data = [
        "message" => "Password changed successfully"
    ];
    
    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $data );
    
}

function scodezy_update_self_phone(){
    //print_r( $_REQUEST );
    checkAuthorizationForFunction( __FUNCTION__ );
    
    $jwt = $_COOKIE[ TOKEN_NAME ];
    $payload = getJWTPayload( $jwt );
    
    $user_id        = $payload[ 'user_id' ];
    $password       = request( 'password' );
    $country_code   = request( 'country_code' );
    $phone          = request( 'phone' );
    
    // Validations for emptiness
    validateEmptyString( $password, __FUNCTION__, "Password is required !" );
    validateEmptyDigitString( $country_code, __FUNCTION__, "Country code is required !" );
    validateEmptyDigitString( $phone, __FUNCTION__, "Mobile number is required !" );
    
    // Validations for REGEX
    //validate( $user_id, __FUNCTION__, getValidationRegex( "VLDTN_USER_ID" ), "User ID is invalid" );
    //validate( $password, __FUNCTION__, getValidationRegex( "VLDTN_PASSWORD" ), "Password entered is invalid !" );
    validate( $country_code, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), "Country code is invalid !" );
    validate( $phone, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), "Mobile number is invalid !" );
    
    // Escape dangerous database characters
    $e_country_code     = escape_string( $country_code );
    $e_phone            = escape_string( $phone );
    $e_password         = escape_string( $password );
    
    $currentMillis  = currentTimeMilliseconds();
    
    // Check the existence of user_id
    $sql = "Select * from users where user_id='$user_id'";
    $result_set = selectQuery( $sql );
    if( mysqli_num_rows( $result_set ) == 0 ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Authentication Failure !" );
        return;
    }

    $value = mysqli_fetch_assoc( $result_set );

    if( ( $value[ 'user_id' ] == $user_id ) && ( password_verify( $e_password, $value[ 'password' ] ) ) ){
        
        // Check if this user has already updated his phone within 30 days
        $last_phone_update_timestamp = getUserMetaValue( $user_id, _PHONE_UPDATE_TIMESTAMP );
        if( $last_phone_update_timestamp !== NULL ){
            $last_phone_update_timestamp = intval( $last_phone_update_timestamp );
            $difference = $currentMillis - $last_phone_update_timestamp;
            if( $difference < _PHONE_UPDATE_FREQUENCY_VALUE ){
                echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Your mobile number was updated less than " . (_PHONE_UPDATE_FREQUENCY_VALUE/(24 * 60 * 1000)) . " days ago. You can only update your mobile number after this period" );
                return;
            }
        }

        // Your existing phone and new phone should be different
        $sql = "SELECT phone, email FROM users WHERE user_id='$user_id'";
        $result_set = selectQuery( $sql );
        $existing_phone = "";
        if( mysqli_num_rows( $result_set ) > 0 ){
            $val = mysqli_fetch_object( $result_set );
            $existing_phone = $val->phone;
            $email = $val->email;
        }
        if( $existing_phone === $e_phone ){
            echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "New mobile number should be different from your existing mobile number registered with the system" );
            return;
        }

        // New phone should not belong to someone else
        // Check if the Phone is already associated with another User ID
        $sql = "SELECT * FROM users WHERE (phone='$e_phone') AND (user_id<>'$user_id')";
        $result_set = selectQuery( $sql );
        if ( $result_set != NULL ) {
            if ( mysqli_num_rows( $result_set ) > 0 ) {
                echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "This mobile number is already associated with another user. Please try using a different mobile number");
                return;
            }
        }
        
        // Check if the daily OTP request limit has been exhausted
        $phoneUpdateOtpRequestCount = getUserMetaValue( $user_id, _PHONE_UPDATE_OTP_REQUEST_COUNT );
        if( $phoneUpdateOtpRequestCount === NULL ){
            setUserMetaValue( $user_id, _PHONE_UPDATE_OTP_REQUEST_COUNT, 1 );     // This is the first attempt to request the OTP for phone update
        }
        else{
            $phoneUpdateOtpRequestCount = intval( $phoneUpdateOtpRequestCount );
            if( $phoneUpdateOtpRequestCount === _PHONE_UPDATE_OTP_REQUEST_COUNT_MAX_LIMIT ){
                // Check if 24 hours has passed to reset this limit
                $otpGenerationTimestamp = getUserMetaValue( $user_id, _PHONE_UPDATE_OTP_GENERATION_TIMESTAMP );
                if( $otpGenerationTimestamp !== NULL ){
                    $otpGenerationTimestamp = intval( $otpGenerationTimestamp );
                    $difference = $currentMillis - $otpGenerationTimestamp;
                    if( $difference < _PHONE_UPDATE_OTP_REQUEST_COUNT_MAX_LIMIT_RESET_DURATION ){
                        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "You have exhausted all the attempts to request for an OTP. Please try again after " . ceil(_PHONE_UPDATE_OTP_REQUEST_COUNT_MAX_LIMIT_RESET_DURATION/(60 * 60 * 1000)) . " hours" );
                        return;
                    }
                }
                // If the OTP request limit duration has passed, then reset this limit
                setUserMetaValue( $user_id, _PHONE_UPDATE_OTP_REQUEST_COUNT, 1 );     // Reset the attempt count to 1
                deleteUserMetaValue( $user_id, _PHONE_UPDATE_OTP_GENERATION_TIMESTAMP );
            }
            else{
                $phoneUpdateOtpRequestCount++;
                setUserMetaValue( $user_id, _PHONE_UPDATE_OTP_REQUEST_COUNT, $phoneUpdateOtpRequestCount );     // Increment by 1
            }            
        }
        
        
        // Send email for verification
        $siteConfig = getSiteConfig();
        
        // Generate an OTP, store it in the user_meta table
        $otp            = generateOTP();
        
        //$accountActivationString = sha1( $timestamp );
        setUserMetaValue( $user_id, _PHONE_UPDATE_NEW_PHONE, $e_phone );
        setUserMetaValue( $user_id, _PHONE_UPDATE_NEW_PHONE_COUNTRY_CODE, $e_country_code );
        setUserMetaValue( $user_id, _PHONE_UPDATE_OTP_GENERATION_TIMESTAMP, $currentMillis );
        setUserMetaValue( $user_id, _PHONE_UPDATE_VERIFICATION_STATUS, _PHONE_UPDATE_VERIFICATION_STATUS_DEFAULT );
        setUserMetaValue( $user_id, _PHONE_UPDATE_OTP, $otp );
        
        
        // Generate a private_link for account activation
        //$real_link = WEBSITE_PROTOCOL . '://' . $siteConfig->domain_name . "/" . WEBSERVICE_URL . "?what_do_you_want=scodezy_verify_self_email_update&email_verification_code=$accountActivationString&user_id=$e_user_id";
        //$private_link = generatePrivateLink( $real_link );

        $mail = sendMailObject();
        $mail->isHTML( true );
        $mail->setFrom( EMAIL_NOREPLY, 'Phone Update | ' . $siteConfig->site_name );
        $mail->AddAddress( $email );
        $mail->Subject = 'Please confirm the update of the mobile number associated with your account';

        //$message = file_get_contents("templates/email/email_update_on_account.php");
        //$message = str_replace("{{user_id}}", $user_id, $message);
        //$message = str_replace("{{verification_link}}", $private_link, $message );
        $message = "The OTP to verify your mobile number is $otp";

        $mail->Body = $message;

        if ( !$mail->Send() ) {
            $responseMessage = "Some error occurred, please report to the administrator";

            // Log here about email not sending out for credentials
            
            echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, $responseMessage );
            return;
        }
        else{
            $responseMessage = "An OTP has been sent to `$email` with the procedure to update the mobile number associated with your account. Note: Please check your spam/junk in case you do not find the email in your inbox";
            $userMeta = array(
                /*
                _PHONE_UPDATE_VERIFICATION_STATUS => _PHONE_UPDATE_VERIFICATION_STATUS_DEFAULT,
                "phone" => $e_phone,
                "country_code" => $e_country_code
                 */
            );
            
            $data = array(
                "info" => $responseMessage,
                "data" => $userMeta
            );
            echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $data );
            return;
        }
        
    }
    else{
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "You have entered incorrect password for your account" );
        return;
    }
    
}


?>
