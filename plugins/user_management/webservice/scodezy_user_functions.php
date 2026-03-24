<?php

function scodezy_get_users(){
    checkAuthorizationForFunction( __FUNCTION__ );
    
    $role_id = @$_REQUEST[ 'role_id' ];
    
    if( !isset( $_REQUEST[ 'role_id' ] ) 
            || ($role_id == NULL) 
            || ($role_id == "")){
        $role_id = "-1";  // -1 means retrieve all the users irrespective of the role to which they belong
    }
    
    // Do validation for Role ID
    validateEmptyDigitString( $role_id, __FUNCTION__, "Role ID is required !" );
    if( $role_id != "-1" )
        validate( $role_id, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), "Role ID is invalid !" );
    
    $e_role_id = escape_string( $role_id );
    
    if( $e_role_id == "-1" ){
        $sql = "SELECT U.user_id, U.fname, U.lname, U.email, R.role_name FROM users U, roles R WHERE (U.role_id=R.role_id) ORDER BY U.id";
    }
    else{
        $sql = "SELECT U.user_id, U.fname, U.lname, U.email, R.role_name FROM users U, roles R WHERE (U.role_id=R.role_id) AND (R.role_id='$e_role_id')";
    }
    
    $result_set = selectQuery( $sql );
    if( ($result_set == NULL)
            || ( mysqli_num_rows($result_set) == 0 )){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "User have not been created yet for the selected role !" );
        return;
    }
    
    $users = array();
    while( ($val = mysqli_fetch_assoc( $result_set )) != NULL ){
        $users[] = $val;
    }
    
    $data = array(
        "info" => "Users have been retrieved",
        "data" => $users
    );
    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $data );
}

function scodezy_get_user(){
    checkAuthorizationForFunction( __FUNCTION__ );
    
    $user_id            = request( 'user_id' );
    $user_meta_required = request( 'user_meta_required' );
    
    // Do validation for User ID
    validateEmptyDigitString( $user_id, __FUNCTION__, "Please select a user to retrieve its information" );
    validate( $user_id, __FUNCTION__, getValidationRegex( "VLDTN_USER_ID" ), "Select user id is invalid" );
    
    // Validate if the value is present
    if( $user_meta_required !== NULL ){
        validateEmptyDigitString( $user_meta_required, __FUNCTION__, "The value of user meta required bit is invalid" );
        validate( $user_meta_required, __FUNCTION__, getValidationRegex( "VLDTN_SINGLE_BINARY" ), "The value of user meta required bit is invalid" );
    }
    
    $e_user_id = escape_string( $user_id );
    
    
    $sql = "SELECT U.user_id, U.fname, U.lname, U.nickname, U.email, U.role_id, U.registered_on, R.role_name FROM users U, roles R WHERE (U.role_id=R.role_id) AND (U.user_id='$e_user_id')";    
    $result_set = selectQuery( $sql );
    if( ($result_set == NULL)
            || ( mysqli_num_rows($result_set) == 0 )){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Selected user does not exist in the system !" );
        return;
    }
    
    $user = array();
    if( ($val = mysqli_fetch_assoc( $result_set )) != NULL ){
        $user = $val;
    }
    
    // If the value of $user_meta_required is present
    $user_meta = array();
    if( $user_meta_required === "1" ){
        // Retrieve all the User Meta Information
        $sql = "SELECT * FROM user_meta WHERE user_id='$e_user_id'";
        $result_set = selectQuery( $sql );
        if( ($result_set !== NULL)
            && ( mysqli_num_rows($result_set) > 0 )){
            while( ($val = mysqli_fetch_assoc( $result_set )) != NULL ){
                $user_meta[ $val[ 'umeta_key' ] ] = $val[ 'umeta_value' ];
            }            
        }
    }
    $user[ 'user_meta' ] = $user_meta;
    
    $data = array(
        "info" => "User's information have been retrieved",
        "data" => $user
    );
    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $data );
}

function scodezy_create_user(){
    checkAuthorizationForFunction( __FUNCTION__ );
    
    $user_id            = request( 'user_id' );
    $password           = request( 'password' );
    $email              = request( 'email' );
    $role_id            = request( 'role_id' );
    $first_name         = request( 'fname' );
    $last_name          = request( 'lname' );
    $nick_name          = request( 'nickname' );
    $send_credentials   = request( 'send_credentials' );
    $timestamp          = currentTimeMilliseconds();
    
    
    $e_send_credentials     = escape_string( $send_credentials );
    
    // Validations for emptiness
    validateEmptyString( $user_id, __FUNCTION__, "User ID is required !");
    validateEmptyString( $email, __FUNCTION__, "Email is a required field !");
    validateEmptyString( $password, __FUNCTION__, "Password is required !");
    validateEmptyDigitString( $role_id, __FUNCTION__, "Password is required !");
    validateEmptyDigitString( $send_credentials, __FUNCTION__, "Please select whether to send sign-in information to user's email");

    // Validations for REGEX
    validate( $user_id, __FUNCTION__, getValidationRegex("VLDTN_USER_ID"), "User ID can contain lowercase alphabets a to z<br />Digits 0 to 9 <br />An underscore _ <br />Minimum 3 & maximum 20 characters" );
    validate( $password, __FUNCTION__, getValidationRegex("VLDTN_PASSWORD"), "Password should be of minimum 3 characters & maximum 20 characters<br />Can contain lowercase and uppercase letters<br />Digits 0 to 9<br />Special characters like underscore _ @ and #" );
    validate( $email, __FUNCTION__, getValidationRegex("VLDTN_EMAIL"), getValidationErrMsg("VLDTN_EMAIL") );
    validate( $role_id, __FUNCTION__, getValidationRegex("VLDTN_DIGITS"), "Selected role is invalid" );
    validate( $send_credentials, __FUNCTION__, getValidationRegex("VLDTN_DIGITS"), "Checked value is invalid" );
    validate( $first_name, __FUNCTION__, getValidationRegex("VLDTN_FIRST_NAME"), getValidationErrMsg("VLDTN_FIRST_NAME") );
    validate( $last_name, __FUNCTION__, getValidationRegex("VLDTN_LAST_NAME"), getValidationErrMsg("VLDTN_LAST_NAME") );
    validate( $nick_name, __FUNCTION__, getValidationRegex("VLDTN_LAST_NAME"), getValidationErrMsg("VLDTN_NICK_NAME") );
    
    // Escape dangerous database characters
    $e_user_id          = escape_string( $user_id );
    $e_password         = escape_string( $password );
    $e_email            = escape_string( $email );
    $e_role_id          = escape_string( $role_id );
    $e_first_name       = escape_string( $first_name );
    $e_last_name        = escape_string( $last_name );
    $e_nick_name        = escape_string( $nick_name );
    
    $password_hash = hashPassword( $e_password );
    
    // Check if the User ID is available
    $sql = "SELECT user_id FROM users WHERE user_id = '$e_user_id'";
    $result_set = selectQuery( $sql );
    if ( $result_set != NULL ) {
        if ( mysqli_num_rows( $result_set ) > 0 ) {
            echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "This User ID has already been taken. Please try another ! ");
            return;
        }
    }
    
    // Check whether the Email is already associated with another User ID
    $sql = "SELECT * FROM users WHERE email='$e_email'";
    $result_set = selectQuery( $sql );
    if ( $result_set != NULL ) {
        if ( mysqli_num_rows( $result_set ) > 0 ) {
            echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "This email is already associated with another User ID. Please use another email id to create this user");
            return;
        }
    }
    
    // The role_id should be valid and must exist in the system
    $sql = "SELECT * FROM roles WHERE role_id='$e_role_id'";
    $result_set = selectQuery( $sql );
    if ( $result_set != NULL ) {
        if ( mysqli_num_rows( $result_set ) == 0 ) {
            echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "The selected Role does not exist in the system");
            return;
        }
    }
    
    // Generate public-private key pair for this user
    $keys = generateAsymmetricKeyPair();
    $public_key     = $keys[ 'publicKey' ];
    $private_key    = $keys[ 'privateKey' ];

    $sql = "INSERT into users( `user_id`, `password`, `email`, `fname`, `lname`, `nickname`, `public_key`, `private_key`, `role_id`, `registered_on` ) "
            . "VALUES( '$e_user_id', '$password_hash', '$e_email', '$e_first_name', '$e_last_name', '$e_nick_name', '$public_key', '$private_key', $role_id, '$timestamp' )";
    $rows = insertQuery( $sql );
    if ( $rows == 0 ) {
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Failed to create the user ! " );
        return;
    }
    
    $responseMessage = "";    
    if ( $e_send_credentials === "0" ){
        $responseMessage = "User account has been created";
    }
    else{

        $siteConfig = getSiteConfig();

        $mail = sendMailObject();
        $mail->isHTML( true );
        $mail->setFrom( EMAIL_NOREPLY, 'Account Created | ' . $siteConfig->site_name );
        $mail->AddAddress( $email );
        $mail->Subject = 'Your account has been created';

        $message = file_get_contents("templates/email/new_user_creation.php");
        $message = str_replace("{{url_portal}}", WEBSITE_PROTOCOL . "://" . WEBSITE_DOMAIN_NAME, $message);
        $message = str_replace("{{user_id}}", $user_id, $message);
        $message = str_replace("{{password}}", $e_password, $message );

        $mail->Body = $message;

        if ( !$mail->Send() ) {
            $responseMessage = "User account has been created. Failed to send credentials on user's email";

            // Log here about email not sending out for credentials
        }
        else{
            $responseMessage = "User account has been created. Credentials have been sent on the user's email";
        }           

        
    }
    
    // Set account activation status to be active
    setUserMetaValue( $e_user_id, _NEW_ACCOUNT_ACTIVATION_STATUS, _NEW_ACCOUNT_ACTIVATION_STATUS_ACTIVE );
    setUserMetaValue( $e_user_id, _NEW_ACCOUNT_ACTIVATION_TIMESTAMP, $timestamp );   

    // Retrieve the newly created user account details to display it in the DataTable
    $sql = "SELECT U.user_id, U.fname, U.lname, U.email, R.role_name, U.role_id FROM users U, roles R WHERE (U.role_id=R.role_id) AND (U.user_id='$e_user_id')";
    $result_set = selectQuery( $sql );
    $val = mysqli_fetch_assoc( $result_set );

    $data = array(
        "info" => $responseMessage,
        "data" => $val
    );
    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $data );

    return;
        
}

function scodezy_delete_user(){
    checkAuthorizationForFunction( __FUNCTION__ );
    
    //print_r( $_REQUEST );
    $user_id = request( 'user_id' );
    
    // Do validation for user_id
    validateEmptyDigitString( $user_id, __FUNCTION__, "User ID is required !" );
    validate( $user_id, __FUNCTION__, getValidationRegex( "VLDTN_USER_ID" ), "User ID is invalid !" );
    
    $e_user_id = escape_string( $user_id );
    
    // Check if the user_id belongs to self
    $self_user_id = $_SESSION[ SESSION_USER_ID ];
    if( $e_user_id === $self_user_id ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "You cannot delete your own User Account !" );
        return;
    }
    
    // Should not be able to delete the user_id='admin'
    if( $e_user_id === "admin" ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "The admin account cannot be deleted !" );
        return;
    }
    
    $sql = "SELECT * FROM users WHERE user_id='$e_user_id'";
    $result_set = selectQuery( $sql );
    if( ($result_set == NULL)
            || ( mysqli_num_rows($result_set) == 0 )){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "User ID is invalid !" );
        return;
    }
    $user = mysqli_fetch_assoc( $result_set );
    
    $sql = "DELETE FROM users WHERE user_id='$e_user_id'";
    //echo $sql;
    //return;
    $rows = deleteQuery( $sql );
    if( $rows > 0 ){
        // Delete all the information about this user from the user_meta table
        $sql = "DELETE FROM user_meta WHERE user_id='$e_user_id'";
        updateQuery( $sql );
        
        $data = array(
            "info" => "User has been deleted",
            "data" => $user
        );
        echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $data );
        return;
    }
    echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Failed to delete the user" );
}

function scodezy_delete_users(){
    checkAuthorizationForFunction( __FUNCTION__ );
    
    //print_r( $_REQUEST );
    //return;
    $user_ids = request( 'user_ids' );
    
    if( $user_ids === NULL ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Please select the users to be deleted !" );
        return;
    }
    
    // $user_ids needs to be a CSV string
    $userIDs = explode( ",", $user_ids );
    
    // Do validation for User IDs
    foreach ( $userIDs as $value ) {
        validateEmptyDigitString( $value, __FUNCTION__, "User ID is required !" );
        validate( $value, __FUNCTION__, getValidationRegex( "VLDTN_USER_ID" ), "User ID is invalid !" );
        
        // Check if the user_id belongs to self
        $self_user_id = $_SESSION[ SESSION_USER_ID ];
        if( $value === $self_user_id ){
            echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "You cannot delete your own User Account. Please deselect your User ID from the selection and try again" );
            return;
        }

        // Should not be able to delete the user_id='admin'
        if( $value === "admin" ){
            echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "The admin account cannot be deleted. Please deselect the 'admin' User ID from the selection and try again" );
            return;
        }
    }
    
    $user_ids_recomposed = "";
    foreach ( $userIDs as $value ) {
        $user_ids_recomposed .= "'$value',";
    }
    $user_ids_recomposed = rtrim( $user_ids_recomposed, "," );
    
    //$e_user_ids = escape_string( $user_ids );
    
    $sql = "SELECT * FROM users WHERE user_id IN ($user_ids_recomposed)";
    //echo $sql;
    //return;
    $result_set = selectQuery( $sql );
    if( ($result_set == NULL)
            || ( mysqli_num_rows($result_set) == 0 )){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "User ID is invalid !" );
        return;
    }
    $fetchedUsers = array();
    while( ( $val = mysqli_fetch_assoc( $result_set ) ) != NULL ){
        $fetchedUsers[] = $val;
    }
    
    $sql = "DELETE FROM users WHERE user_id IN ($user_ids_recomposed)";
    //echo $sql;
    //return;
    $rows = deleteQuery( $sql );
    if( $rows > 0 ){
        // Delete all the information about this user from the user_meta table
        $sql = "DELETE FROM user_meta WHERE user_id IN ($user_ids_recomposed)";
        updateQuery( $sql );
        
        $data = array(
            "info" => "Selected users have been deleted",
            "data" => $fetchedUsers
        );
        echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $data );
        return;
    }
    echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Failed to delete the selected users" );
}

/**
 * Any user's password can be reset from the users page in the Administrator section
 * By clicking on edit button against the user and pressing the Reset button
 * 
 * 
 * @return type
 */
function scodezy_reset_user_password(){
    checkAuthorizationForFunction( __FUNCTION__ );
    
    $user_id    = request( 'user_id' );

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
    // Commenting below code otherwise those who have not received password reset code, will not be able to receive the reset code again
    /*
    $activation_status = getUserMetaValue( $e_user_id, _NEW_ACCOUNT_ACTIVATION_STATUS );
    if( $activation_status !== NULL ){
        if( $activation_status == _NEW_ACCOUNT_ACTIVATION_STATUS_PENDING_ACTIVATION ){
            echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Your account is pending activation. Kindly verify and activate your account by using the verification link sent to your registered Email ID" );
            return;
        }
    }
     */
    
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
            
            echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, "Password reset instructions have been sent to the user's email id !" );
        }
        return;
    }

    echo createJSONMessage(GENERAL_ERROR_MESSAGE, __FUNCTION__, "Error occurred !");
    return;
}

function scodezy_update_user(){
    checkAuthorizationForFunction( __FUNCTION__ );
    
    $user_id            = request( 'user_id' );
    $email              = request( 'email' );
    $role_id            = request( 'role_id' );
    $first_name         = request( 'fname' );
    $last_name          = request( 'lname' );
    $nick_name          = request( 'nickname' );
    $timestamp          = currentTimeMilliseconds();
    
    
    // Validations for emptiness
    validateEmptyString( $user_id, __FUNCTION__, "User ID is required !");
    validateEmptyString( $email, __FUNCTION__, "Email is a required field !");
    validateEmptyDigitString( $role_id, __FUNCTION__, "Password is required !");
    
    // Validations for REGEX
    validate( $user_id, __FUNCTION__, getValidationRegex("VLDTN_USER_ID"), "User ID can contain lowercase alphabets a to z<br />Digits 0 to 9 <br />An underscore _ <br />Minimum 3 & maximum 20 characters" );
    validate( $email, __FUNCTION__, getValidationRegex("VLDTN_EMAIL"), getValidationErrMsg("VLDTN_EMAIL") );
    validate( $role_id, __FUNCTION__, getValidationRegex("VLDTN_DIGITS"), "Selected role is invalid" );
    validate( $first_name, __FUNCTION__, getValidationRegex("VLDTN_FIRST_NAME"), getValidationErrMsg("VLDTN_FIRST_NAME") );
    validate( $last_name, __FUNCTION__, getValidationRegex("VLDTN_LAST_NAME"), getValidationErrMsg("VLDTN_LAST_NAME") );
    validate( $nick_name, __FUNCTION__, getValidationRegex("VLDTN_LAST_NAME"), getValidationErrMsg("VLDTN_NICK_NAME") );
    
    // Escape dangerous database characters
    $e_user_id          = escape_string( $user_id );
    $e_email            = escape_string( $email );
    $e_role_id          = escape_string( $role_id );
    $e_first_name       = escape_string( $first_name );
    $e_last_name        = escape_string( $last_name );
    $e_nick_name        = escape_string( $nick_name );
    
    // Check if the User ID exist in the system
    $sql = "SELECT user_id, email FROM users WHERE user_id = '$e_user_id'";
    $result_set = selectQuery( $sql );
    if ( $result_set != NULL ) {
        if ( mysqli_num_rows( $result_set ) == 0 ) {
            echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "This User ID does not exist in the system");
            return;
        }
    }
    $val = mysqli_fetch_object( $result_set );
    $existing_email = $val->email;
    
    // Check whether the Email is already associated with another User ID
    $sql = "SELECT * FROM users WHERE (email='$e_email') AND (user_id<>'$e_user_id')";
    $result_set = selectQuery( $sql );
    if ( $result_set != NULL ) {
        if ( mysqli_num_rows( $result_set ) > 0 ) {
            echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "This email is already associated with another User ID. Please use another email id to update this user");
            return;
        }
    }
    
    // The role_id should be valid and must exist in the system
    $sql = "SELECT * FROM roles WHERE role_id='$e_role_id'";
    $result_set = selectQuery( $sql );
    if ( $result_set != NULL ) {
        if ( mysqli_num_rows( $result_set ) == 0 ) {
            echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "The selected Role does not exist in the system");
            return;
        }
    }
    
    $sql = "UPDATE users SET "
            . "`user_id`='$e_user_id', "
            . "`email`='$e_email', "
            . "`fname`='$e_first_name', "
            . "`lname`='$e_last_name', "
            . "`nickname`='$e_nick_name', "
            . "`role_id`='$e_role_id' "
            . "WHERE user_id='$e_user_id'";
    $rows = updateQuery( $sql );
    if ( $rows == 0 ) {
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Failed to update the user ! " );
        return;
    }
    $responseMessage = "User account has been updated";
    
    // If the email address is changed, then send an email to the existing and new email about this change
    if( $existing_email !== $e_email ){
        $siteConfig = getSiteConfig();

        $mail = sendMailObject();
        $mail->isHTML( true );
        $mail->setFrom( EMAIL_NOREPLY, 'Account Updated | ' . $siteConfig->site_name );
        $mail->AddAddress( $existing_email );
        $mail->AddAddress( $e_email );
        $mail->Subject = 'Your account has been updated';

        $message = file_get_contents("templates/email/user_account_email_update.php");
        $message = str_replace("{{existing_email}}", $existing_email, $message);
        $message = str_replace("{{email}}", $e_email, $message );

        $mail->Body = $message;

        if ( !$mail->Send() ) {
            $responseMessage = "User account has been updated. Failed to send an intimation on user's email";

            // Log here about email not sending out
        }
        else{
            $responseMessage = "User account has been updated. Intimation has been sent on user's email";
        }      
    }
    
    // Retrieve the newly updated user account details to display it in the DataTable
    $sql = "SELECT U.user_id, U.fname, U.lname, U.email, R.role_name, U.role_id FROM users U, roles R WHERE (U.role_id=R.role_id) AND (U.user_id='$e_user_id')";
    $result_set = selectQuery( $sql );
    $val = mysqli_fetch_assoc( $result_set );

    $data = array(
        "info" => $responseMessage,
        "data" => $val
    );
    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $data );

    return;
        
}