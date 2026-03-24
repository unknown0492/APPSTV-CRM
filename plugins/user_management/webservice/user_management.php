<?php

// Check the availability of user_id
function scodezy_is_user_id_available() {
    checkAuthorizationForFunction( __FUNCTION__ );
    
    // checkPrivilegeForFunction( __FUNCTION__ );

    $user_id = @$_REQUEST[ 'user_id' ];
    
    validateEmptyString( $user_id, __FUNCTION__, "Please choose a User ID" );
    validate( $user_id, __FUNCTION__, getValidationRegex( "VLDTN_USER_ID" ), getValidationErrMsg( "VLDTN_USER_ID" ) );
    
    $e_user_id  = escape_string( $user_id );
    if( $e_user_id !== $user_id ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "The User ID you have entered contains invalid characters !" );
        return;
    }

    // Check if the user_id already exist
    $sql = "SELECT user_id FROM users WHERE user_id='$user_id'";
    $result_set = selectQuery( $sql );
    $data = array();
    if( mysqli_num_rows( $result_set ) > 0 ){
        $data[ 'is_available' ] = "0";
        $data[ 'info' ]         = "This Username has already been taken, please try another !";
        
        echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $data );
        return;
    } 
    else{
        $data[ 'is_available' ] = "1";
        $data[ 'info' ]         = "Username is available";
        
        echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $data );
        return;
    }
    echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Something went wrong. Please contact the Administrator !" );
}


/**
 * 
 * Called from the register.php page when a new user tries to Sign Up
 * 
 */
function scodezy_sign_up(){
    //print_r( $_REQUEST );
    //return;
    
    $first_name         = @$_REQUEST[ 'first_name' ];
    $last_name          = @$_REQUEST[ 'last_name' ];
    $user_id            = @$_REQUEST[ 'user_id' ];
    $email              = @$_REQUEST[ 'email' ];
    $password           = @$_REQUEST[ 'password' ];
    $retype_password    = @$_REQUEST[ 'retype_password' ];
    $accept_terms       = @$_REQUEST[ 'accept_terms' ];
    $role_id            = roleToRoleId( "Registered User" );
    $timestamp          = currentTimeMilliseconds();
    
    
    $e_accept_terms     = escape_string( $accept_terms );
    
    if( $e_accept_terms !== "true" ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Please read and accept the Terms !" );
        return;
    }

    // Validations for emptiness
    validateEmptyString( $first_name, __FUNCTION__, "First Name is required !");
    validateEmptyString( $last_name, __FUNCTION__, "Last Name is required !");
    validateEmptyString( $user_id, __FUNCTION__, "Username is required !");
    validateEmptyString( $email, __FUNCTION__, "Email is a required field !");
    validateEmptyString( $password, __FUNCTION__, "Password is required !");
    validateEmptyString( $retype_password, __FUNCTION__, "Re-enter password is required !");

    // Validations for REGEX
    validate( $first_name, __FUNCTION__, getValidationRegex("VLDTN_FIRST_NAME"), getValidationErrMsg("VLDTN_FIRST_NAME"));
    validate( $last_name, __FUNCTION__, getValidationRegex("VLDTN_LAST_NAME"), getValidationErrMsg("VLDTN_LAST_NAME"));
    validate( $user_id, __FUNCTION__, getValidationRegex("VLDTN_USER_ID"), getValidationErrMsg("VLDTN_USER_ID"));
    validate( $email, __FUNCTION__, getValidationRegex("VLDTN_EMAIL"), getValidationErrMsg("VLDTN_EMAIL"));
    validate( $password, __FUNCTION__, getValidationRegex("VLDTN_PASSWORD"), getValidationErrMsg("VLDTN_PASSWORD"));
    validate( $retype_password, __FUNCTION__, getValidationRegex("VLDTN_PASSWORD"), getValidationErrMsg("VLDTN_PASSWORD"));
    
    // Escape dangerous database characters
    $e_first_name       = escape_string( $first_name );
    $e_last_name        = escape_string( $last_name );
    $e_user_id          = escape_string( $user_id );
    $e_email            = escape_string( $email );
    $e_password         = escape_string( $password );
    $e_retype_password  = escape_string( $retype_password );
    
    
    if ( $e_password != $e_retype_password ) {
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Password and re-entered password does not match !");
        return;
    }

    $password_hash = hashPassword( $e_password );
    
    // Check if the User ID is available
    $sql = "SELECT user_id FROM users WHERE user_id = '$e_user_id'";
    $result_set = selectQuery( $sql );
    if ( $result_set != NULL ) {
        if ( mysqli_num_rows( $result_set ) > 0 ) {
            echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "This Username has already been taken. Please try with another ! ");
            return;
        }
    }
    
    // Check if the Email is already associated with another User ID
    $sql = "SELECT * FROM users WHERE email='$e_email'";
    $result_set = selectQuery( $sql );
    if ( $result_set != NULL ) {
        if ( mysqli_num_rows( $result_set ) > 0 ) {
            echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "This email is already associated with another Username. If you have forgotton the password, use the Forgot Password option on the Login page to reset your password !");
            return;
        }
    }
    
    // Generate public-private key pair for this user
    $keys = generateAsymmetricKeyPair();
    $public_key     = $keys[ 'publicKey' ];
    $private_key    = $keys[ 'privateKey' ];

    $sql = "INSERT into users( `user_id`, `password`, `email`, `fname`, `lname`, `public_key`, `private_key`, `role_id`, `registered_on` ) "
            . "VALUES( '$e_user_id', '$password_hash', '$e_email', '$e_first_name', '$e_last_name', '$public_key', '$private_key', $role_id, '$timestamp' )";
    $rows = insertQuery( $sql );
    $responseMessage = "";
    if ( $rows > 0 ) {

        $sql = "Select allow_email_credentials FROM site_config LIMIT 0,1";
        $result_set = selectQuery($sql);
        if (( $result_set == NULL ) || ( mysqli_num_rows($result_set) == 0 )){
            $responseMessage = "You have been signed up successfully ! ";
        }
        else{

            $val = mysqli_fetch_object( $result_set );
            $data = array(
                "login_url" => PAGE_LOGIN
            );
            if ( $val->allow_email_credentials == "1" ) {

                $siteConfig = getSiteConfig();

                $mail = sendMailObject();
                $mail->isHTML( true );
                $mail->setFrom( EMAIL_NOREPLY, 'Sign Up Successful | ' . $siteConfig->site_name );
                $mail->AddAddress( $email );
                $mail->Subject = 'Your account has been created';

                $message = file_get_contents("templates/email/new_user_creation.php");
                $message = str_replace("{{url_portal}}", WEBSITE_PROTOCOL . "://" . WEBSITE_DOMAIN_NAME, $message);
                $message = str_replace("{{user_id}}", $user_id, $message);
                $message = str_replace("{{password}}", $e_password, $message );

                $mail->Body = $message;

                if ( !$mail->Send() ) {
                    $responseMessage = "You have been signed up successfully ! ";

                    // Log here about email not sending out for credentials
                }
                else{
                    $responseMessage = "You have been signed up successfully. Credentials have been sent to your email ! ";
                }           
            }
        }
        
        
        // Check if Account activation is required for the user after the New User signs up
        $accountActivationRequired = getConfigurationValue( _NEW_ACCOUNT_ACTIVATION_REQUIRED );
        if( ($accountActivationRequired === NULL) 
                || !($accountActivationRequired) ){
            
            setUserMetaValue( $e_user_id, _NEW_ACCOUNT_ACTIVATION_STATUS, _NEW_ACCOUNT_ACTIVATION_STATUS_ACTIVE );
            setUserMetaValue( $e_user_id, _NEW_ACCOUNT_ACTIVATION_TIMESTAMP, $timestamp );            
        }
        else{
            // Generate an account activation string, store it in the user_meta table and send another mail to the user for account activation
            $accountActivationString = sha1( $timestamp );
            setUserMetaValue( $e_user_id, _EMAIL_UPDATE_NEW_EMAIL, $e_email );
            setUserMetaValue( $e_user_id, _NEW_ACCOUNT_ACTIVATION_CODE, $accountActivationString );
            setUserMetaValue( $e_user_id, _NEW_ACCOUNT_ACTIVATION_STATUS, _NEW_ACCOUNT_ACTIVATION_STATUS_PENDING_ACTIVATION );
            setUserMetaValue( $e_user_id, _NEW_ACCOUNT_ACTIVATION_CODE_GENERATION_TIMESTAMP, $timestamp );

            // Generate a private_link for account activation
            $real_link = WEBSITE_PROTOCOL . '://' . $siteConfig->domain_name . "/" . WEBSERVICE_URL . "?what_do_you_want=scodezy_activate_new_account&new_account_activation_code=$accountActivationString&user_id=$e_user_id";
            $private_link = generatePrivateLink( $real_link );

            $siteConfig = getSiteConfig();

            $mail = sendMailObject();
            $mail->isHTML( true );
            $mail->setFrom( EMAIL_NOREPLY, 'Account Activation | ' . $siteConfig->site_name );
            $mail->AddAddress( $email );
            $mail->Subject = 'Verify and activate your account';

            $message = file_get_contents("templates/email/new_user_account_activation.php");
            $message = str_replace("{{user_id}}", $user_id, $message);
            $message = str_replace("{{verification_link}}", $private_link, $message );

            $mail->Body = $message;

            if ( !$mail->Send() ) {
                $responseMessage .= "Failed to send account activation link on your registered email, please try signing up again ! ";

                // Log here about email not sending out for activation
            }
            else{
                $responseMessage .= "Account activation link has been sent to your registered email ! ";
            }                  

        }
                
        $data[ 'info' ] = $responseMessage;
        echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $data );
        
        return;
    }
    echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Error Occurred. Please contact the Administrator ! " );
    return;
}

/**
 * 
 * Called from the Forgot Password page when a user tries to reset his password
 * 
 */
function scodezy_forgot_password(){
    if (!isForgotPasswordOptionEnabled()) {
        echo createJSONMessage(GENERAL_ERROR_MESSAGE, __FUNCTION__, "This functionality has been disabled by the Administrator !");
        return;
    }

    $user_id    = @$_REQUEST[ 'user_id' ];

    validateEmptyString( $user_id, __FUNCTION__, "User ID is required !" );
    validate( $user_id, __FUNCTION__, getValidationRegex( 'VLDTN_USER_ID' ), "Please enter the correct User ID !" );

    $e_user_id = escape_string( $user_id );
    
    // Someone trying to inject malicious code
    if( $user_id !== $e_user_id ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "You are not welcomed by this system. Sorry !!" );
        return;
    }
    
    // The status of this user_id should be active, in order to proceed further
    // Check if the user_id is activated in user_meta table
    $activation_status = getUserMetaValue( $e_user_id, _NEW_ACCOUNT_ACTIVATION_STATUS );
    if( $activation_status !== NULL ){
        if( $activation_status == _NEW_ACCOUNT_ACTIVATION_STATUS_PENDING_ACTIVATION ){
            echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Your account is pending activation. Kindly verify and activate your account by using the verification link sent to your registered Email ID" );
            return;
        }
    }
    
    // Fetch the account details
    $sql = "SELECT user_id, email, password_reset_code, password_reset_expiry FROM users WHERE user_id= '$e_user_id'";
    $result_set = selectQuery( $sql );
    if ( mysqli_num_rows( $result_set ) <= 0 ) {
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "This user id is not associated with any account !" );
        return;
    }
    
    $val = mysqli_fetch_object($result_set);
    $email = $val->email;
    
    if( $email == "" ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "There is no email associated with this account, please contact the Administrator to reset your password !" );
        return;
    }
    
    $flag = false;
    $send_email = false;

    $password_reset_code        = $val->password_reset_code;
    $password_reset_expiry      = $val->password_reset_expiry;
    $current_time               = currentTimeMilliseconds();

    /*
     * 1. password_reset_code is empty, create fresh password reset code, and current timestamp -> send email, update in DB
     * 2. password_reset_code is present. If password_reset_expiry is MORE than 24 hours, create fresh password reset code, and current timestamp -> send email, update in DB
     * 3. password_reset_code is present. If password_reset_expiry is LESS than 24 hours, send email
     * 
     */

    if ( $password_reset_code === "") {
        $password_reset_code        = sha1( $current_time );
        $password_reset_expiry      = $current_time;
    } 
    else{
        if ( isPasswordResetValidityExpired( $current_time, $password_reset_expiry ) ) {
            // create new reset code and current time as interval
            $password_reset_code        = sha1( $current_time );
            $password_reset_expiry      = $current_time;
        }
    }

    $sql = "UPDATE users SET password_reset_code='$password_reset_code', password_reset_expiry='$password_reset_expiry' WHERE user_id='$user_id'";
    $status = updateQuery( $sql );
    if ( $status ) {
        $site_config = getSiteConfig();
        // Send the password reset email
        $mail = sendMailObject();
        $mail->isHTML( true );
        $mail->setFrom( EMAIL_NOREPLY, 'Password Reset | ' . $site_config->domain_name );
        $mail->AddAddress( $email );
        $mail->Subject = 'Password Reset request for your account has been received';

        $password_reset_link = WEBSITE_PROTOCOL . "://" . WEBSITE_DOMAIN_NAME . "/password-reset.php?code=$password_reset_code";
        $message = file_get_contents("templates/email/password_reset.php");
        $message = str_replace("{{password_reset_url}}", $password_reset_link, $message);
        $message = str_replace("{{password_reset_url_html}}", htmlentities($password_reset_link), $message);

        $mail->Body = $message;
        if (!$mail->Send()) {
            echo createJSONMessage(GENERAL_ERROR_MESSAGE, __FUNCTION__, "Failed to reset password ! !");
        } 
        else {
            
            $data = array(
                "login_url" => PAGE_LOGIN,
                "info" => "Password reset instructions have been sent to your registered Email ID !"
            );
            
            echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $data );
        }
        return;
    }

    echo createJSONMessage(GENERAL_ERROR_MESSAGE, __FUNCTION__, "Error occurred !");
    return;
}

function scodezy_activate_new_account(){
    // print_r( $_REQUEST );
    
    $activation_code    = @$_REQUEST[ 'new_account_activation_code' ];
    $user_id            = @$_REQUEST[ 'user_id' ];
    
    // Escape the code to make it database-safe
    $e_activation_code  = escape_string( $activation_code );
    $e_user_id          = escape_string( $user_id );
    
    // Check if this user_id has already been activated and this is a re-request
    $activation_status = getUserMetaValue( $e_user_id, _NEW_ACCOUNT_ACTIVATION_STATUS );
    if( $activation_status !== NULL ){
        if( $activation_status === _NEW_ACCOUNT_ACTIVATION_STATUS_ACTIVE ){
            echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, "Your account has already been activated. Please proceed to the Login page to Sign in to Administrator Panel !" );
            return;
        }
    }
    
    
    // Check if the activation_code exist in the DB
    $sql = "SELECT * FROM user_meta WHERE (user_id='$e_user_id') AND (umeta_key='"._NEW_ACCOUNT_ACTIVATION_CODE."') AND (umeta_value='$e_activation_code')";
    $result_set = selectQuery( $sql );
    if( $result_set === NULL ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Account activation link is either invalid or has been expired !" );
        return;
    }
    if( mysqli_num_rows( $result_set ) == 0 ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Account activation link is either invalid or has been expired !" );
        return;
    }
    
    // Check the if the validity of the activation_code has not expired
    $sql = "SELECT * FROM configurations WHERE config_key='"._NEW_ACCOUNT_ACTIVATION_CODE_VALIDITY."'";
    $result_set = selectQuery( $sql );
    if( $result_set === NULL ){
        $activation_code_validity_should_be = _NEW_ACCOUNT_ACTIVATION_CODE_VALIDITY_DEFAULT;
    }
    if( mysqli_num_rows( $result_set ) == 0 ){
        $activation_code_validity_should_be = _NEW_ACCOUNT_ACTIVATION_CODE_VALIDITY_DEFAULT;
    }
    else{
        $val = mysqli_fetch_object( $result_set );
        $activation_code_validity_should_be = $val->config_value;
    }
    
    // Retreive the timestamp when the Activation code was generated
    $sql = "SELECT * FROM user_meta WHERE (user_id='$e_user_id') AND (umeta_key='"._NEW_ACCOUNT_ACTIVATION_CODE_GENERATION_TIMESTAMP."')";
    $result_set = selectQuery( $sql );
    if( $result_set === NULL ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Account activation link is either invalid or has been expired !" );
        return;
    }
    if( mysqli_num_rows( $result_set ) == 0 ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Account activation link is either invalid or has been expired !" );
        return;
    }
    
    $val = mysqli_fetch_object( $result_set );
    $activation_code_generation_timestamp = $val->umeta_value;
    
    // Check if the activation_code still has validity
    $currentTimestamp = currentTimeMilliseconds();
    $timeDifference   = $currentTimestamp - $activation_code_generation_timestamp;
    
    if( $timeDifference > $activation_code_validity_should_be ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Account activation link has been expired. Kindly perform the sign-up process again !" );
        return;
    }
    
    
    // If all the above constraints have been surpassed, it means that the validation process is successful
    // Change the activation_status for the user
    // Create new entry in the user_meta for the timestamp of the instance when the account was activated
    // Remove the actiavtion_code and timestamp from the table
    setUserMetaValue( $e_user_id, _NEW_ACCOUNT_ACTIVATION_STATUS, _NEW_ACCOUNT_ACTIVATION_STATUS_ACTIVE );
    setUserMetaValue( $e_user_id, _NEW_ACCOUNT_ACTIVATION_TIMESTAMP, currentTimeMilliseconds() );
    deleteUserMetaValue( $e_user_id, _NEW_ACCOUNT_ACTIVATION_CODE );
    deleteUserMetaValue( $e_user_id, _NEW_ACCOUNT_ACTIVATION_CODE_GENERATION_TIMESTAMP );
    
    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, "Your account has been activated successfully. You may proceed to the Login page to Sign In to the Administrator Panel !" );
    
}

function scodezy_update_password_on_reset_page(){
    
    if( !isForgotPasswordOptionEnabled() ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "This feature has been disabled by the Administrator. If you want to Update or Reset your password, please contact the Administrator" );
        return;
    }
    
    @session_start();
    $password_reset_code = $_SESSION[ 'code' ];
    
    $e_password_reset_code = escape_string( $password_reset_code );
    
    $data = array();
    
    // Check if the password_reset_code is valid
    $sql = "SELECT user_id, password_reset_expiry, email FROM users WHERE password_reset_code='$e_password_reset_code'";
    $result_set = selectQuery( $sql );
    if( ($result_set === NULL) 
            || (mysqli_num_rows( $result_set ) == 0) ){
        $data[ 'url' ] = PAGE_FORGOT_PASSWORD;
        $data[ 'info' ] = "Password Reset Link has been expired, please redo the password reset process again on the Forgot Password Page";
        session_destroy();
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, $data );
        return;
    }

    $val = mysqli_fetch_object( $result_set );
    $password_reset_expiry = $val->password_reset_expiry;
    $current_time = currentTimeMilliseconds();

    if( isPasswordResetValidityExpired( $current_time, $password_reset_expiry ) ){
        $data[ 'url' ] = PAGE_FORGOT_PASSWORD;
        $data[ 'info' ] = "Password Reset Link has been expired, please redo the password reset process again on the Forgot Password Page";
        session_destroy();
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, $data );
        return;
    }
    
    $user_id            = $val->user_id;
    $password           = @$_REQUEST[ 'password' ];
    $retype_password    = @$_REQUEST[ 'retype_password' ];
    
    // Validations for emptiness
    validateEmptyString( $password, __FUNCTION__, "Password is required !");
    validateEmptyString( $retype_password, __FUNCTION__, "Re-enter password is required !");

    // Validations for REGEX
    validate( $password, __FUNCTION__, getValidationRegex("VLDTN_PASSWORD"), getValidationErrMsg("VLDTN_PASSWORD"));
    validate( $retype_password, __FUNCTION__, getValidationRegex("VLDTN_PASSWORD"), getValidationErrMsg("VLDTN_PASSWORD"));
    
    // Escape dangerous database characters
    $e_password         = escape_string( $password );
    $e_retype_password  = escape_string( $retype_password );
    
    if ( $e_password != $e_retype_password ) {
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Password and re-entered password does not match !");
        return;
    }

    $password_hash = hashPassword( $e_password );
    
    // Update the new password in the DB
    $sql = "UPDATE users SET password='$password_hash' WHERE user_id='$user_id'";
    $rows = selectQuery( $sql );
    if( $rows > 0 ){
        $data[ 'url' ] = PAGE_LOGIN;
        $data[ 'info' ] = "Your password has been updated successfully . Proceed to the Login page to Sign In to the Administrator Panel";
        
        // Clear the password reset codes from the table
        $sql = "UPDATE users SET password_reset_code='', password_reset_expiry='' WHERE user_id='$user_id'";
        updateQuery( $sql );
        
        session_destroy();
        echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $data );
        return;
    }
    echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Unknown error occurred, please try again later" );
    return;
    
}

?>