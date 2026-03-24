<?php

//include PLU_PATH . FILE_SEPARATOR . "appstv_crm_apps" . FILE_SEPARATOR . "includes" . FILE_SEPARATOR . "shopify-keys.php";
//include PLU_PATH . FILE_SEPARATOR . "appstv_crm_apps" . FILE_SEPARATOR . "includes" . FILE_SEPARATOR . "shopify-constants.php";

require LIB_PATH . FILE_SEPARATOR . 'ShopifySDK' . FILE_SEPARATOR . "vendor/autoload.php";

/**
 * 
 * The shopify App URL needs to be : https://myappstv.com/webservice.php?what_do_you_want=install_shopify_app&app_id=lmuMl4DA7T_a
 * at the time of installation of the app.
 * This URL cannot contain access_token because the limit for URL characters is only 255. Including the access_token in this URL will cause it to be more than 255 and wont allow us to 
 * save the URL.
 * 
 * So, we have kept app_id in this URL, and using app_id we shall retrieve the access_token and then append it to the redirect_uri
 */
function install_shopify_app(){
    global $store_url, $client_id, $globalAccessToken;
    
    //display_php_errors();
    
    // The shopify is not accepting URLs longer than 255 characters, so we will pass the app_id to shopify url and then fetch the access_token from database
    $app_id = request( 'app_id' );
    
    $e_app_id = escape_string( $app_id );
    
    // Check if the app_id is valid
    $sql = "SELECT app_id FROM apps WHERE app_id='$e_app_id'";
    $result_set = selectQuery( $sql );
    if( mysqli_num_rows( $result_set ) === 0 ){
        send_json_mime_type_header();
        echo createJSONMessage(GENERAL_ERROR_MESSAGE, __FUNCTION__, "App is invalid" );
        return;
    }
    
    $sql = "SELECT rt.access_token, rt.is_expired FROM refresh_tokens rt, app_tokens at WHERE (at.app_id='$e_app_id') AND (rt.token_id=at.token_id)";
    $result_set = selectQuery( $sql );
    if( mysqli_num_rows( $result_set ) === 0 ){
        send_json_mime_type_header();
        echo createJSONMessage(GENERAL_ERROR_MESSAGE, __FUNCTION__, "App is invalid" );
        return;
    }
    $val = mysqli_fetch_object( $result_set );
    $access_token   = $val->access_token;
    $is_expired     = $val->is_expired;
    
    if( $is_expired === 1 ){
        send_json_mime_type_header();
        echo createJSONMessage(GENERAL_ERROR_MESSAGE, __FUNCTION__, "Please regenerate the token" );
        return;
    }
    
    
    $scopes = "read_orders,write_orders,read_products,read_customers,write_inventory,read_locations,read_assigned_fulfillment_orders,write_assigned_fulfillment_orders,read_fulfillments,write_fulfillments,read_merchant_managed_fulfillment_orders,write_merchant_managed_fulfillment_orders,read_third_party_fulfillment_orders,write_third_party_fulfillment_orders";
    //$scopes = "read_orders,read_products";
    /*
    $scopes = "read_orders,read_all_orders,read_assigned_fulfillment_orders,write_assigned_fulfillment_orders,read_cart_transforms,write_cart_transforms,read_content,write_content,"
            . "read_online_store_pages,read_customers,write_customers,read_discounts,write_discounts,read_own_subscription_contracts,write_own_subscription_contracts,"
            . "customer_read_customers,customer_write_customers,customer_read_orders,customer_read_own_subscription_contracts,customer_write_own_subscription_contracts,"
            . "read_products,write_products";
    * 
    */

    
    $redirect_uri = "https://myappstv.com/webservice.php?what_do_you_want=generate_shopify_app_token%26" . TOKEN_NAME . "=$access_token";
    // Please Read: The %26 in above URL is to only url-encode the & before the token_name, so that the shopify will consider this parameter to be a part of redirect_uri
    // rather than the part of the actual URL $install_url below
    
    
    // Build install/approval URL to redirect to
    $install_url = "https://" . $store_url . "/admin/oauth/authorize?client_id=" . $client_id . "&scope=" . $scopes . "&redirect_uri=" . $redirect_uri;

    // Redirect
    header("Location: " . $install_url);
    die();
}

/**
 * 
 * This function call needs to be in the Allowed redirection URL(s) list of the Shopify App configuration settings page
 * This is because, the install_shopify_app function above will ultimately redirect to the generate_shopify_app_token() function
 * The shopify will show an error if this generate_shopify_app_token url is not whitelisted
 * 
 */
function generate_shopify_app_token(){
    global $store_name, $store_url, $client_id, $client_secret, $webhook_return_address;
    /*
    display_php_errors();
    
    // The shopify is not accepting URLs longer than 255 characters, so we will pass the app_id to shopify url and then fetch the access_token from database
    $app_id = request( 'app_id' );
    
    $e_app_id = escape_string( $app_id );
    
    // Check if the app_id is valid
    $sql = "SELECT app_id FROM apps WHERE app_id='$e_app_id'";
    $result_set = selectQuery( $sql );
    if( mysqli_num_rows( $result_set ) === 0 ){
        send_json_mime_type_header();
        echo createJSONMessage(GENERAL_ERROR_MESSAGE, __FUNCTION__, "App is invalid" );
        return;
    }
    
    $sql = "SELECT rt.access_token, rt.is_expired FROM refresh_tokens rt, app_tokens at WHERE (at.app_id='$e_app_id') AND (rt.token_id=at.token_id)";
    $result_set = selectQuery( $sql );
    if( mysqli_num_rows( $result_set ) === 0 ){
        send_json_mime_type_header();
        echo createJSONMessage(GENERAL_ERROR_MESSAGE, __FUNCTION__, "App is invalid" );
        return;
    }
    $val = mysqli_fetch_object( $result_set );
    $at             = $val->access_token;
    $is_expired     = $val->is_expired;
    
    if( $is_expired === 1 ){
        send_json_mime_type_header();
        echo createJSONMessage(GENERAL_ERROR_MESSAGE, __FUNCTION__, "Please regenerate the token" );
        return;
    }
    */
    $params = $_GET; // Retrieve all request parameters
    $hmac   = $_GET[ 'hmac' ]; // Retrieve HMAC request parameter

    $params = array_diff_key( $params, array('hmac' => '')); // Remove hmac from params
    ksort( $params ); // Sort params lexographically

    $computed_hmac = hash_hmac('sha256', http_build_query($params), $client_secret );

    // Use hmac data to check that the response is from Shopify or not
    if ( hash_equals($hmac, $computed_hmac ) ) {

        // Set variables for our request
        $query = array(
            "client_id" => $client_id, // Your API key
            "client_secret" => $client_secret, // Your app credentials (secret key)
            "code" => $params[ 'code' ] // Grab the access key from the URL
        );

        // Generate access token URL
        $access_token_url = "https://" . $params[ 'shop' ] . "/admin/oauth/access_token";

        // Configure curl client and execute request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $access_token_url);
        curl_setopt($ch, CURLOPT_POST, count($query));
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($query));
        $result = curl_exec($ch);
        curl_close($ch);

        // Store the access token
        $result = json_decode($result, true);
        $access_token = $result['access_token'];

        // Show the access token (don't do this in production!)
        // echo $access_token;
        setConfigurationValue( "_shopify_store_{$store_name}_access_token", $access_token );
        
        send_json_mime_type_header();
        echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, "Access token has been successfully generated and stored in the system !" );
    }
    else{
        // Someone is trying to be shady!
        //die(  );
        send_json_mime_type_header();
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, 'This request is NOT from Shopify!' );
    }
}

/**
 * Perform a cURL POST request to Shopify REST API
 * 
 * @global type $store_name
 * @param type $params Array containing 'headers', 'api_url' and 'url_params' as the keys to be used as parameters in the function
 * @return mixed The script exists after echoing JSONMessage during errors. Returns output from Shopify REST API when the cURL Request is successful
 */
function shopifyPerformCurlRequest( $params ){
    global $store_name;
    
    if( !isset( $params[ 'headers' ] )){
        
        // Retrieve the shopify_access_token from the DB
        $shopify_access_token = getConfigurationValue( "_shopify_store_{$store_name}_access_token" );
        if( $shopify_access_token === NULL ){
            echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Please generate the shopify access token using the shopify install url from the shopif app development settings" );
            exit();
        }
        
        $params[ 'headers' ] = array(
            "Accept: application/json",
            "Content-Type: application/json",
            "Connection: keep-alive",
            "X-Shopify-Access-Token: $shopify_access_token"
        );
    }
    
    if( !isset( $params[ 'api_url' ] ) ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Shoppify API URL is missing" );
        exit();
    }
    
    $ch = curl_init();
    curl_setopt( $ch, CURLOPT_HTTPHEADER, $params[ 'headers' ] );
    curl_setopt( $ch, CURLOPT_URL, $params[ 'api_url' ] );
    
    if( isset( $params[ 'method' ] ) ){
        curl_setopt( $ch, CURLOPT_POST, true );
    }
    
    if( isset( $params[ 'custom_request' ] ) ){
        curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, $params[ 'custom_request' ] );
    }
    
    if( isset( $params[ 'url_params' ] ) ){
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $params[ 'url_params' ] );
    }
    //curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true);
    
    $output = curl_exec( $ch );
    
    curl_close( $ch );
    
    /*
    $output_arr = json_decode( $output, true );
    if( $output_arr === NULL ){
        //echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Failed to receive content from the Shopify REST API " );
        //exit();
        return ""; 
    }*/
    
    return $output;
}

?>