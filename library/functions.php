<?php

//sessionRevalidate();
@session_start();
$con = null;


define('PAGE_NAME_LOGIN', 'login');
define('PAGE_LOGIN', 'login.php');
define('PAGE_FORGOT_PASSWORD', 'forgot-password.php');

define('PAGE_NAME_ADMIN', 'adminpanel');

define('PAGE_NAME_404', '404error');
define('PAGE_404', '404error.php');

// WEBSITE DETAILS
initWebsite();
define('WEBSITE_ADMINPANEL_URL', "adminpanel" . WEBSITE_LINK_ENDS_WITH);
define('WEBSERVICE_URL', "webservice" . WEBSITE_LINK_ENDS_WITH);
define('URL_MAINTENANCE_PAGE', 'http://' . WEBSITE_DOMAIN_NAME . "maintenance.php");
$VLDTN = getAllValidationConstants();

function connect() {
    require( dirname(__DIR__) . "/configurations/config.php" );
    global $con;

    if (empty($host) || empty($database_username)) {
        $error = createJSONMessage(GENERAL_ERROR_MESSAGE, "config-file", "config.php file not yet configured !");
        echo $error;
        return;
    }
    if ($con == null) {
        $con = mysqli_connect($host, $database_username, $database_password) or die('Cannot connect to database');
        mysqli_set_charset($con, 'utf8');
        mysqli_select_db($con, $database_name) or die('cannot select database ' + $database_name);
    }
}

function createId($prefix) {
    /*
     * Creates a randomly generated ID with a prefix of your choice
     * $prefix -> an identifier of your choice followed by 10 Digit Random Number
     */
    $id = $prefix . "-" . rand(1, 99999) . rand(1, 99999);
    return $id;
}

function createMessage($type, $for, $info) {
    /*
     * Generates an Array of Error or Success information
     *
     * $type -> Identifier of the message ; success or error
     * $for  -> Purpose/Task
     * $info -> Custom message associated with the identifier and purpose
     *
     */
    $arr = array();
    array_push($arr, array("type" => $type, "for" => $for, "info" => $info));
    return $arr;
}

function createJSONMessage($type, $for, $info, $json_extra_param = JSON_UNESCAPED_UNICODE) {
    /*
     * Generates a Json Encoded Array of Error or Success information
     * 
     * $type -> Identifier of the message ; success or error
     * $for  -> Purpose/Task
     * $info -> Custom message associated with the identifier and purpose
     * 
     */
    $arr = array();
    array_push($arr, array("type" => $type, "for" => $for, "info" => $info));
    return json_encode($arr, $json_extra_param);
}

function isValidFileExtension($file_url, $allowed_file_extensions) {
    /*
     * To check if the file extension is allowed
     * $file_url -> URL or name of the file
     * $allowed_file_extensions -> Array of valid file extensions
     *
     */
    $file_extension = substr($file_url, strpos($file_url, ".") + 1);
    //for( $i=0; $i<count( $allowed_file_extensions ); $i++ ){
    if (!in_array($file_extension, $allowed_file_extensions)) {
        return false;
    }
    return true;
    //}
}

function getFileExtension($file_url) {
    /*
     * To get the file extension of the File that has been input
     * $file_url -> URL or name of the file
     *
     */
    return substr($file_url, strpos($file_url, ".") + 1);
}

function getFileName($file_url) {
    /*
     * To get the file name of the File URL that has been input, removing the slashes and its extension
     * $file_url -> URL or name of the file
     *
     */
    if (( $pos = strpos($file_url, "/") ) != false)
        return substr($file_url, $pos + 1, strpos($file_url, ".") - $pos - 1);

    return substr($file_url, 0, strpos($file_url, "."));
}

function rawQuery($sql) {
    /*
     * Fires the Generic Query over the database handle opened using $con object
     * $sql -> The query to be fired
     *
     */
    connect();
    global $con;
    mysqli_query($con, $sql) or ( $error = createJSONMessage(GENERAL_ERROR_MESSAGE, QUERY_FIRE_ERROR, mysqli_error($con)) );

    if (@$error == NULL)
        $error = array();

    if (count(@$error) > 0) {
        echo $error;
        return;
    }
}

function selectQuery($sql) {
    /*
     * Fires the Select Query over the database handle opened using $con object
     * $sql -> The query to be fired
     * 
     */
    connect();
    global $con;
    $result_set = mysqli_query($con, $sql) or ( $error = createJSONMessage(GENERAL_ERROR_MESSAGE, QUERY_FIRE_ERROR, mysqli_error($con)) );

    if (@$error == NULL)
        $error = array();

    if (count(@$error) > 0) {
        echo $error;
        return;
    }

    return $result_set;
}

function insertQuery($query) {
    /*
     * Fires the Insert Query over the database handle opened using $con object
     * $query -> The query to be fired
     *
     */
    connect();
    global $con;
    $num_rows_inserted = mysqli_query($con, $query) or ( $error = createJSONMessage(GENERAL_ERROR_MESSAGE, QUERY_FIRE_ERROR, mysqli_error($con)) );

    if (@$error == NULL)
        $error = array();

    if (count(@$error) > 0) {
        echo $error;
        return;
    }

    return $num_rows_inserted;
}


/**
 * 
 * Executes the Update Query on the Database
 * 
 * @global mysqli $con MySQL connection object
 * @param string $sql The SQL query
 * @return boolean true on success, false on failure
 * @return array JSONArray containing the failure message
 */        
function updateQuery( $sql ) {    
    connect();
    global $con;
    $status = mysqli_query( $con, $sql ) or ( $error = createJSONMessage(GENERAL_ERROR_MESSAGE, QUERY_FIRE_ERROR, mysqli_error($con)) );

    if (@$error == NULL)
        $error = array();

    if (count(@$error) > 0) {
        echo $error;
        return;
    }

    return $status;
}

function deleteQuery($query) {
    /*
     * Fires the Delete Query over the database handle opened using $con object
     * $query -> The query to be fired
     *
     */
    connect();
    global $con;
    $num_rows_deleted = mysqli_query($con, $query) or ( $error = createJSONMessage(GENERAL_ERROR_MESSAGE, QUERY_FIRE_ERROR, mysqli_error($con)) );

    if (@$error == NULL)
        $error = array();

    if (count(@$error) > 0) {
        echo $error;
        return;
    }

    return $num_rows_deleted;
}

function redirect($relative_destination_url) {
    /*
     * Redirects to $relative_destination_url
     * The URL should be relative to the current page
     * 
     */
    header( 'Location: ' . $relative_destination_url );
    exit();
    //echo '<script>window.location.href="' . $relative_destination_url . '"</script>';
}

function bin2base64($file_url) {
    /*
     * Outputs the Base64 Encoded Version of Input File
     * $file_url -> Input file URL
     */
    $binary_file = file_get_contents($file_url);
    echo base64_encode($binary_file);
}

function containSpecialCharacters($string, $special_chars = array("'", '"', "\\", ".", "+", ",")) {
    /*
     * Checks if the String contains Special Characters supplied in $special_chars aray
     * $string -> String to be checked fo presence of special chars
     * $special_chars -> Array containing special chars which has to be checked for their presence
     * 
     */

    //$special_chars = array("'", '"', "\\", ".", "+", ",");
    if (gettype($special_chars) != "array") {
        $special_chars = array("'", '"', "\\", ".", "+", ",");
    }

    for ($i = 0; $i < count($special_chars); $i++) {
        if (strstr($string, $special_chars[$i])) {
            return true;
        }
    }
    return false;
}

function resizeImage($file_url, $required_width, $required_height, $select_width) {
    /*
     * To resize Image specified by the URL $file_url with the Proportions Constrained
     * $file_url -> URL of the image
     * $required_width -> Desired width of the image, so that height will be adjusted automatically
     * $required_height -> Desired width of the image, so that width will be adjusted automatically
     * $select_width -> Boolean, if true, Only width will be considered, if False, only Height will be considered
     * 
     * Returns a $img_new (gd image) object which can be passed to imagepng( $img_new, $tmp_image ) to save the new resized image to $tmp_image URL
     *
     */

    $size = getimagesize($file_url);
    $width = $size[0];
    $height = $size[1];

    if ($select_width) {
        $factor = $width / $required_width;
        $new_width = $width / $factor;
        $new_height = $height / $factor;
    } else {
        $factor = $height / $required_height;
        $new_height = $height / $factor;
        $new_width = $width / $factor;
    }
    /* echo "Original Width : " . $size[0] . "<br>";
      echo "Original Height : " . $size[1] . "<br>";
      echo "New Width : " . $new_width . "<br>";
      echo "New Height : " . $new_height . "<br>";
     */
    $image_extension = getFileExtension($file_url);

    // implement switch case for png, jpg, gif, bmp
    switch ($image_extension) {
        case "jpg":
        case "jpeg":
        case "JPG":
        case "JPEG":
            $src = imagecreatefromjpeg($file);
            break;

        case "png":
        case "PNG":
            $src = imagecreatefrompng($file);
            break;

        case "gif":
        case "GIF":
            $src = imagecreatefromgif($file);
            break;

        case "bmp":
        case "BMP":
            $src = imagecreatefromwbmp($file);
            break;
    }

    $img_new = imagecreatetruecolor($new_width, $new_height);
    imagecopyresampled($img_new, $file_url, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
    //imagepng( $dst, $file );  // save the $dst i.e new small image to the same file $file

    return $img_new;
}

function convertTimestamp($timestamp, $format) {
    /*
     * Refer ->http://php.net/manual/en/function.time.php
     * 
     * Converts the timestamp in milliseconds retrieved from the Database into specified $format
     * $timestamp -> Timestamp value taken from the database
     * $format -> Output to be seen e.g: 09 July, 1992 i.e. $format = "d F, Y"
     * 
     * Returns the string representation of the Formatted timestamp
     * 
     */

    $d = date($format, (int) ($timestamp / 1000));

    return $d;
}

function uploadBinaryFileFromAndroid($file_name, $file_extension, $directory) {
    /*
     * Uploads a Binary File to the same directory from Android Device
     * 
     * $file_name -> File to be Named As
     * $file_extension -> Extension for the File
     * $directory -> Directory name where file has to be moved after getting uploaded
     * 
     * Returns the Constructed FileName
     */

    $filename .= "." . $file_extension;
    $fileData = file_get_contents('php://input');
    $fhandle = fopen($filename, 'wb');
    fwrite($fhandle, $fileData);
    fclose($fhandle);
    rename($file_name, $directory . FILE_SEPARATOR . $file_name);
    //copy( $filename, "./$move_to/" . $filename );
    // delete from here
    echo $filename;
}

function sendMail($to, $from, $subject, $message) {

    //Headers
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: <" . $from . ">";

    mail($to, $subject, $message, $headers);
}

function sendMailObject() {
    /*
    require_once( "PHPMailer/class.phpmailer.php" );
    require_once( "PHPMailer/class.smtp.php" );
    */

    require 'PHPMailer/vendor/autoload.php';
    
    
    //$mail = new PHPMailer();
    $mail = new \PHPMailer\PHPMailer\PHPMailer();
    $mail->IsSMTP();  // telling the class to use SMTP
    $mail->SMTPAuth = true;                               // Enable SMTP authentication
    $mail->Host         = EMAIL_SERVER_HOST; // SMTP server
    $mail->Username     = EMAIL_NOREPLY;                 // SMTP username
    $mail->Password     = EMAIL_NOREPLY_PASSWORD;                           // SMTP password
    $mail->SMTPSecure   = EMAIL_PROTOCOL;
    $mail->Port         = EMAIL_SMTP_PORT;

    //$mail->setFrom( EMAIL_NOREPLY, 'SC FRAMEWORK.' );
    //$mail->addCC( EMAIL_ADMIN );				// Sending the copy to the administrator
    //$mail->isHTML( true );

    return $mail;
}

function getPageName($url_type) {
    /*
     * Returns the name of the Curent Page Stripping off all the other parts of the URL
     *
     * $url_type -> the type of URL that is Provided as Input (currently on clean is supported)
     *
     * Returns the name of the Current Page
     */

    if ($url_type == "clean") {
        $current_relative_path = $_SERVER['REQUEST_URI']; // /silentcoders/services.html	 --- On Server, it gives only /
        //echo $current_relative_path . "<br />";
        $page_name = substr($current_relative_path, strrpos($current_relative_path, "/") + 1);
        $page_name = substr($page_name, 0, strpos($page_name, "."));
        /*
          $page_name = substr( $current_relative_path, 0, strrpos( $current_relative_path, "." ) );
          $page_name = substr( $page_name, strrpos( $page_name, "/" ) + 1 );
         * 
         */
    }

    return $page_name;
}

function getDomainName() {
    /*
     * Returns the domain name without "www."
     * 
     */

    $host = $_SERVER['HTTP_HOST'];
    return ( strpos($host, "www.") ) ? ( trim($host, "www.") ) : $host;
}


function initWebsite() {
    /*
     * Initialize the DOMAIN_NAME constant
     *
     */
    connect();
    global $con, $email_server_host, $noreply_email, $noreply_password, $protocol, $port;
    // echo "{$email_server_host}, {$noreply_email}, {$noreply_password}";
    $sql = "Select * from site_config";
    $result_set = selectQuery($sql);
    $value = mysqli_fetch_array($result_set);

    define('WEBSITE_URL_TYPE', $value['url_type']); // No ? in the URL
    define('WEBSITE_LINK_ENDS_WITH', $value['link_ends_with']);
    define('WEBSITE_DOMAIN_NAME', $value['domain_name']);

    $index = ( ( $v = strpos($value['domain_name'], "/") ) == false ) ? strlen($value['domain_name']) : $v;
    define('WEBSITE_SUB_DOMAIN_DIRECTORY', substr($value['domain_name'], $index, strlen($value['domain_name'])));
    define('WEBSITE_PROTOCOL', $value['protocol']);
    define('EMAIL_SERVER_HOST', $email_server_host);
    define('EMAIL_NOREPLY', $noreply_email);
    define('EMAIL_NOREPLY_PASSWORD', $noreply_password);
    define('EMAIL_SMTP_PORT', $port );
    define('EMAIL_PROTOCOL', $protocol);
}

function getUrlParameters($url) {
    // $url = "/digital_signage/events.html?menu=create&oi=tou";
    // $url = "/digital_signage/events.html";
    // check if Question mark exist in the url
    if (strpos($url, "?") == false) { // false is returned if question mark is not found
        return false;
    } else {
        $params = substr($url, strpos($url, "?") + 1);
    }

    /*
      if( $params == $url ){  // no question mark found
      echo false;
      exit();
      } */

    $params2 = explode("&", $params);
    // print_r( $params2 );

    for ($i = 0; $i < count($params2); $i++) {
        $temp = explode("=", $params2[$i]);
        $params_arr[$temp[0]] = $temp[1];
    }

    /* if( $params2[ 0 ] == $params ){ // no & is found, meaning only one paramter exist
      $params3 = explode( "=", $params2[ 0 ] );
      $params_arr[ $params3[ 0 ] ] = $params3[ 1 ];
      // print_r( $params_arr );

      }
      else{
      for( $i = 0 ; $i < count( $params2 ) ; $i++ ){
      $temp = explode( "=", $params2[ $i ] );
      $params_arr[ $temp[ 0 ] ] = $temp[ 1 ];
      }
      } */

    // print_r( $params_arr );
    return $params_arr;
}

function generateQRFromText($text, $dest_image) {
    include "phpqrcode/qrlib.php";
    QRcode::png($text, $dest_image, 'XL', 6, 2);
    return $dest_image;
}

function havePrivilege($functionality_name, $functionality_id) {

    $role_id = $_SESSION[SESSION_AUTHORIZATION];

    if (( $functionality_id == false ) || ( trim($functionality_id) == "" )) {
        $sql = "Select role_id FROM roles_privileges WHERE privilege_id = 
						( SELECT privilege_id FROM privileges_functionalities WHERE functionality_id = 
						(SELECT functionality_id FROM functionalities WHERE functionality_name = '$functionality_name') ) AND role_id = $role_id";
    } else {
        $sql = "Select role_id FROM roles_privileges WHERE privilege_id =
			( SELECT privilege_id FROM privileges_functionalities WHERE functionality_id = $functionality_id ) AND role_id=$role_id";
    }

    $result_set = selectQuery($sql);

    if (mysqli_num_rows($result_set) == 0) {
        return false;
    }
    return true;
}

function equals($var1, $var2) {
    if ($var1 === $var2)
        return true;
    return false;
}

function contains($str, $part) {
    if (strpos($str, $part) == false)
        return false;
    return true;
}

function timeNow($format = "jS M, Y h:i A") {
    echo date($format, time());
}

function roleToRoleId($role_name) {
    switch ($role_name) {
        case "Non Registered Users":
            return 0;
        case "admin":
            return 1;
        case "staff":
            return 2;
        case "Registered User":
            return 10;
    }
}

/**
 *
 * This is a customized version of in_array() function, developed by Silent Coders Pvt Ltd
 * to search for an element in a multi dimension array
 *
 * @param String $needle The value to be searched
 * @param Array $haystack The array in which the value is to be searched
 * @param Boolean $strict [optional] if true, does case comparison
 *
 * @return boolean
 *
 */
function in_array_r($needle, $haystack, $strict = false) {
    foreach ($haystack as $item) {
        if (( $strict ? $item === $needle : $item == $needle ) || ( is_array($item) && in_array_r($needle, $item, $strict) )) {
            return true;
        }
    }
    return false;
}

/**
 * 
 * @param String $file_name The name of the php file in which the function is being called
 *
 */
function checkPrivilegeForPage($file_name) {
    if (!havePrivilege($file_name, false)) {
        echo createJSONMessage(GENERAL_ERROR_MESSAGE, __FUNCTION__, "You dont have sufficient privilege to view this page !");
        exit();
    }
}

/**
 *
 * @param String $function_name The name of the php function in which the function is being called
 *
 */
function checkPrivilegeForFunction($function_name) {
    if (!havePrivilege($function_name, false)) {
        echo createJSONMessage(GENERAL_ERROR_MESSAGE, __FUNCTION__, "You dont have sufficient privilege to perform this action !");
        exit();
    }
}

// Private function for internal calls only
function sign_out(){
    setcookie( TOKEN_NAME, "", time()-3600, "/" );
    //setcookie( REFRESH_TOKEN_NAME, "", time()-3600, "/" );
    setcookie( TOKEN_NAME, "", 1, "/");     // or we can try this too to unset the cookie
    //setcookie( REFRESH_TOKEN_NAME, "", 1, "/");     // or we can try this too to unset the cookie
    unset( $_COOKIE[ TOKEN_NAME ] );
    //unset( $_COOKIE[ REFRESH_TOKEN_NAME ] );
}

/**
 *
 * Checks if the user is logged in or not using Tokens
 * 
 * @return boolean true if user is logged in, false otherwise
 *
 */
function isLoggedIn(){
    global $globalAccessToken;
    
    // 1. Check if there is a cookie with access token inside
    if( !isset( $_COOKIE[ TOKEN_NAME ] ) ){
        sign_out();
        return false;
    }
    
    // 2. Check if the Token is valid
    $jwt = $_COOKIE[ TOKEN_NAME ];//$_COOKIE[ TOKEN_NAME ];
    if( !isValidJWT( $jwt ) ){
        sign_out();
        return false;
    }
    
    // 3. Check if the Token is expired
    if( isAccessTokenExpired( $jwt ) ){
        sign_out();
        return false;
    }
    
    // 4. If the user_id or role_id has been deleted from the DB, then logout the user
    $jwtData = getJWTPayload( $jwt );
    $sql = "SELECT user_id, role_id FROM users WHERE user_id='{$jwtData[ SESSION_USER_ID ]}'";
    $result_set = selectQuery( $sql );
    if( ($result_set === NULL) || (mysqli_num_rows( $result_set ) == 0) ){
        sign_out();
        return false;
    }
    else{
        $val = mysqli_fetch_object( $result_set );
        if( $jwtData[ SESSION_AUTHORIZATION ] !== $val->role_id ){
            sign_out();
            return false;
        }
    }
    
    return true;
}

/**
 *
 * Checks if the user is logged in or not using Sessions
 * 
 * @return boolean true if user is logged in, false otherwise
 *
 */
function isLoggedIn1() {
    @session_start();
    
    // 1. If the session_authorization is not set, means that the Login has not been initiated yet
    if( !isset( $_SESSION[ SESSION_AUTHORIZATION ] ) ){
        //@session_destroy();
        return false;
    }
    
    // 2. Check if Auto-Logout timeout has passed
    $session_timestamp = $_SESSION[ SESSION_ID_GENERATE_TIMESTAMP ];
    $currentMillis = currentTimeMilliseconds();
    $difference = $currentMillis - $session_timestamp;
    //echo $difference . ", " . SESSION_EXPIRY_DURATION;
    if( $difference > SESSION_EXPIRY_DURATION ){
        @session_destroy();
        //sessionRevalidate( true );
        return false;
    }
    
    
    // 3. New Session ID Auto regenerate interval
    //if( $difference > SESSION_ID_REGENERATE_INTERVAL_DEFAULT ){
    //    @session_regenerate_id();
    //}
    
    
    // 4. Compare the browsers
    if( isset( $_SESSION[ 'HTTP_USER_AGENT' ] )){
        if( $_SERVER[ 'HTTP_USER_AGENT' ] !== $_SESSION[ 'HTTP_USER_AGENT' ] ){
            //session_destroy();
            return false;
        }
    }
    
    // 5. If the user_id or role_id has been deleted from the DB, then logout the user
    $sql = "SELECT user_id, role_id FROM users WHERE user_id='{$_SESSION[ SESSION_USER_ID ]}'";
    $result_set = selectQuery( $sql );
    if( ($result_set === NULL) || (mysqli_num_rows( $result_set ) == 0) )
        return false;
    else{
        $val = mysqli_fetch_object( $result_set );
        if( $_SESSION[ SESSION_AUTHORIZATION ] !== $val->role_id ){
            return false;
        }
    }
    
    
    //print_r( $_SESSION );
    
    return true;
    // 5. Compare the IP Addresses  1234567
    // 
    /*
    if( $force ){
        //echo "here";
        @session_regenerate_id();
        $_SESSION[ SESSION_ID_GENERATE_TIMESTAMP ] = currentTimeMilliseconds();
    }
    else{
        if( !isset( $_SESSION[ SESSION_ID_GENERATE_TIMESTAMP ] ) ){
            //echo "here1";
            $_SESSION[ SESSION_ID_GENERATE_TIMESTAMP ] = currentTimeMilliseconds();
        }
        else{
            //echo "here2";
            //print_r( $_SESSION );
            $session_timestamp = $_SESSION[ SESSION_ID_GENERATE_TIMESTAMP ];
            $currentMillis = currentTimeMilliseconds();
            $difference = $currentMillis - $session_timestamp;
            //echo "difference: $difference," . SESSION_ID_REGENERATE_INTERVAL_DEFAULT;
            if( $difference > SESSION_ID_REGENERATE_INTERVAL_DEFAULT ){
                @session_regenerate_id( );
                $_SESSION[ SESSION_ID_GENERATE_TIMESTAMP ] = currentTimeMilliseconds();
            }
        }
        
    }
     * 
     */
    /*
    if (!@isset($_SESSION[SESSION_AUTHORIZATION])) { // If session variable is not set
        return false;
    }
    return true;
     * 
     */
}

/**
 * 
 * Checks if the user is logged in or not
 * If not logged in, user is redirected to the Login Page
 * 
 */
function checkIfLoggedIn() {
    //echo "not logged";
    if (!isLoggedIn()) {
        //echo "not logged in";
        @redirect(PAGE_LOGIN);
        return;
    }
}

/**
 * 
 * Only administrator can perform this operation. Both the parameters
 * cannot be false at the same time. At least one parameter is a must.
 * 
 * @param String/boolean $user_id User ID of the gonna be user
 * @param int/boolean $role_id Role ID of the gonne be user
 * 
 * @return true If the current user is admin. false If the current user is not the admin
 * 
 */
function onlyAdminCan($user_id, $role_id) {
    if (( $role_id == false ) || ( trim($role_id) == "" )) {
        // Get the role_id of the gonna be user
        $sql = "SELECT role_id FROM users WHERE user_id = '$user_id'";
        $result_set = selectQuery($sql);
        if (mysqli_num_rows($result_set) <= 0) {
            echo createJSONMessage(GENERAL_ERROR_MESSAGE, __FUNCTION__, "User ID does not exist");
            return true;
        }
        $val = mysqli_fetch_object($result_set);
        $role_id = $val->role_id;
    }

    if (( $role_id == roleToRoleId(SESSION_ADMIN) ) && // if role to be edited is of admin
            ( $_SESSION[SESSION_AUTHORIZATION] != roleToRoleId(SESSION_ADMIN) )) {  // if change karne wala is not the admin
        echo createJSONMessage(GENERAL_ERROR_MESSAGE, __FUNCTION__, "Youu dont have sufficient privileges to perform this operation !");
        return true;
    }
    return false;
}

/**
 * 
 * Returns the milliseconds of the current date and time
 * 
 */
function currentTimeMilliseconds() {
    return number_format(round(microtime(true) * 1000), 0, "", "");
}

/**
 * 
 * Deletes the directory recursively
 * 
 * @param String $dir path to the directory
 * @return boolean
 * 
 */
function deleteDirectoryRecursive($dir) {
    $files = array_diff(scandir($dir), array('.', '..'));
    //print_r( $files );
    //if(is_array( $files ) )
    foreach ($files as $file) {
        ( is_dir("$dir/$file") ) ? deleteDirectoryRecursive("$dir/$file") : unlink("$dir/$file");
    }
    return rmdir($dir);
}

/**
 * 
 * Gets all the site_config data from the database table
 * 
 * @return Object array of site_config information
 * 
 */
function getSiteConfig() {
    $sql = "Select * FROM site_config";
    $result_set = selectQuery($sql);
    return mysqli_fetch_object($result_set);
}

/**
 * Validates the empty string
 *
 * @param String $str The input string to validate for emptiness
 * @param String $err_msg The error message to echo if not validated
 *
 * @return JSON array of error if not validated, else returns nothing
 *
 */
function validateEmptyString($str, $for, $err_msg) {
    if( $str === NULL ){  // When the URL parameter is missing
        //send_json_mime_type_header();
        echo createJSONMessage(GENERAL_ERROR_MESSAGE, $for, $err_msg);
        exit();
    }
    if (empty($str) || ( trim($str) == "" )) {
        //send_json_mime_type_header();
        echo createJSONMessage(GENERAL_ERROR_MESSAGE, $for, $err_msg);
        exit();
    }
}

/**
 * Validates the empty Digits Variable
 *
 * @param String $str The input digit string to validate for emptiness
 * @param String $err_msg The error message to echo if not validated
 *
 * @return JSON array of error if not validated, else returns nothing
 *
 */
function validateEmptyDigitString($str, $for, $err_msg) {
    if( $str === NULL ){  // When the URL parameter is missing
        //send_json_mime_type_header();
        echo createJSONMessage(GENERAL_ERROR_MESSAGE, $for, $err_msg);
        exit();
    }
    if (( $str != 0)) {
        if (empty($str) || ( trim($str) == "" )) {
            //send_json_mime_type_header();
            echo createJSONMessage(GENERAL_ERROR_MESSAGE, $for, $err_msg);
            exit();
        }
    }
}

/**
 * Validates the string for the supplied regex pattern
 *
 * @param String $str The input string to validate for emptiness
 * @param String $regex The regex pattern to validate the intput string $str
 * @param String $err_msg The error message to echo if not validated
 *
 * @return JSON array of error if not validated, else returns nothing
 *
 */
function validate($str, $for, $regex, $err_msg) {
    if( $str === NULL ){  // When the URL parameter is missing
        //send_json_mime_type_header();
        echo createJSONMessage(GENERAL_ERROR_MESSAGE, $for, $err_msg);
        exit();
    }
    if (!preg_match($regex, $str)) {
        //send_json_mime_type_header();
        echo createJSONMessage(GENERAL_ERROR_MESSAGE, $for, $err_msg);
        exit();
    }
}

/**
 *
 * Show a javascript alert
 *
 */
function alert($msg) {
    echo "<script>alert( '$msg' )</script>";
}

/**
 *
 * Checks if the current user has already logged in. If not, then the role for this user is set as Not-Registered
 *
 */
function setNonRegisteredUser() {
    if (!@isset($_SESSION[SESSION_AUTHORIZATION])) {
        $_SESSION[SESSION_USER_ID] = "not_registered";
        $_SESSION[SESSION_AUTHORIZATION] = roleToRoleId("Non Registered Users");
        return;
    }
}

/**
 *
 * Search for a value in the 2D array
 * http://stackoverflow.com/questions/6661530/php-multi-dimensional-array-search
 *
 * @params $id Value to be searched
 * @params $array The 2D array to search the value in
 * @params $field The filed inside the 2D array to be searched for the value
 *
 * @return index of the 1st dimension of the array if value is found, else null
 *
 *
 */
function searchForId($id, $array, $field) {
    foreach ($array as $key => $val) {
        if ($val[$field] == $id) {
            return $key;
        }
    }
    return null;
}

/**
 * 
 * Retrieves all validation variables and regex expressions
 * 
 * @return Array of Validation variables, type and regex expressions
 * 
 */
function getAllValidationConstants() {
    require( dirname(__DIR__) . "/configurations/config.php" );

    global $validation_array;
    // print_r( $validation_array );

    return $validation_array;
}

/**
 * 
 * Check if the registrations are open or not
 * 
 * @return Boolean true if the registration is open, false otherwise
 * 
 */
function isRegistrationOpen() {
    $sql = "SELECT is_registration_open FROM site_config";
    $result_set = selectQuery($sql);
    if (mysqli_num_rows($result_set) == 0) {
        echo createJSONMessage(GENERAL_ERROR_MESSAGE, __FUNCTION__, "No such field in Database");
        return;
    }
    $val = mysqli_fetch_object($result_set);

    return ($val->is_registration_open == 0) ? false : true;
}

/**
 *
 * Check if the Forgot Password Option is enabled 
 *
 * @return Boolean true if the Forgot Password Option is open, false otherwise
 *
 */
function isForgotPasswordOptionEnabled() {
    $is_enabled = getConfigurationValue( _FORGOT_PASSWORD_FEATURE );
    if( $is_enabled === NULL ){
        setConfigurationValue( _FORGOT_PASSWORD_FEATURE, _FORGOT_PASSWORD_FEATURE_DEFAULT );
        return false;
    }
    
    return ($is_enabled) ? true : false;
}

/**
 * 
 * Returns the REGEX expression for the supplied Validation Constant
 * 
 * @param String Validation Constant
 * 
 * @return String REGEX pattern for the supplied Validation Constant
 * 
 * 
 */
function getValidationRegex($VLDTN_CONSTANT) {
    $arr = getAllValidationConstants();

    return $arr[$VLDTN_CONSTANT]['REGEX'];
}

/**
 *
 * Returns the Error Message for the supplied Validation Constant
 *
 * @param String Validation Constant
 *
 * @return String Error Message for the supplied Validation Constant
 *
 *
 */
function getValidationErrMsg($VLDTN_CONSTANT) {
    $arr = getAllValidationConstants();

    return $arr[$VLDTN_CONSTANT]['ERR_MSG'];
}

/**
 * 
 * Check if the password reset expiry time has been already passed
 * 
 * @param Long milliseconds $current_time
 * @param Long milliseconds $password_reset_expiry_time
 * 
 * @return false if the difference between the $current_time and $password_reset_expiry_time is within valid range, true otherwise
 */
function isPasswordResetValidityExpired($current_time, $password_reset_expiry_time) {
    $validity = 24; // Hours
    $difference = ( $current_time - $password_reset_expiry_time ) / ( 1000 * 60 * 60 );
    if ($difference >= $validity) { // Expired
        return true;
    }
    return false;
}

/**
 * 
 * Generate a Random Alphanumeric String
 * 
 * @param Int Number of characters of password, max 32. $max_chars
 * 
 * @return String Random alphanumeric string
 */
function getRandomString($max_chars) {
    return substr(md5(rand()), 0, $max_chars);
}

/**
 * 
 * Generate a Random Numeric 5 digit number
 * 
 * @return String Random numeric string
 */
function generateOTP() {
    return rand( 10000, 99999 );
}

/**
 * 
 * Generate the Loading Content HTML
 * 
 * @param String $class Class name of the top-level div
 * 
 * @param String $loading_text The text to be shown for loading alias
 * 
 * @param boolean $is_hidden Whether by default the loading div is hidden
 * 
 * @param String $position left, right, center The positioning of the loading animation
 * 
 * @param Int $height The height of the loading animation, width will be adjusted automatically
 * 
 * @return String HTML for the loading animation
 * 
 */
function getLoadingHTML($class, $loading_text, $is_hidden, $position, $height) {
    $hidden = ( $is_hidden ) ? "hidden" : "";
    $data = "";
    if (( $position == "left" ) || ( $position == "right" )) {
        $data = "<div class=\"$class $hidden\" style=\"float: $position\">";
        $data .= "<img src=\"images/small-loading.gif\" style=\"height: " . $height . "px;\" />";
        $data .= "<p>Loading Content...</p>";
        $data .= "</div>";
    } else if (( $position == "center")) {
        $data = "<center><div class=\"$class $hidden\">";
        $data .= "<img src=\"images/small-loading.gif\" style=\"height: " . $height . "px;\" />";
        $data .= "<p>Loading Content...</p>";
        $data .= "</div>";
        $data .= "</center>";
    }
    echo $data;
}

/**
 * 
 * @global type $con
 * @return Returns the Auto Increment ID generated by the Insert Query's last insert
 * 
 */
function getAIID() {
    connect();
    global $con;

    return mysqli_insert_id($con);
}

function millisToHumanReadableDate($millis) {
    //echo date( "d M, Y H:i:s", $millis/1000 );
    return date("d M, Y H:i:s", $millis / 1000);
}


/***
 * Generates the relative path for the Plugin's page
 * 
 * @param type $type The type of the file (File extension)
 * 
 * @return String The relative path for the js/css file for the plugin's php page
 */
function includePluginPageFile( $type, $pluginName, $fileMagicConstant ){
    
    if( $type == "css" ){
        $path = "plugins/$pluginName/css/" . basename( $fileMagicConstant, ".php" ) . ".css";
    }
    else if( $type == "js" ){
        $path = "plugins/$pluginName/js/" . basename( $fileMagicConstant, ".php" ) . ".js";
    }
    
    return $path;
   
}


function escape_string( $var ){
    global $con;
    if( $var === NULL ){
        $var = "";
    }
    return mysqli_real_escape_string( $con, $var );
}


function getRoleIdFromSlug( $slug_name ){
    
    $sql = "SELECT role_id FROM roles WHERE ( (role_slug='$slug_name') || (role_slug='staff') ) ORDER BY role_id";
    $result_set = selectQuery( $sql );
    if( mysqli_num_rows( $result_set ) == 1 ){
        $val = mysqli_fetch_object( $result_set );
        return $val->role_id; 
    }
    mysqli_data_seek( $result_set, 1 );
    $val = mysqli_fetch_object( $result_set );
    return $val->role_id;
    
}

/**
 * Generates the HASH for the input $passwordString
 * Currently Blowfish is being used for Hashing
 * 
 * @param string $passwordString The string to be hashed
 */
function hashPassword( $passwordString ){
    return password_hash( $passwordString, PASSWORD_BCRYPT );    // Blowfish
}


/**
 * This function is connected with the configurations table of the Database
 * It takes a parameter which is the config_key which is the column in the Database 
 * 
 * @param string $config_key The unique key representing a record in the configurations table
 * @return mixed config_value for the corresponding config_key in the table configurations. NULL if there is no record for the config_key
 */
function getConfigurationValue( $config_key, $default_value = NULL ){
    $sql = "SELECT * FROM configurations WHERE config_key='$config_key'";
    $result_set = selectQuery( $sql );
    if( $result_set == NULL ){
        return $default_value;
    }
    
    if( mysqli_num_rows( $result_set ) == 0 ){
        return $default_value;
    }
    
    $val = mysqli_fetch_assoc( $result_set );
    return $val[ 'config_value' ];    
}

/**
 * This function is connected with the configurations table of the Database
 * It takes a parameter which is the config_key and config_value and stores it as a combined record in the DB
 * 
 * @param string $config_key The unique key representing a record in the configurations table
 * @param string $config_value The value pertaining to the unique key
 * @return bool TRUE on successful insert/update. FALSE otherwise
 */
function setConfigurationValue( $config_key, $config_value ){
    $sql = "SELECT * FROM configurations WHERE config_key='$config_key'";
    $result_set = selectQuery( $sql );
    if( ($result_set == NULL) || 
            (mysqli_num_rows( $result_set ) == 0) ){
        $sql = "INSERT INTO configurations( `config_key`, `config_value` ) "
                . "VALUES( '$config_key', '$config_value' )";
        $rows = insertQuery( $sql );
        if( $rows > 0 ){
            return true;
        }
    }
    else{
        $sql = "UPDATE configurations SET config_value='$config_value' WHERE config_key='$config_key'";
        $rows = updateQuery( $sql );
        if( $rows > 0 ){
            return true;
        }
    }
    return false;
}


/**
 * This function will create or update a record in the User Meta table
 * It will first check if a combination of $user_id and $umeta_key exist.
 * If exist, it will update the $umeta_value, else it will create a fresh record
 * 
 * @param string $user_id The user_id of the user for which the record is being created
 * @param string $umeta_key The unique key for the user_id to identify the purpose of the record
 * @param string $umeta_value The value for the record
 * @return bool True on successful entry or update. False on failure
 */
function setUserMetaValue( $user_id, $umeta_key, $umeta_value ){
    // Check if a combination of $user_id and $umeta_key already exist
    $sql = "SELECT * FROM user_meta WHERE (user_id='$user_id') AND (umeta_key='$umeta_key')";
    $result_set = selectQuery( $sql );
    if( $result_set !== NULL ){
        if( mysqli_num_rows( $result_set ) > 0 ){
            $sql = "UPDATE user_meta SET umeta_value='$umeta_value' WHERE ((user_id='$user_id') AND (umeta_key='$umeta_key'))";
            $rows = updateQuery( $sql );
            if( $rows > 0 ){
                return true;
            }
            return false;
        }
    }
    
    // Fresh insert into the user_meta table
    $sql = "INSERT INTO user_meta( `user_id`, `umeta_key`, `umeta_value` ) "
            . "VALUES( '$user_id', '$umeta_key', '$umeta_value' )";
    $rows = insertQuery( $sql );
    if( $rows > 0 ){
        return true;
    }
    return false;
}

/**
 * Remove a record from the DB user_meta table for the given $user_id and $umeta_key
 * 
 * @param string $user_id The user_id of the user for which the record is being deleted
 * @param string $umeta_key The unique key for the user_id to identify the purpose of the record
 * @return bool True on successful delete. False on failure
 */
function deleteUserMetaValue( $user_id, $umeta_key ){
    // Check if a combination of $user_id and $umeta_key already exist
    $sql = "SELECT * FROM user_meta WHERE (user_id='$user_id') AND (umeta_key='$umeta_key')";
    $result_set = selectQuery( $sql );
    if( $result_set !== NULL ){
        if( mysqli_num_rows( $result_set ) > 0 ){
            $sql = "DELETE FROM user_meta WHERE ((user_id='$user_id') AND (umeta_key='$umeta_key'))";
            $rows = deleteQuery( $sql );
            if( $rows > 0 ){
                return true;
            }
            return false;
        }
    }
    return false;
}

/**
 * Retrieve a record from the DB user_meta table for the given $user_id and $umeta_key
 * 
 * @param string $user_id The user_id of the user for which the record is being retrieved
 * @param string $umeta_key The unique key for the user_id to identify the purpose of the record
 * @return mixed value if exists else NULL
 */
function getUserMetaValue( $user_id, $umeta_key ){
    // Check if a combination of $user_id and $umeta_key already exist
    $sql = "SELECT * FROM user_meta WHERE (user_id='$user_id') AND (umeta_key='$umeta_key')";
    $result_set = selectQuery( $sql );
    if( $result_set !== NULL ){
        if( mysqli_num_rows( $result_set ) > 0 ){
            $val = mysqli_fetch_object( $result_set );
            return $val->umeta_value;
        }
    }
    return NULL;
}


/**
 * This function will create or update a record in the App Meta table
 * It will first check if a combination of $app_id and $ameta_key exist.
 * If exist, it will update the $ameta_value, else it will create a fresh record
 * 
 * @param string $app_id The app_id of the App for which the record is being created
 * @param string $ameta_key The unique key for the app_id to identify the purpose of the record
 * @param string $ameta_value The value for the record
 * @return bool True on successful entry or update. False on failure
 */
function setAppMetaValue( $app_id, $ameta_key, $ameta_value ){
    // Check if a combination of $app_id and $ameta_key already exist
    $sql = "SELECT * FROM app_meta WHERE (app_id='$app_id') AND (ameta_key='$ameta_key')";
    $result_set = selectQuery( $sql );
    if( $result_set !== NULL ){
        if( mysqli_num_rows( $result_set ) > 0 ){
            $sql = "UPDATE app_meta SET ameta_value='$ameta_value' WHERE ((app_id='$app_id') AND (ameta_key='$ameta_key'))";
            $rows = updateQuery( $sql );
            if( $rows > 0 ){
                return true;
            }
            return false;
        }
    }
    
    // Fresh insert into the app_meta table
    $sql = "INSERT INTO app_meta( `app_id`, `ameta_key`, `ameta_value` ) "
            . "VALUES( '$app_id', '$ameta_key', '$ameta_value' )";
    $rows = insertQuery( $sql );
    if( $rows > 0 ){
        return true;
    }
    return false;
}

/**
 * Remove a record from the DB app_meta table for the given $app_id and $ameta_key
 * 
 * @param string $app_id The app_id of the app for which the record is being deleted
 * @param string $ameta_key The unique key for the app_id to identify the purpose of the record
 * @return bool True on successful delete. False on failure
 */
function deleteAppMetaValue( $app_id, $ameta_key ){
    // Check if a combination of $app_id and $ameta_key already exist
    $sql = "SELECT * FROM app_meta WHERE (app_id='$app_id') AND (ameta_key='$ameta_key')";
    $result_set = selectQuery( $sql );
    if( $result_set !== NULL ){
        if( mysqli_num_rows( $result_set ) > 0 ){
            $sql = "DELETE FROM app_meta WHERE ((app_id='$app_id') AND (ameta_key='$ameta_key'))";
            $rows = deleteQuery( $sql );
            if( $rows > 0 ){
                return true;
            }
            return false;
        }
    }
    return false;
}

/**
 * Retrieve a record from the DB app_meta table for the given $app_id and $ameta_key
 * 
 * @param string $app_id The app_id of the app for which the record is being retrieved
 * @param string $ameta_key The unique key for the app_id to identify the purpose of the record
 * @return mixed value if exists else NULL
 */
function getAppMetaValue( $app_id, $ameta_key ){
    // Check if a combination of $app_id and $ameta_key already exist
    $sql = "SELECT * FROM app_meta WHERE (app_id='$app_id') AND (ameta_key='$ameta_key')";
    $result_set = selectQuery( $sql );
    if( $result_set !== NULL ){
        if( mysqli_num_rows( $result_set ) > 0 ){
            $val = mysqli_fetch_object( $result_set );
            return $val->ameta_value;
        }
    }
    return NULL;
}


/**
 * Generates a new record in the DB table private_links
 * 
 * @param URL $real_link the complete real URL of the resource that is hidden behind the private link
 * @param bool $single_use Specify if the link being generated is a single use or multiple use
 * @return null
 */
function generatePrivateLink( $real_link, $name = "", $type = "page", $single_use = true, $mime_type = "auto" ){
    $timestamp = currentTimeMilliseconds();
    $code = base64_encode( hashPassword( $timestamp ) );
    //$single_use = true;
    $createdOn = currentTimeMilliseconds();
    
    $private_link = getSiteRootPath() . "/links.php?code=";
                
    $sql = "INSERT INTO private_links( `code`, `real_link`, `name`, `type`, `mime_type`, `single_use`, `created_on` ) "
            . "VALUES( '$code', '$real_link', '$name', '$type', '$mime_type', '$single_use', '$createdOn' )";
    $rows = insertQuery( $sql );
    if( $rows > 0 ){
        $private_link .= $code;
        return $private_link;
    }
    return NULL;
}

/**
 * The private links are of 2 types viz: single use and multiple-use
 * This function is used to set the is_used status of the private_links to true or false
 * 
 * @param string $code The unique code that identifies the private link
 * @return bool true on success, false otherwise
 */
function setPrivateLinkAsUsed( $code ){
    // Check if the code is valid
    $sql = "SELECT code FROM private_links WHERE code='$code'";
    //echo $sql . " = ";
    $result_set = selectQuery( $sql );
    if( ($result_set === NULL) || (mysqli_num_rows($result_set) == 0) ){
        return false;
    }
    $timestamp = currentTimeMilliseconds();
    $sql = "UPDATE private_links SET is_used='1', last_used_on='$timestamp' WHERE code='$code'";
    //echo $sql;
    $rows = updateQuery( $sql );
    if( $rows > 0 ){
        return true;
    }
    return false;
}

/**
 * To reduce session hijacking attacks, the session id needs to be regenerated under following circumstances
 * 1. At every successful login
 * 2. At every successful logout
 * 3. After every regular interval (set in scodezy-constans.php) file
 * 
 * @param bool $force If true, forces to regenerate the session id instantly. False is default, will regenerate session id only during expiry of the timestamp
 */
function sessionRevalidate( $force = FALSE ){
    @session_start();
    if( $force ){
        //echo "here";
        @session_regenerate_id();
        $_SESSION[ SESSION_ID_GENERATE_TIMESTAMP ] = currentTimeMilliseconds();
    }
    else{
        if( !isset( $_SESSION[ SESSION_ID_GENERATE_TIMESTAMP ] ) ){
            //echo "here1";
            $_SESSION[ SESSION_ID_GENERATE_TIMESTAMP ] = currentTimeMilliseconds();
        }
        else{
            //echo "here2";
            //print_r( $_SESSION );
            $session_timestamp = $_SESSION[ SESSION_ID_GENERATE_TIMESTAMP ];
            $currentMillis = currentTimeMilliseconds();
            $difference = $currentMillis - $session_timestamp;
            //echo "difference: $difference," . SESSION_ID_REGENERATE_INTERVAL_DEFAULT;
            if( $difference > SESSION_ID_REGENERATE_INTERVAL_DEFAULT ){
                @session_regenerate_id( );
                $_SESSION[ SESSION_ID_GENERATE_TIMESTAMP ] = currentTimeMilliseconds();
            }
        }
        
    }
}

function request( $var ){
    if( !isset( $_REQUEST[ $var ] ) ){
        return NULL;
    }
    if( is_array( $_REQUEST[ $var ] ) )
        return $_REQUEST[ $var ];
    else 
        return trim( $_REQUEST[ $var ] );
}

/**
 * Get all the information about the page from the table pages for the given page_name or page_id
 * 
 * @param string $page_name The page identified by the column page_name
 * @param string $page_id The page identified by the column page_id
 * @return mixed NULL if both the input parameters are NULL, or when page_name or page_id does not exist in the table pages. Database table row object otherwise
 */
function getPageInformation( $page_name = NULL, $page_id = NULL ){
    if( ($page_name == NULL) && ($page_id == NULL) ){
        return NULL;
    }
    
    if( $page_name == NULL ){
        $sql = "Select * FROM pages WHERE page_id='$page_id'";        
    }
    else{
        $sql = "Select * FROM pages WHERE page_name='$page_name'";
    }
    $result_set = selectQuery( $sql );
    if( $result_set == NULL ){
        return NULL;
    }
    if( mysqli_num_rows( $result_set ) == 0 ){
        return NULL;
    }
    return mysqli_fetch_object( $result_set );
    
}


function getBreadcrumbHierarchy( $page_name = NULL, $page_id = NULL ){
    if( ($page_name == NULL) && ($page_id == NULL) ){
        return NULL;
    }
    
    $page_hierarchy = array();
    
    if( $page_name == NULL ){
        $sql = "Select parent_id, page_name, page_title, hierarchy FROM pages WHERE page_id='$page_id'";        
    }
    else{
        $sql = "Select parent_id, page_name, page_title, hierarchy FROM pages WHERE page_name='$page_name'";
    }
    $result_set = selectQuery( $sql );
    if( $result_set == NULL ){
        return NULL;
    }
    if( mysqli_num_rows( $result_set ) == 0 ){
        return NULL;
    }
    
    $val = mysqli_fetch_assoc( $result_set );
    $page_hierarchy[] = $val;
    while( $val[ 'hierarchy' ] != "0" ){
        $sql = "Select parent_id, page_name, page_title, hierarchy FROM pages WHERE page_id='{$val[ 'parent_id' ]}'";
        $result_set = selectQuery( $sql );
        if( mysqli_num_rows( $result_set ) == 0 ){
            break;
        }
        $val = mysqli_fetch_assoc( $result_set );
        $page_hierarchy[] = $val;
    }
    
    return $page_hierarchy;
}

function createZipFromDirectory( $directoryToBeZippedPath, $zipFilePath ){
    // Remove any trailing slashes from the path
    //$rootPath = rtrim( $directoryToBeZippedPath, '\\/' );

    // Get real path for our folder
    $rootPath = realpath( $directoryToBeZippedPath );

    // Initialize archive object
    $zip = new ZipArchive();
    $zip->open( $zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    // Create recursive directory iterator
    /** @var SplFileInfo[] $files */
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($rootPath),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($files as $file)
    {
        // Skip directories (they would be added automatically)
        if (!$file->isDir())
        {
            // Get real and relative path for current file
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($rootPath) + 1);

            // Add current file to archive
            $zip->addFile($filePath, $relativePath);
        }
    }

    // Zip archive will be created only after closing object
    $zip->close();
}

function getSiteRootPath( $subPath = NULL ){
    //echo BASE_PATH;
    if( $subPath === NULL ){
        // Remove any trailing slashes from the path
        //$rootDirectory = rtrim( BASE_PATH, '\\/' );
        $str = WEBSITE_PROTOCOL . "://" . WEBSITE_DOMAIN_NAME;
    }
    else{
        $str = WEBSITE_PROTOCOL . "://" . WEBSITE_DOMAIN_NAME . FILE_SEPARATOR . $subPath;
    }
    
    return $str;
}

function createContent( $content, $contentAlias, $contentIcon, $contentTitle, $contentType, $contentMimeType, $contentStatus ){
    $currentTimestamp = currentTimeMilliseconds();
    $sql = "INSERT INTO contents( `content_name`, `content_created_on`, `content_modified_on`, `content`, `content_alias`, `content_icon`, `content_title`, `content_type`, `content_mime_type`, `content_status` ) "
            . " VALUES( '$currentTimestamp', '$currentTimestamp', '$currentTimestamp', '$content', '$contentAlias', '$contentIcon', '$contentTitle', '$contentType', '$contentMimeType', '$contentStatus'  )";
    $rows = insertQuery( $sql );
    if( $rows > 0 ){
        return true;
    }
    return false;
}

function get_valid_scodezy_filename( $fileName ){
    $normal_characters  = "a-zA-Z0-9_\-.";  // Only these characters are allowed for the fileName and the rest are removed from the fileName
    $normal_text        = preg_replace("/[^$normal_characters]/", '', $fileName );  
    
    return $normal_text;    
}

function checkAuthorizationForFunction( $functionality_name ){
    if ( !hasAuthorization( $functionality_name ) ) {
        echo createJSONMessage(GENERAL_ERROR_MESSAGE, $functionality_name, "You dont have sufficient privilege to perform this action !");
        exit();
    }
}

function checkAuthorizationForPage( $file_name ){
    if ( !hasAuthorization( $file_name ) ) {
        echo createJSONMessage(GENERAL_ERROR_MESSAGE, __FUNCTION__, "You dont have sufficient privilege to view this page !");
        exit();
    }
}

// Token based check
function hasAuthorization( $functionality_name ){
    // $jwt = $_COOKIE[ TOKEN_NAME ];
    global $globalAccessToken;
    $jwt = $globalAccessToken;
    $payload = getJWTPayload( $jwt );
    $role_id = $payload[ SESSION_AUTHORIZATION ];
    
    // Check if this role_id has access to the $functionality_name
    $sql = "SELECT rf.functionality_id, rf.role_id FROM functionalities f, roles_functionalities rf WHERE (f.functionality_name='$functionality_name') AND (f.functionality_id=rf.functionality_id) AND (rf.role_id='$role_id')";
    $result_set = selectQuery( $sql );
    if( mysqli_num_rows( $result_set ) == 0 ){
        return false;
    }
    return true;
}

// Session Based Check
function hasAuthorization1( $functionality_name ){
    @session_start();
    
    if( !isset( $_SESSION[ SESSION_AUTHORIZATION ] ) ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, $functionality_name, "Please sign out and sign in again to continue !" );
        return;
    }
    
    $role_id = $_SESSION[ SESSION_AUTHORIZATION ];
    
    // Check if this role_id has access to the $functionality_name
    $sql = "SELECT rf.functionality_id, rf.role_id FROM functionalities f, roles_functionalities rf WHERE (f.functionality_name='$functionality_name') AND (f.functionality_id=rf.functionality_id)";
    $result_set = selectQuery( $sql );
    if( mysqli_num_rows( $result_set ) == 0 ){
        return false;
    }
    return true;
}

function base64UrlEncode( $text ){
    
    $encoded_text = base64_encode( $text );
    $replaced_text = str_replace( ['+', '/'], ['-', '_'], $encoded_text );
    //$replaced_text = str_replace( '=', '_--_', $replaced_text );
    $replaced_text = str_replace( '=', '', $replaced_text );
    
    //$replaced_text = base64_encode( $text );
    return $replaced_text;
}

function base64UrlDecode( $text ){
    
    // $replaced_text = str_replace( '_--_', '=', $text );
    //$replaced_text = str_replace( '_--_', '=', $text );
    $replaced_text = str_replace( ['-', '_'], ['+', '/'], $text );
    $decoded_text = base64_decode( $replaced_text );
    
    //$decoded_text = base64_decode( $text );
    return $decoded_text;
}

function generateAsymmetricKeyPair(){
    require( dirname(__DIR__) . "/configurations/config.php" );
    global $openssl;
    
    //write your configurations :D
    $configargs = array(
        "config" => $openssl,
        "digest_alg" => "sha256",
        "private_key_bits" => 2048,
        "private_key_type" => OPENSSL_KEYTYPE_RSA,
    );
    
    // Create the private and public key
    $res = openssl_pkey_new($configargs);

    // Extract the private key from $res to $privKey
    openssl_pkey_export($res, $privKey,NULL,$configargs);

    // Extract the public key from $res to $pubKey
    $pubKey = openssl_pkey_get_details($res);
    
    // print_r( $pubKey );
    $pubKey = $pubKey["key"];
    
    $keys = array(
        "publicKey" => $pubKey,
        "privateKey" => $privKey
    );
    
    return $keys;
}

function encryptData( $publicKey, $data ){
    // Encrypt the data to $encrypted using the public key
    openssl_public_encrypt($data, $encrypted, $publicKey);
    
    $base64 = base64UrlEncode( $encrypted );
    
    return $base64;
}

function decryptData( $privateKey, $data ){
    $binary = base64UrlDecode( $data );

    // Decrypt the data using the private key and store the results in $decrypted
    openssl_private_decrypt( $binary, $decrypted, $privateKey );
    
    return $decrypted;
}

function createStandardJWT( $payload, $header = NULL ){
    global $secret;
    
    if( $header === NULL ){
        $header = [
            'typ' => 'JWT',
            'alg' => 'HS256'
        ];
    }
    
    $header = json_encode( $header );
    $payload = json_encode( $payload );
    
    // Encode Header
    $base64UrlHeader = base64UrlEncode($header);

    // Encode Payload
    $base64UrlPayload = base64UrlEncode($payload);

    // Create Signature Hash
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);
    
    // Encode Signature to Base64Url String
    $base64UrlSignature = base64UrlEncode($signature);

    // Create JWT
    $jwt = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;

    return $jwt;
}

function createStandardRefreshToken( $key, $payload, $header = NULL ){
    global $secret;
    
    if( $header === NULL ){
        $header = [
            'typ' => 'JWT',
            'alg' => 'HS256'
        ];
    }
    
    $header = json_encode( $header );
    $payload = json_encode( $payload );
    
    // Encode Header
    $base64UrlHeader = base64UrlEncode( $header );

    // Encode Payload
    $base64UrlPayload = base64UrlEncode( $payload );

    // Create Signature Hash
    $signature = hash_hmac( 'sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true );

    // Encode Signature to Base64Url String
    $base64UrlSignature = base64UrlEncode($signature);

    // Create JWT
    $jwt = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    
    // Encrypt JWT
    $jwt = encryptData( $key, $jwt );

    return $jwt;
}

function isValidJWT( $jwt ){
    global $secret;
    
    // split the token
    $tokenParts = explode( '.', $jwt );
    
    if( count( $tokenParts ) !== 3 ){
        return false;
    }
    
    $header = ($tokenParts[0]);
    $payload = ($tokenParts[1]);
    $signatureProvided = $tokenParts[2];
    
    //print_r( $header );
    //print_r( $payload );
    
    // check the expiration time - note this will cause an error if there is no 'exp' claim in the token
    
    // build a signature based on the header and payload using the secret
    //$base64UrlHeader = base64UrlEncode($header);
    //$base64UrlPayload = base64UrlEncode($payload);
    $signature = hash_hmac('sha256', $header . "." . $payload, $secret, true);
    $base64UrlSignature = base64UrlEncode($signature);
        
    //echo $base64UrlSignature . "," .$signatureProvided;

    // verify it matches the signature provided in the token
    $signatureValid = ($base64UrlSignature === $signatureProvided);
    
    if ($signatureValid) {
        return true;
    } 
    else {
        return false;
    }
}

function isAccessTokenExpired( $jwt ){
    global $secret;
    
    // split the token
    $tokenParts = explode( '.', $jwt );
    
    if( count( $tokenParts ) !== 3 ){
        return false;
    }
    
    $header = base64UrlDecode($tokenParts[0]);
    $payload = base64UrlDecode($tokenParts[1]);
    $signatureProvided = $tokenParts[2];
    
    //echo $header;
    //echo $payload;
    
    $data = json_decode( $payload, true );
    
    $currentTime = currentTimeMilliseconds();
    $initializationTime = $data[ 'iat' ];
    $expiryDuration = $data[ 'exp' ];
    
    $difference = $currentTime - $initializationTime;
    //echo $difference;
    
    // Token Expired condition
    if( $difference > $expiryDuration ){
        return true;
    }
    return false;
}

function setAccessTokenAsExpiredInDB( $token ){
    $e_token = escape_string( $token );
    $sql = "SELECT token_id, access_token FROM refresh_tokens WHERE access_token='$e_token'";
    $result_set = selectQuery( $sql );
    if( mysqli_num_rows( $result_set ) > 0 ){
        $val            = mysqli_fetch_object( $result_set );
        $access_token   = $val->access_token;
        $token_id       = $val->token_id;
        if( $token === $access_token ){
            $sql = "UPDATE refresh_tokens SET is_expired='1' WHERE token_id='$token_id'";
            updateQuery( $sql );
        }
    }
}

function setRefreshTokenAsExpiredInDB( $token ){
    $e_token = escape_string( $token );
    $sql = "SELECT token_id, token FROM refresh_tokens WHERE token='$e_token'";
    $result_set = selectQuery( $sql );
    if( mysqli_num_rows( $result_set ) > 0 ){
        $val = mysqli_fetch_object( $result_set );
        $refresh_token  = $val->token;
        $token_id       = $val->token_id;
        if( $token === $refresh_token ){
            $sql = "UPDATE refresh_tokens SET is_expired='1' WHERE token_id='$token_id'";
            updateQuery( $sql );
        }
    }
}

function storeTokenInDB( $refreshToken, $expiry, $accessToken, $isExpired ){
    $sql = "INSERT INTO refresh_tokens( `token`, `expiry`, `access_token`, `is_expired` ) "
            . "VALUES( '$refreshToken', '$expiry', '$accessToken', '$isExpired' )";
    $rows = insertQuery( $sql );
    if( $rows > 0 ){
        return getAIID();
    }
    return false;
}

function storeUserIDTokenIDPair( $user_id, $token_id ){
    $sql = "INSERT INTO user_tokens( `user_id`, `token_id` ) "
            . "VALUES( '$user_id', '$token_id' )";
    $rows = insertQuery( $sql );
}

function storeAppIDTokenIDPair( $app_id, $token_id ){
    $sql = "INSERT INTO app_tokens( `app_id`, `token_id` ) "
            . "VALUES( '$app_id', '$token_id' )";
    $rows = insertQuery( $sql );
}

function getJWTPayload( $jwt ){
    // split the token
    $tokenParts = explode( '.', $jwt );
    
    if( count( $tokenParts ) !== 3 ){
        return false;
    }
    
    $payload = base64UrlDecode( $tokenParts[ 1 ] );
    
    return json_decode( $payload, true );
}

function crypto_rand_secure( $min, $max ){
    // Source: https://stackoverflow.com/questions/1846202/how-to-generate-a-random-unique-alphanumeric-string
    $range = $max - $min;
    if ($range < 1) return $min; // not so random...
    $log = ceil(log($range, 2));
    $bytes = (int) ($log / 8) + 1; // length in bytes
    $bits = (int) $log + 1; // length in bits
    $filter = (int) (1 << $bits) - 1; // set all lower bits to 1
    do {
        $rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
        $rnd = $rnd & $filter; // discard irrelevant bits
    } while ($rnd > $range);
    return $min + $rnd;
}

function getRandomID( $length ){
    // Source: https://stackoverflow.com/questions/1846202/how-to-generate-a-random-unique-alphanumeric-string
    $token = "";
    $codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $codeAlphabet.= "abcdefghijklmnopqrstuvwxyz";
    $codeAlphabet.= "0123456789";
    $codeAlphabet.= "_";
    $max = strlen($codeAlphabet); // edited

    for ($i=0; $i < $length; $i++) {
        $token .= $codeAlphabet[crypto_rand_secure(0, $max-1)];
    }

    return $token;
}

function generateRandomNumber( $length ){ 
    // Source: https://stackoverflow.com/questions/1846202/how-to-generate-a-random-unique-alphanumeric-string
    $token = "";
    $codeAlphabet = "0123456789";
    $max = strlen( $codeAlphabet ); 
    
    for ($i=0; $i < $length; $i++) {
        $token .= $codeAlphabet[crypto_rand_secure(0, $max-1)];
    }

    return $token;
}

function generateUniqueID( $suffix = "" ){
    $id = getRandomID( 10 );
    $id .= "_$suffix";
    return $id;
}

function generateUniqueAppSecret(){
    $id = getRandomID( 32 );
    return $id;
}

function send_json_mime_type_header(){
    header( 'Content-Type: application/json' );
}

function display_php_errors(){
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}


/**
 * Check if the specified $var exists in the $array if $array is specified
 * Checks if the specified $var is not null if the $array is not specified
 * 
 * @param type $var The key for the $array to check if it exists and is not null
 * @param type $array The array to check for the key
 * @return bool 
 */
function data_exists( $var, $array = NULL ){
    if( $array !== NULL ){
        if( is_array( $array ) ){
            //if( isset( $array[ $var ] ) && ( $array[ $var ] !== NULL ) && ( strtolower( $array[ $var ] ) !== "null" ) ){
            if( isset( $array[ $var ] ) && ( $array[ $var ] !== NULL ) ){
                return true;
            }
            // If the element inside the array is null
            return false;
        }
        else{
            // If second parameter specified is not an array
            return false;
        }        
    }
    else{
        // If no array is specified, if the $var is null or "null"
        if( ($var === NULL) || ( strtolower( $var ) === "null" ) ){
            return false;
        }
        else{
            return true;
        }
    }
}

function createRandomUserID(){
    $prefixes = array( "superhuman", "applepie", "sweetmelon", "lagoona", "spacecraft", "croissant", "metahuman", "timetraveler" );
    $prefix = $prefixes[ random_int( 0, count( $prefixes ) - 1 ) ];
    $suffix = random_int( 0, 999999 );
    return $prefix . $suffix;
}

function getWebserviceUrl(){
    $siteConfig = getSiteConfig();
    $url = $siteConfig->protocol . "://" . $siteConfig->domain_name . "/webservice.php";
    return $url;
}

function getAccessTokenUsingAppID( $app_id ){
    // Check if the app_id is valid
    $sql = "SELECT app_id FROM apps WHERE app_id='$app_id'";
    $result_set = selectQuery( $sql );
    if( mysqli_num_rows( $result_set ) === 0 ){
        return NULL;
    }
    
    $sql = "SELECT rt.access_token, rt.is_expired FROM refresh_tokens rt, app_tokens at WHERE (at.app_id='$app_id') AND (rt.token_id=at.token_id)";
    $result_set = selectQuery( $sql );
    if( mysqli_num_rows( $result_set ) === 0 ){
        return NULL;
    }
    $val = mysqli_fetch_object( $result_set );
    
    if( $val->is_blocked === "1" ){
        return NULL;
    }
    
    return $val->access_token;
}

function create_iso_8601_datetime(){
    return $iso_8601_datetime = date( 'c' );
}

function convert_iso_8601_datetime_to_millis( $iso_8601_datetime ){
    /* Your date string */
    //$dateString = $iso_8601_datetime;

    /* Create a DateTime object */
    $dateTime = new DateTime( $iso_8601_datetime );

    /* Get the timestamp in seconds */
    $timestampInSeconds = $dateTime->getTimestamp();

    /* Convert seconds to milliseconds */
    $timestampInMilliseconds = $timestampInSeconds * 1000;

    return $timestampInMilliseconds;
}

/**
 * Creates largest number for the number of digits given as input parameter
 * 
 * @param type $x The number of digits
 * @return type Largest integer containg $x number of digits
 */
function getLargestXDigitNumber( $x ){
    $max = '';
    for( $i = 1; $i <= $x ; $i++ ){
        $max .= '9';
    }
    return intval( $max );
}

/**
 * Appends the givenNo with zeros so that the total number of characters in resulting number is equal to $totalLength
 * 
 * @param type $givenNo
 * @param type $totalLength
 * @return type
 */
function appendZeros( $givenNo, $totalLength ){
    $length_of_given_no = strlen( '' . $givenNo );
    if( $length_of_given_no > $totalLength )
        return $givenNo;
    
    $result = '';
    for( $i = $length_of_given_no ; $i < $totalLength ; $i++ ){
        $result .= '0';
    }
    $result .= $givenNo;
    
    return $result;
}
?>