<?php

// Check the availability of user_id
function is_user_id_available() {
    // checkPrivilegeForFunction( __FUNCTION__ );

    $user_id = $_REQUEST['user_id'];

    // Check if the user_name already exist
    $sql = "SELECT user_id  FROM users WHERE user_id='$user_id'";
    $result_set = selectQuery($sql);
    if (mysqli_num_rows($result_set) > 0) {
        echo createJSONMessage(GENERAL_ERROR_MESSAGE, __FUNCTION__, "This User Name already exist, please try another !");
        return;
    } else if (mysqli_num_rows($result_set) == 0) {
        echo createJSONMessage(GENERAL_SUCCESS_MESSAGE, __FUNCTION__, "User ID is available");
        return;
    }
    echo createJSONMessage(GENERAL_ERROR_MESSAGE, __FUNCTION__, "Something went wrong. Please contact the Administrator !");
}

function create_user() {
    checkPrivilegeForFunction(__FUNCTION__);

    $user_id = $_REQUEST['user_id'];
    $password = $_REQUEST['password'];
    $email = $_REQUEST['email'];
    $fname = $_REQUEST['fname'];
    $lname = $_REQUEST['lname'];
    $nickname = $_REQUEST['nickname'];
    $role_id = $_REQUEST['role_id'];
    $timestamp = currentTimeMilliseconds();

    // Validations for emptiness
    validateEmptyString($user_id, __FUNCTION__, "User ID is a required field !");
    validateEmptyString($password, __FUNCTION__, "Password is a required field !");
    validateEmptyString($email, __FUNCTION__, "Email is a required field !");
    validateEmptyDigitString($role_id, __FUNCTION__, "Something went wrong. Please Log out and Log in again !");

    // Validations for REGEX
    validate($user_id, __FUNCTION__, getValidationRegex("VLDTN_USER_ID"), getValidationErrMsg("VLDTN_USER_ID"));
    validate($password, __FUNCTION__, getValidationRegex("VLDTN_PASSWORD"), getValidationErrMsg("VLDTN_PASSWORD"));
    validate($email, __FUNCTION__, getValidationRegex("VLDTN_EMAIL"), getValidationErrMsg("VLDTN_EMAIL"));
    validate($fname, __FUNCTION__, getValidationRegex("VLDTN_FIRST_NAME"), getValidationErrMsg("VLDTN_FIRST_NAME"));
    validate($lname, __FUNCTION__, getValidationRegex("VLDTN_LAST_NAME"), getValidationErrMsg("VLDTN_LAST_NAME"));
    validate($nickname, __FUNCTION__, getValidationRegex("VLDTN_NICK_NAME"), getValidationErrMsg("VLDTN_NICK_NAME"));
    validate($role_id, __FUNCTION__, getValidationRegex("VLDTN_ROLE_ID"), getValidationErrMsg("VLDTN_ROLE_ID"));

    $password = md5($_REQUEST['password']);
    $password_backup = $_REQUEST['password'];

    if ($role_id == "none")
        $role_id = roleToRoleId(SESSION_STAFF);

    if (onlyAdminCan(false, $role_id))
        return;

    $sql = "SELECT user_id FROM users WHERE user_id = '$user_id'";
    $result_set = selectQuery($sql);
    if ($result_set != NULL) {
        if (mysqli_num_rows($result_set) > 0) {
            echo createJSONMessage(GENERAL_ERROR_MESSAGE, __FUNCTION__, "This User ID has been taken. Please try another !");
            return;
        }
    }

    $sql = "INSERT into users( `user_id`, `password`, `email`, `fname`, `lname`, `nickname`, `role_id`, `registered_on` ) VALUES( '$user_id', '$password', '$email', '$fname', '$lname', '$nickname', $role_id, '$timestamp' )";
    $rows = insertQuery($sql);
    if ($rows > 0) {

        $sql = "Select allow_email_credentials FROM site_config LIMIT 0,1";
        $result_set = selectQuery($sql);
        if (( $result_set == NULL ) || ( mysqli_num_rows($result_set) == 0 ))
            return;

        $val = mysqli_fetch_object($result_set);
        if ($val->allow_email_credentials == "1") {
            $mail = sendMailObject();
            $mail->isHTML(true);
            global $sender_name;
            global $sender_email;
            $mail->setFrom( $sender_email, "Account Created | $sender_name" );
            $mail->AddAddress( $email );
            $mail->Subject = 'Your account has been created';

            $message = file_get_contents("templates/email/new_user_creation.php");
            $message = str_replace("{{url_portal}}", WEBSITE_PROTOCOL . "://" . WEBSITE_DOMAIN_NAME, $message);
            $message = str_replace("{{user_id}}", $user_id, $message);
            $message = str_replace("{{password}}", $password_backup, $message);

            $mail->Body = $message;

            if (!$mail->Send()) {
                echo createJSONMessage(GENERAL_SUCCESS_MESSAGE, __FUNCTION__, "User created successfully, but failed to send email !");
                return;
            }
            echo createJSONMessage(GENERAL_SUCCESS_MESSAGE, __FUNCTION__, "User created successfully, and email has been sent !");
            return;
        }
        echo createJSONMessage(GENERAL_SUCCESS_MESSAGE, __FUNCTION__, "User created successfully !");
        return;
    }
    echo createJSONMessage(GENERAL_ERROR_MESSAGE, __FUNCTION__, "Error Occurred. Please contact the Support Team !");
    return;
}

function register_user() {
    $user_id = $_REQUEST['user_id'];
    $password = $_REQUEST['password'];
    $password_confirm = $_REQUEST['password_confirm'];
    $email = $_REQUEST['email'];
    $fname = $_REQUEST['fname'];
    $lname = $_REQUEST['lname'];
    $role_id = roleToRoleId("Registered User");
    $timestamp = currentTimeMilliseconds();

    // Validations for emptiness
    validateEmptyString($user_id, __FUNCTION__, "User ID is a required field !");
    validateEmptyString($password, __FUNCTION__, "Password is a required field !");
    validateEmptyString($email, __FUNCTION__, "Email is a required field !");
    // validateEmptyDigitString( $role_id, __FUNCTION__, "Something went wrong. Please Log out and Log in again !" );
    // Validations for REGEX
    validate($user_id, __FUNCTION__, getValidationRegex("VLDTN_USER_ID"), getValidationErrMsg("VLDTN_USER_ID"));
    validate($password, __FUNCTION__, getValidationRegex("VLDTN_PASSWORD"), getValidationErrMsg("VLDTN_PASSWORD"));
    validate($password_confirm, __FUNCTION__, getValidationRegex("VLDTN_PASSWORD"), getValidationErrMsg("VLDTN_PASSWORD"));
    validate($email, __FUNCTION__, getValidationRegex("VLDTN_EMAIL"), getValidationErrMsg("VLDTN_EMAIL"));
    validate($fname, __FUNCTION__, getValidationRegex("VLDTN_FIRST_NAME"), getValidationErrMsg("VLDTN_FIRST_NAME"));
    validate($lname, __FUNCTION__, getValidationRegex("VLDTN_LAST_NAME"), getValidationErrMsg("VLDTN_LAST_NAME"));
    //validate( $role_id, __FUNCTION__, getValidationRegex( "VLDTN_ROLE_ID" ), getValidationErrMsg( "VLDTN_ROLE_ID" ) );

    if ($password != $password_confirm) {
        echo createJSONMessage(GENERAL_ERROR_MESSAGE, __FUNCTION__, "Both passwords do not match !");
        return;
    }

    $password = md5($_REQUEST['password']);
    $password_backup = $_REQUEST['password'];



    $sql = "SELECT user_id FROM users WHERE user_id = '$user_id'";
    $result_set = selectQuery($sql);
    if ($result_set != NULL) {
        if (mysqli_num_rows($result_set) > 0) {
            echo createJSONMessage(GENERAL_ERROR_MESSAGE, __FUNCTION__, "This User ID has been taken. Please try another !");
            return;
        }
    }

    $sql = "INSERT into users( `user_id`, `password`, `email`, `fname`, `lname`, `role_id`, `registered_on` ) VALUES( '$user_id', '$password', '$email', '$fname', '$lname', $role_id, '$timestamp' )";
    $rows = insertQuery($sql);
    if ($rows > 0) {

        $sql = "Select allow_email_credentials FROM site_config LIMIT 0,1";
        $result_set = selectQuery($sql);
        if (( $result_set == NULL ) || ( mysqli_num_rows($result_set) == 0 ))
            return;

        $val = mysqli_fetch_object($result_set);
        if ($val->allow_email_credentials == "1") {
            $mail = sendMailObject();
            $mail->isHTML(true);
            $mail->setFrom(EMAIL_NOREPLY, 'Account Created');
            $mail->AddAddress($email);
            $mail->Subject = 'Your account has been created';

            $message = file_get_contents("templates/email/new_user_creation.php");
            $message = str_replace("{{url_portal}}", WEBSITE_PROTOCOL . "://" . WEBSITE_DOMAIN_NAME, $message);
            $message = str_replace("{{user_id}}", $user_id, $message);
            $message = str_replace("{{password}}", $password_backup, $message);

            $mail->Body = $message;

            if (!$mail->Send()) {
                echo createJSONMessage(GENERAL_SUCCESS_MESSAGE, __FUNCTION__, "Account created successfully, but failed to send email !");
                return;
            }
            echo createJSONMessage(GENERAL_SUCCESS_MESSAGE, __FUNCTION__, "Account created successfully, and email has been sent !");
            return;
        }
        echo createJSONMessage(GENERAL_SUCCESS_MESSAGE, __FUNCTION__, "Account created successfully !");
        return;
    }
    echo createJSONMessage(GENERAL_ERROR_MESSAGE, __FUNCTION__, "Error Occurred. Please contact the Support Team !");
    return;
}

function user_info() {
    checkPrivilegeForFunction(__FUNCTION__);

    $user_id = $_REQUEST['user_id'];
    if (onlyAdminCan($user_id, false)) {
        return;
    }

    $sql = "SELECT fname,lname,nickname,email,roles.role_id,registered_on,role_name FROM users,roles WHERE users.user_id = '$user_id' AND roles.role_id = users.role_id";
    $result_set = selectQuery($sql);
    if (mysqli_num_rows($result_set) <= 0) {
        echo createJSONMessage(GENERAL_ERROR_MESSAGE, __FUNCTION__, "Error occurred !");
        return;
    }

    $val = mysqli_fetch_object($result_set);
    echo createJSONMessage(GENERAL_SUCCESS_MESSAGE, __FUNCTION__, json_encode(array($val)));
    return;
}

function delete_user() {
    checkPrivilegeForFunction(__FUNCTION__);

    //print_r( $_REQUEST );

    $user_id = $_REQUEST['user_id'];
    if (onlyAdminCan($user_id, false)) {
        return;
    }

    $sql = "DELETE FROM users WHERE user_id = '$user_id'";
    $rows = deleteQuery($sql);
    if ($rows > 0) {
        echo createJSONMessage(GENERAL_SUCCESS_MESSAGE, __FUNCTION__, "User deleted successfully !");
        return;
    }

    echo createJSONMessage(GENERAL_ERROR_MESSAGE, __FUNCTION__, "Failed to Delete User ! Please contact the Technical Team...");
    return;
}

function update_user() {
    checkPrivilegeForFunction(__FUNCTION__);

    // print_r( $_REQUEST );

    $user_id = $_REQUEST['user_id'];
    if (onlyAdminCan($user_id, false)) {
        return;
    }

    // Validate these variables
    $fname = $_REQUEST['edit_fname'];
    $lname = $_REQUEST['edit_lname'];
    $nickname = $_REQUEST['edit_nickname'];
    $email = $_REQUEST['edit_email'];
    $role_id = $_REQUEST['edit_role_id'];
    /* $change_password	= $_REQUEST[ 'change_password' ];
      $confirm_password	= $_REQUEST[ 'confirm_password' ]; */

    /*
      //validate dropdown for change password and Confirm Password
      validateEmptyString( $change_password, __FUNCTION__, 'Please Enter Change Password !' );
      validate( $change_password, __FUNCTION__, "/^[a-zA-Z0-9-_\.\#\@\$]*$/", 'Invalid Password Entered' );
      validateEmptyString( $confirm_password, __FUNCTION__, 'Please Enter Confirm Password !' );
      validate( $confirm_password, __FUNCTION__, "/^[a-zA-Z0-9-_\.\#\@\$]*$/", 'Invalid Password Entered' );

      if( $change_password != $confirm_password  && $change_password != NULL && $confirm_password != NULL){
      echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Confirm Password does not match Change Password !" );
      return;
      }

      $change_password	= md5( $_REQUEST[ 'change_password' ] ); */
    //$sql = "UPDATE users SET fname = '$fname', lname = '$lname', nickname = '$nickname', email = '$email', role_id = $role_id, password = '$change_password' WHERE user_id = '$user_id'";
    $sql = "UPDATE users SET fname = '$fname', lname = '$lname', nickname = '$nickname', email = '$email', role_id = $role_id WHERE user_id = '$user_id'";
    $rows = updateQuery($sql);
    if ($rows > 0) {
        echo createJSONMessage(GENERAL_SUCCESS_MESSAGE, __FUNCTION__, "User information updated successfully !");
        return;
    }

    echo createJSONMessage(GENERAL_ERROR_MESSAGE, __FUNCTION__, "Error occurred !");
    return;
}

function reset_password() {
    //checkPrivilegeForFunction(__FUNCTION__);

    $user_id = $_REQUEST['user_id'];
    if (onlyAdminCan($user_id, false)) {
        return;
    }

    $sql = "SELECT user_id, email, password_reset_code, password_reset_expiry FROM users WHERE user_id = '$user_id'";
    $result_set = selectQuery($sql);
    if (mysqli_num_rows($result_set) <= 0) {
        echo createJSONMessage(GENERAL_ERROR_MESSAGE, __FUNCTION__, "Invalid User ID");
        return;
    }
    $val = mysqli_fetch_object($result_set);
    $email = $val->email;
    $flag = true;

    $password_reset_code = $val->password_reset_code;
    $current_time = currentTimeMilliseconds();

    // if reset code is present in the columns
    if (!( ( trim($password_reset_code) == "" ) || ( $password_reset_code == NULL ) )) {
        // compare the expiry
        $password_reset_expiry = $val->password_reset_expiry;
        if (!( ( trim($password_reset_expiry) == "" ) || ( $password_reset_expiry == NULL ) )) {
            $difference = ( $current_time - $password_reset_expiry ) / ( 1000 * 60 * 60 );
            if ($difference > 24) {
                // create new reset code and current time as interval
                $password_reset_code = md5($current_time);
                $password_reset_expiry = $current_time;
                $flag = true;
            }
        } else {
            $password_reset_expiry = $current_time;
            $flag = true;
        }
    } else {
        // reset code not present in the columns
        $flag = true;
        $password_reset_code = md5($current_time);
        $password_reset_expiry = $current_time;
    }

    if ($flag) {
        $sql = "UPDATE users SET password_reset_code = '$password_reset_code', password_reset_expiry = '$password_reset_expiry' WHERE user_id = '$user_id'";
        $rows = updateQuery($sql);
        if ($rows > 0) {
            // Send the password reset email
            // sendMail( $email, $from, "Password Reset", "use template" );
            $mail = sendMailObject();
            $mail->isHTML(true);
            $mail->setFrom( EMAIL_NOREPLY, 'Password Reset' );
            //$mail->From = EMAIL_NOREPLY;
            $mail->AddAddress( $email );
            $mail->Subject = 'Password reset';
            
            $password_reset_link = WEBSITE_PROTOCOL . "://" . WEBSITE_DOMAIN_NAME . "password_reset.php?code=$password_reset_code";
            $message = file_get_contents("templates/email/password_reset.php");
            $message = str_replace("{{password_reset_url}}", $password_reset_link, $message);
            $message = str_replace("{{password_reset_url_html}}", htmlentities($password_reset_link), $message);

            $mail->Body = $message;
            if (!$mail->Send()) {
                echo createJSONMessage(GENERAL_ERROR_MESSAGE, __FUNCTION__, "Failed to send password reset email !");
            } else {
                echo createJSONMessage(GENERAL_SUCCESS_MESSAGE, __FUNCTION__, "Password reset information has been sent to $email");
            }

            return;
        }
    } else if (!$flag) {
        // sendMail( $email, $from, "Password Reset", "use template" );
        echo createJSONMessage(GENERAL_SUCCESS_MESSAGE, __FUNCTION__, "Password reset information has been sent to $email");
        return;
    }

    echo createJSONMessage(GENERAL_ERROR_MESSAGE, __FUNCTION__, "Error occurred !");
    return;
}

function edit_user_profile() {
    checkPrivilegeForFunction(__FUNCTION__);
    $first_name = $_REQUEST['first_name'];
    $last_name = $_REQUEST['last_name'];
    $nick_name = $_REQUEST['nick_name'];
    $user_email = $_REQUEST['user_email'];
    $security_question = $_REQUEST['security_question'];
    $security_answer = $_REQUEST['security_answer'];

    //validations starts here
    validateEmptyString($first_name, __FUNCTION__, 'Please enter First Name.');
    validate($first_name, __FUNCTION__, "/^[a-zA-Z_]*$/", 'Only alphabets and underscores are allowed');

    validateEmptyString($last_name, __FUNCTION__, 'Please enter Last Name.');
    validate($last_name, __FUNCTION__, "/^[a-zA-Z_]*$/", 'Only alphabets and underscores are allowed');

    validateEmptyString($nick_name, __FUNCTION__, 'Please enter Nick Name.');
    validate($nick_name, __FUNCTION__, "/^[a-zA-Z_]*$/", 'Only alphabets and underscores are allowed');

    validateEmptyString($user_email, __FUNCTION__, 'Please enter Email Address.');
    validate($user_email, __FUNCTION__, "/^[a-z]+[a-z0-9._]+@[a-z]+\.[a-z.]{2,10}$/", 'Invalid Email Address');

    validateEmptyString($security_question, __FUNCTION__, 'Please enter Security Question.');
    validate($security_question, __FUNCTION__, "/^[A-Za-z0-9_ ]*$/", 'Only alphabets,numbers and underscores are allowed');

    validateEmptyString($security_answer, __FUNCTION__, 'Please enter Security Answer.');
    validate($security_answer, __FUNCTION__, "/^[A-Za-z0-9_ ]*$/", 'Only alphabets,numbers and underscores are allowed');

    $sql = " SELECT user_id FROM users WHERE user_id='" . $_REQUEST['user_id'] . "' ";
    $result_set = selectQuery($sql);
    if (mysqli_num_rows($result_set) > 0) {
        $sql = "UPDATE users SET fname='$first_name', lname='$last_name', nickname='$nick_name',email='$user_email', security_question='$security_question', security_answer='$security_answer' WHERE user_id='" . $_REQUEST['user_id'] . "' ";
        $rows = updateQuery($sql);
        if ($rows > 0) {
            echo createJSONMessage(GENERAL_SUCCESS_MESSAGE, __FUNCTION__, "User Profile Information updated Successfully");
            return;
        }
        echo createJSONMessage(GENERAL_ERROR_MESSAGE, __FUNCTION__, "User Profile Updation Failed !");
        return;
    }
    echo createJSONMessage(GENERAL_ERROR_MESSAGE, __FUNCTION__, "Error occurred !");
    return;
}

function edit_user_password() {
    checkPrivilegeForFunction(__FUNCTION__);

    $user_password_old = $_REQUEST['user_password_old'];
    $user_password_new = $_REQUEST['user_password_new'];
    $user_password_repeat = $_REQUEST['user_password_repeat'];

    //validation starts here
    validateEmptyString($user_password_old, __FUNCTION__, 'Please enter Old Password.');
    //validate( $user_password_old, __FUNCTION__, "", 'Invalid Password' );

    validateEmptyString($user_password_new, __FUNCTION__, 'Please enter New Password.');
    //validate( $user_password_new, __FUNCTION__, "", 'Invalid Password' );

    validateEmptyString($user_password_repeat, __FUNCTION__, 'Please Repeat New Password.');
    //validate( $user_password_repeat, __FUNCTION__, "", 'Invalid Password' );

    if ($user_password_new != $user_password_repeat && $user_password_new != NULL && $user_password_repeat != NULL) {
        echo createJSONMessage(GENERAL_ERROR_MESSAGE, __FUNCTION__, "Repeat Password does not match with New Password !");
        return;
    }

    $user_password_old = md5($_REQUEST['user_password_old']);
    $user_password_new = md5($_REQUEST['user_password_new']);

    $sql = " SELECT password FROM users WHERE user_id='" . $_REQUEST['user_id'] . "' ";
    $result_set = selectQuery($sql);
    if (mysqli_num_rows($result_set) > 0) {
        $val = mysqli_fetch_assoc($result_set);
        if ($user_password_old != $val['password']) {
            echo createJSONMessage(GENERAL_ERROR_MESSAGE, __FUNCTION__, "Old Password entered does not match!");
            return;
        }
    } else {
        echo createJSONMessage(GENERAL_ERROR_MESSAGE, __FUNCTION__, "No such User!");
        return;
    }

    $sql = "UPDATE users SET password = '$user_password_new' WHERE user_id = '" . $_REQUEST['user_id'] . "' ";
    $rows = updateQuery($sql);
    if ($rows > 0) {
        echo createJSONMessage(GENERAL_SUCCESS_MESSAGE, __FUNCTION__, "Password updated Successfully");
        return;
    }
    echo createJSONMessage(GENERAL_ERROR_MESSAGE, __FUNCTION__, "Error occurred !");
    return;
}

function reset_own_password() {

    if (!isForgotPasswordOptionEnabled()) {
        echo createJSONMessage(GENERAL_ERROR_MESSAGE, __FUNCTION__, "Forgot Password Option is Disabled in the Site Configuration !");
        return;
    }

    $email = @$_REQUEST['email'];

    validateEmptyString($email, __FUNCTION__, "Email ID cannot be empty !");
    validate($email, __FUNCTION__, getValidationRegex('VLDTN_EMAIL'), getValidationErrMsg('VLDTN_EMAIL'));

    // return; 
    $sql = "SELECT user_id, email, password_reset_code, password_reset_expiry FROM users WHERE email = '$email'";
    $result_set = selectQuery($sql);
    if (mysqli_num_rows($result_set) <= 0) {
        echo createJSONMessage(GENERAL_ERROR_MESSAGE, __FUNCTION__, "This Email is not associated with any account");
        return;
    }
    // echo mysqli_num_rows( $result_set );
    $val = mysqli_fetch_object($result_set);
    $email = $val->email;
    $flag = false;
    $send_email = false;

    $password_reset_code = $val->password_reset_code;
    $password_reset_expiry = $val->password_reset_expiry;
    $current_time = currentTimeMilliseconds();

    /*
     * 1. password_reset_code is empty, create fresh password reset code, and current timestamp -> send email, update in DB
     * 2. password_reset_code is present. If password_reset_expiry is MORE than 24 hours, create fresh password reset code, and current timestamp -> send email, update in DB
     * 3. password_reset_code is present. If password_reset_expiry is LESS than 24 hours, send email
     * 
     */

    // echo "password_reset_code : $password_reset_code ";
    if (trim($password_reset_code) === "") {
        $password_reset_code = md5($current_time);
        $password_reset_expiry = $current_time;
        // $send_email = true;
    } else {
        if (isPasswordResetValidityExpired($current_time, $password_reset_expiry)) {
            // create new reset code and current time as interval
            $password_reset_code = md5($current_time);
            $password_reset_expiry = $current_time;
            // $send_email = true;
        }
    }

    $sql = "UPDATE users SET password_reset_code = '$password_reset_code', password_reset_expiry = '$password_reset_expiry' WHERE email = '$email'";
    $rows = updateQuery($sql);
    if ($rows > 0) {
        // Send the password reset email
        // sendMail( $email, $from, "Password Reset", "use template" );
        $mail = sendMailObject();
        $mail->isHTML(true);
        $mail->setFrom(EMAIL_NOREPLY, 'noreply');
        $mail->AddAddress($email);
        $mail->Subject = 'Password Reset';

        $password_reset_link = WEBSITE_PROTOCOL . "://" . WEBSITE_DOMAIN_NAME . "password_reset.php?code=$password_reset_code";
        $message = file_get_contents("templates/email/password_reset.php");
        $message = str_replace("{{password_reset_url}}", $password_reset_link, $message);
        $message = str_replace("{{password_reset_url_html}}", htmlentities($password_reset_link), $message);

        $mail->Body = $message;
        if (!$mail->Send()) {
            echo createJSONMessage(GENERAL_ERROR_MESSAGE, __FUNCTION__, "Failed to send password reset email !");
        } else {
            echo createJSONMessage(GENERAL_SUCCESS_MESSAGE, __FUNCTION__, "Password reset information has been sent to $email");
        }
        return;
    }

    echo createJSONMessage(GENERAL_ERROR_MESSAGE, __FUNCTION__, "Error occurred !");
    return;
}

/**
 * 
 * Allow to reset the password of other users of the system
 * 
 */
function reset_others_password(){
    checkPrivilegeForFunction( __FUNCTION__ );
    //print_r( $_REQUEST );

    $email		 = @$_REQUEST[ 'email' ];
    $user_id		 = @$_REQUEST[ 'user_id' ];

    validateEmptyString( $email, __FUNCTION__, "Email ID cannot be empty !" );
    validateEmptyString( $user_id, __FUNCTION__, "User ID is required !" );
    validate( $email, __FUNCTION__, getValidationRegex( 'VLDTN_EMAIL' ), getValidationErrMsg( 'VLDTN_EMAIL' ) );
    validate( $user_id, __FUNCTION__, getValidationRegex( 'VLDTN_USER_ID' ), getValidationErrMsg( 'VLDTN_USER_ID' ) );

    // return;
    $sql 		= "SELECT user_id, email, password_reset_code, password_reset_expiry FROM users WHERE user_id = '$user_id'";
    $result_set = selectQuery( $sql );
    /*
    if( mysqli_num_rows( $result_set ) <= 0 ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "This Email is not associated with any account" );
        return;
    }
     */
    if( mysqli_num_rows( $result_set ) == 0 ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "No such account exist !" );
        return;
    }

    // echo mysqli_num_rows( $result_set );
    $val = mysqli_fetch_object( $result_set );
    $email = $val->email;
    if( $email == "" ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "No email is associated with this account. Please assign an email to this account first !" );
        return;
    }

    $flag = false;
    $send_email = false;

    $password_reset_code = $val->password_reset_code;
    $password_reset_expiry = $val->password_reset_expiry;
    $current_time = currentTimeMilliseconds();

    /*
     * 1. password_reset_code is empty, create fresh password reset code, and current timestamp -> send email, update in DB
     * 2. password_reset_code is present. If password_reset_expiry is MORE than 24 hours, create fresh password reset code, and current timestamp -> send email, update in DB
     * 3. password_reset_code is present. If password_reset_expiry is LESS than 24 hours, send email
     *
     */

    // echo "password_reset_code : $password_reset_code ";
    if( trim( $password_reset_code ) === "" ){
        $password_reset_code 	= md5( $current_time );
        $password_reset_expiry  = $current_time;
        // $send_email = true;
    }
    else{
        if( isPasswordResetValidityExpired( $current_time, $password_reset_expiry ) ){
            // create new reset code and current time as interval
            $password_reset_code 	= md5( $current_time );
            $password_reset_expiry  = $current_time;
            // $send_email = true;
        }
    }

    $sql = "UPDATE users SET password_reset_code = '$password_reset_code', password_reset_expiry = '$password_reset_expiry' WHERE user_id = '$user_id'";
    $rows = updateQuery( $sql );
    if( $rows > 0 ){
        // Send the password reset email
        // sendMail( $email, $from, "Password Reset", "use template" );
        $mail = sendMailObject();
        $mail->isHTML( true );
        $mail->setFrom( EMAIL_NOREPLY, 'noreply' );
        $mail->AddAddress( $email );
        $mail->Subject = 'Password Reset request initiated on behalf of the administrator';

        $password_reset_link = WEBSITE_PROTOCOL . "://" . WEBSITE_DOMAIN_NAME . "password_reset.php?code=$password_reset_code";
        $message = file_get_contents( "templates/email/password_reset.php" );
        $message = str_replace( "{{password_reset_url}}", $password_reset_link, $message );
        $message = str_replace( "{{password_reset_url_html}}", htmlentities( $password_reset_link ), $message );

        $mail->Body = $message;
        if( ! $mail->Send() ) {
            echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Failed to send password reset email !" );
        }
        else{
            echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, "Password reset information has been sent to $email" );
        }
        return;
    }

    echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Error occurred !" );
    return;
}

/**
 * 
 * Change the password on the Password Reset Page. Don't need to have privileges !
 * 
 */
function change_reset_password() {

    if (!isForgotPasswordOptionEnabled()) {
        echo createJSONMessage(GENERAL_ERROR_MESSAGE, __FUNCTION__, "Forgot Password Option is Disabled in the Site Configuration !");
        return;
    }

    /**
     * 
     * 1. If `password_reset_code` parameter empty, Send error
     * 2. if `password_reset_code` parameter is set, if password_reset_code does not exist in DB , Display error -> Password reset Link has been expired
     * 3. if `password_reset_code` parameter is set, if password_reset_expiry >= 24 , Display error -> Password reset Link has been expired
     * 4. if `password_reset_code` parameter is set, if password_reset_expiry < 24, check for passwords 
     * 
     */
    // print_r( $_REQUEST );
    // 1.
    if (!isset($_REQUEST['password_reset_code'])) {
        echo createJSONMessage(GENERAL_ERROR_MESSAGE, __FUNCTION__, "Something went wrong : err1");
        return;
    }

    // 2.
    $password_reset_code = $_REQUEST['password_reset_code'];
    $sql = "SELECT user_id, password_reset_expiry, email FROM users WHERE password_reset_code='$password_reset_code'";
    $result_set = selectQuery($sql);

    if (mysqli_num_rows($result_set) == 0) {
        echo createJSONMessage(GENERAL_ERROR_MESSAGE, __FUNCTION__, "Password Reset Link has been expired, please redo the password reset process again on the Login Page");
        return;
    }

    // 3.
    $val = mysqli_fetch_object($result_set);
    $password_reset_expiry = $val->password_reset_expiry;
    $current_time = currentTimeMilliseconds();

    if (isPasswordResetValidityExpired($current_time, $password_reset_expiry)) {
        echo createJSONMessage(GENERAL_ERROR_MESSAGE, __FUNCTION__, "Password Reset Link has been expired, please redo the password reset process again on the Login Page");
        return;
    }

    // 4.
    $password_new = $_REQUEST['password_new'];
    $password_new_repeat = $_REQUEST['password_new_repeat'];

    validateEmptyString($password_new, __FUNCTION__, "Password cannot be empty !");
    validate($password_new, __FUNCTION__, getValidationRegex("VLDTN_PASSWORD"), getValidationErrMsg("VLDTN_PASSWORD"));

    validateEmptyString($password_new_repeat, __FUNCTION__, "Re Entered Password cannot be empty !");
    validate($password_new_repeat, __FUNCTION__, getValidationRegex("VLDTN_PASSWORD"), getValidationErrMsg("VLDTN_PASSWORD"));

    if ($password_new != $password_new_repeat) {
        echo createJSONMessage(GENERAL_ERROR_MESSAGE, __FUNCTION__, "Password and Re Entered Password Do Not Match !");
        return;
    }

    $password_new = md5($password_new);

    $sql = "UPDATE users SET password='$password_new', password_reset_expiry='', password_reset_code='' WHERE password_reset_code='$password_reset_code'";
    $rows = updateQuery($sql);
    if ($rows > 0) {
        echo createJSONMessage(GENERAL_SUCCESS_MESSAGE, __FUNCTION__, "Password Changed Successfully !");
        return;
    }

    echo createJSONMessage(GENERAL_ERROR_MESSAGE, __FUNCTION__, "Something went wrong : errX");
    return;
}

?>