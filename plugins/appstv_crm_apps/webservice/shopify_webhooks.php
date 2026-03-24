<?php

/**
 * 
 * WebHook Topics: https://shopify.dev/docs/api/admin-rest/2024-10/resources/webhook#event-topics
 * 
 * This function creates shopify webhook that the APPSTV CRM needs, when the topic is provided
 * 
 */
function create_shopify_webhook(){
    global $store_name, $store_url, $client_id, $client_secret, $api_version, $webhook_return_address;
    
    checkAuthorizationForFunction( __FUNCTION__ );
    
    display_php_errors();
    
    $app_id = request( 'app_id' );
    $topic  = request( 'topic' );
    
    validateEmptyString( $app_id, __FUNCTION__, "App ID is required" );
    validateEmptyString( $topic, __FUNCTION__, "Webhook topic is required" );
    
    $e_app_id   = escape_string( $app_id );
    $e_topic    = escape_string( $topic );
    
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
    $is_expired     = $val->is_expired;
    
    if( $is_expired === 1 ){
        send_json_mime_type_header();
        echo createJSONMessage(GENERAL_ERROR_MESSAGE, __FUNCTION__, "Please regenerate the token" );
        return;
    }
    
    
    
    // Retrieve the shopify_access_token from the DB
    $shopify_access_token = getConfigurationValue( "_shopify_store_{$store_name}_access_token" );
    if( $shopify_access_token === NULL ){
        send_json_mime_type_header();
        echo createJSONMessage(GENERAL_ERROR_MESSAGE, __FUNCTION__, "Please generate the shopify access token using the shopify install url from the shopif app development settings" );
        return;
    }
    
    $currentMillis = currentTimeMilliseconds();
    $webhook_registration_url    = "https://$store_url/admin/api/$api_version/webhooks.json";
    //$return_address              = "https://myappstv.com/webservice.php?what_do_you_want=shopify_webhook_response&app_id=$e_app_id";
    // The reason to not include access_token for return_address of the webhooks is because the access_token expires in 3 months. This will cause us to update webhooks before 
    // the expiry of access_token
    //$return_address              = "https://myappstv.com/webservice.php?what_do_you_want=shopify_webhook_response";
    //$return_address              = "https://myappstv.com/webservice.php?what_do_you_want=shopify_webhook_response&app_id=$e_app_id";
    $webhook_return_address .= "&app_id=$e_app_id";
    
    //WebHook Topics: https://shopify.dev/docs/api/admin-rest/2024-10/resources/webhook#event-topics
    //$topic = "orders/create";
    
    
    $data = array(
        "webhook" => array(
            "topic" => $topic,
            "address" => $webhook_return_address,
            "format" => "json"
        )
    );
    
    /*
    $data = array(
        "webhook" => array(
            "topic" => "customers/create",
            "address" => $return_address,
            "format" => "json"
        )
    );
     * 
     */
    $data_json = json_encode( $data );
    //echo $data_json;
    
    $auth_header = array(
        "Accept: application/json",
        "Content-Type: application/json",
        "Connection: keep-alive",
        "X-Shopify-Access-Token: $shopify_access_token"
    );
    
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_HTTPHEADER, $auth_header );
    curl_setopt($ch, CURLOPT_URL, $webhook_registration_url );
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json );    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    //curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $output = curl_exec($ch);
    curl_close($ch);

    $output_json = json_decode( $output, true );
    if( $output_json === NULL ){
        $sql = "INSERT INTO shopify_webhooks( `shopify_topic`, `shopify_store_url`, `address`, `created_at`, `api_version`, `successfully_created`, `created_response` ) "
                . "VALUES( '$e_topic', '$store_url', '$webhook_return_address', '$currentMillis', '$api_version', '0', '$output' )";
        insertQuery( $sql );
        
        send_json_mime_type_header();
        echo createJSONMessage(GENERAL_ERROR_MESSAGE, __FUNCTION__, "Invalid response received. The response has been stored in the DB" );
        return;
    }
    
    // Check if error has been received
    if( isset( $output_json[ 'errors' ] ) ){
        $sql = "INSERT INTO shopify_webhooks( `shopify_topic`, `shopify_store_url`, `address`, `created_at`, `api_version`, `successfully_created`, `created_response` ) "
                . "VALUES( '$e_topic', '$store_url', '$webhook_return_address', '$currentMillis', '$api_version', '0', '$output' )";
        insertQuery( $sql );
        send_json_mime_type_header();
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, $output_json );
        return;
    }
    
    // Write a code to store the webhook topic details in the new detabase table
    if( isset( $output_json[ 'webhook' ] ) ){
        $op                 = $output_json[ 'webhook' ];
        $shopify_webhook_id = '';
        $address            = '';
        $topic              = '';
        $created_at         = '';
        $updated_at         = '';
        $api_v              = '';
        if( isset( $op[ 'id' ] ) ){ $shopify_webhook_id = $op[ 'id' ]; }
        if( isset( $op[ 'address' ] ) ){ $address = $op[ 'address' ]; }
        if( isset( $op[ 'topic' ] ) ){ $topic = $op[ 'topic' ]; }
        if( isset( $op[ 'created_at' ] ) ){ $created_at = $op[ 'created_at' ]; }
        if( isset( $op[ 'updated_at' ] ) ){ $updated_at = $op[ 'updated_at' ]; }
        if( isset( $op[ 'api_version' ] ) ){ $api_v = $op[ 'api_version' ]; }
        
        $sql = "INSERT INTO shopify_webhooks( `shopify_webhook_id`, `shopify_topic`, `shopify_store_url`, `address`, `created_at`, `updated_at`, `api_version`, `created_response` ) "
                . "VALUES( '$shopify_webhook_id', '$e_topic', '$store_url', '$webhook_return_address', '$created_at', '$updated_at', '$api_v', '$output' )";
        insertQuery( $sql );
    }
    
    
    send_json_mime_type_header();
    echo $output;
}

/**
 * Updates a webhook for the given topic_id
 */
function update_shopify_webhook(){
    global $store_name, $store_url, $client_id, $client_secret, $api_version, $webhook_return_address;
    
    checkAuthorizationForFunction( __FUNCTION__ );
    
    display_php_errors();
    
    $app_id      = request( 'app_id' );
    $topic       = request( 'topic' );
    $webhook_id  = request( 'webhook_id' );
    
    validateEmptyString( $app_id, __FUNCTION__, "App ID is required" );
    validateEmptyString( $topic, __FUNCTION__, "Webhook topic is required" );
    validateEmptyString( $webhook_id, __FUNCTION__, "Webhook ID is required" );
    
    $e_app_id        = escape_string( $app_id );
    $e_topic         = escape_string( $topic );
    $e_webhook_id    = escape_string( $webhook_id );
    
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
    $is_expired     = $val->is_expired;
    
    if( $is_expired === 1 ){
        send_json_mime_type_header();
        echo createJSONMessage(GENERAL_ERROR_MESSAGE, __FUNCTION__, "Please regenerate the token" );
        return;
    }
    
    // Webhook ID must exist in the DB
    $sql = "SELECT * FROM shopify_webhooks WHERE shopify_webhook_id='$e_webhook_id'";
    $result_set = selectQuery( $sql );
    if( mysqli_num_rows( $result_set ) === 0 ){
        send_json_mime_type_header();
        echo createJSONMessage(GENERAL_ERROR_MESSAGE, __FUNCTION__, "Shopify Webhook ID does not exist in the system" );
        return;
    }
    
    // Retrieve the shopify_access_token from the DB
    $shopify_access_token = getConfigurationValue( "_shopify_store_{$store_name}_access_token" );
    if( $shopify_access_token === NULL ){
        send_json_mime_type_header();
        echo createJSONMessage(GENERAL_ERROR_MESSAGE, __FUNCTION__, "Please generate the shopify access token using the shopify install url from the shopif app development settings" );
        return;
    }
    
    $currentMillis = currentTimeMilliseconds();
    $webhook_update_url = "https://$store_url/admin/api/$api_version/webhooks/$e_webhook_id.json";
    //echo $webhook_update_url;
    
    
    $webhook_return_address .= "&app_id=$e_app_id";
    
    $data = array(
        "webhook" => array(
            "id" => intval( $webhook_id ),
            "address" => $webhook_return_address
        )
    );
    $data_json = json_encode( $data );
    //echo $data_json;
    
    $auth_header = array(
        //"Accept: application/json",
        "Content-Type: application/json",
        //"Connection: keep-alive",
        "X-Shopify-Access-Token: $shopify_access_token"
    );
    
    //print_r( $auth_header );
    
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_HTTPHEADER, $auth_header );
    curl_setopt($ch, CURLOPT_URL, $webhook_update_url );
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json );
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    //curl_setopt($ch, CURLOPT_HEADER, 1);
    //curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $output = curl_exec($ch);
    // Then, after your curl_exec call:
    //$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    //$header = substr($output, 0, $header_size);
    //$body = substr($output, $header_size);
    //echo $header;
    //echo $body;
    curl_close($ch);

    $output_json = json_decode( $output, true );
    if( $output_json === NULL ){
        $sql = "INSERT INTO shopify_webhooks( `shopify_topic`, `shopify_store_url`, `address`, `created_at`, `api_version`, `successfully_created`, `created_response` ) "
                . "VALUES( '$e_topic', '$store_url', '$webhook_return_address', '$currentMillis', '$api_version', '0', '$output' )";
        insertQuery( $sql );
        
        send_json_mime_type_header();
        echo createJSONMessage(GENERAL_ERROR_MESSAGE, __FUNCTION__, "Invalid response received. The response has been stored in the DB" );
        return;
    }
    
    // Check if error has been received
    if( isset( $output_json[ 'errors' ] ) ){
        $sql = "INSERT INTO shopify_webhooks( `shopify_topic`, `shopify_store_url`, `address`, `created_at`, `api_version`, `successfully_created`, `created_response` ) "
                . "VALUES( '$e_topic', '$store_url', '$webhook_return_address', '$currentMillis', '$api_version', '0', '$output' )";
        insertQuery( $sql );
        send_json_mime_type_header();
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, $output_json );
        return;
    }
    
    if( isset( $output_json[ 'webhook' ] ) ){
        $op                 = $output_json[ 'webhook' ];
        $shopify_webhook_id = '';
        $address            = '';
        $topic              = '';
        $created_at         = '';
        $updated_at         = '';
        $api_v              = '';
        if( isset( $op[ 'id' ] ) ){ $shopify_webhook_id = $op[ 'id' ]; }
        if( isset( $op[ 'address' ] ) ){ $address = $op[ 'address' ]; }
        if( isset( $op[ 'topic' ] ) ){ $topic = $op[ 'topic' ]; }
        if( isset( $op[ 'created_at' ] ) ){ $created_at = $op[ 'created_at' ]; }
        if( isset( $op[ 'updated_at' ] ) ){ $updated_at = $op[ 'updated_at' ]; }
        if( isset( $op[ 'api_version' ] ) ){ $api_v = $op[ 'api_version' ]; }
        
        $sql = "UPDATE shopify_webhooks SET "
                . "`shopify_webhook_id` = '$shopify_webhook_id', "
                . "`shopify_topic` = '$e_topic', "
                . "`shopify_store_url` = '$store_url', "
                . "`address` = '$webhook_return_address', "
                . "`created_at` = '$created_at', "
                . "`updated_at` = '$updated_at', "
                . "`api_version` = '$api_v', "
                . "`created_response` = '$output' "
                . "WHERE shopify_webhook_id='$webhook_id'";
        insertQuery( $sql );
    }
    
    // Write a code to store the webhook topic details in the new detabase table
    send_json_mime_type_header();
    echo $output;
}

// Shopify delete successfuly webhook returns an empty object response: {}

function delete_shopify_webhook(){
    global $store_name, $store_url, $client_id, $client_secret, $api_version, $webhook_return_address;
    
    checkAuthorizationForFunction( __FUNCTION__ );
    
    display_php_errors();
    
    $webhook_id  = request( 'webhook_id' );
    
    validateEmptyString( $webhook_id, __FUNCTION__, "Webhook ID is required" );
    
    $e_webhook_id    = escape_string( $webhook_id );
    
    // Webhook ID must exist in the DB
    $sql = "SELECT * FROM shopify_webhooks WHERE shopify_webhook_id='$e_webhook_id'";
    $result_set = selectQuery( $sql );
    if( mysqli_num_rows( $result_set ) === 0 ){
        send_json_mime_type_header();
        echo createJSONMessage(GENERAL_ERROR_MESSAGE, __FUNCTION__, "Shopify Webhook ID does not exist in the system" );
        return;
    }
    
    // Retrieve the shopify_access_token from the DB
    $shopify_access_token = getConfigurationValue( "_shopify_store_{$store_name}_access_token" );
    if( $shopify_access_token === NULL ){
        send_json_mime_type_header();
        echo createJSONMessage(GENERAL_ERROR_MESSAGE, __FUNCTION__, "Please generate the shopify access token using the shopify install url from the shopif app development settings" );
        return;
    }
    
    $currentMillis = currentTimeMilliseconds();
    $webhook_delete_url = "https://$store_url/admin/api/$api_version/webhooks/$e_webhook_id.json";
    //echo $webhook_update_url;
    
    $data = array(
        "webhook" => array(
            "id" => intval( $webhook_id )
        )
    );
    $data_json = json_encode( $data );
    //echo $data_json;
    
    $auth_header = array(
        //"Accept: application/json",
        "Content-Type: application/json",
        //"Connection: keep-alive",
        "X-Shopify-Access-Token: $shopify_access_token"
    );
    
    //print_r( $auth_header );
    
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_HTTPHEADER, $auth_header );
    curl_setopt($ch, CURLOPT_URL, $webhook_delete_url );
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json );
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    //curl_setopt($ch, CURLOPT_HEADER, 1);
    //curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $output = curl_exec($ch);
    // Then, after your curl_exec call:
    //$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    //$header = substr($output, 0, $header_size);
    //$body = substr($output, $header_size);
    //echo $header;
    //echo $body;
    curl_close($ch);

    $output_json = json_decode( $output, true );
    if( $output_json === NULL ){
        $sql = "INSERT INTO shopify_webhooks( `shopify_topic`, `shopify_store_url`, `address`, `created_at`, `api_version`, `successfully_created`, `created_response` ) "
                . "VALUES( 'Deleting Webhook', '$store_url', '$webhook_return_address', '$currentMillis', '$api_version', '0', '$output' )";
        insertQuery( $sql );
        
        send_json_mime_type_header();
        echo createJSONMessage(GENERAL_ERROR_MESSAGE, __FUNCTION__, "Invalid response received. The response has been stored in the DB" );
        return;
    }
    
    // Check if error has been received
    if( isset( $output_json[ 'errors' ] ) ){
        $sql = "INSERT INTO shopify_webhooks( `shopify_topic`, `shopify_store_url`, `address`, `created_at`, `api_version`, `successfully_created`, `created_response` ) "
                . "VALUES( 'Deleting Webhook', '$store_url', '$webhook_return_address', '$currentMillis', '$api_version', '0', '$output' )";
        insertQuery( $sql );
        send_json_mime_type_header();
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, $output_json );
        return;
    }
    
    if(count( $output_json ) === 0 ){
        $sql = "DELETE FROM shopify_webhooks WHERE shopify_webhook_id='$webhook_id'";
        deleteQuery( $sql );
    }
    
    // Write a code to store the webhook topic details in the new detabase table
    send_json_mime_type_header();
    echo $output;
}



/**
 * This function is called by the Shopify Webhooks on events which are registered with Shopify Webhooks
 */
function shopify_webhook_response(){
    global $store_name, $store_url, $client_id, $client_secret, $api_version;
    
    display_php_errors();
    
    $app_id      = request( 'app_id' );
    
    validateEmptyString( $app_id, __FUNCTION__, "App ID is required" );
    
    $e_app_id        = escape_string( $app_id );
    
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
    
    
    //$file = fopen( "test.txt", "a+" );
    
    $hmac_header        = $_SERVER[ 'HTTP_X_SHOPIFY_HMAC_SHA256' ];
    //$data               = file_get_contents( 'php://input' );
    $data = '';
    $fh   = @fopen('php://input', 'r');
    if ( $fh ){
        while ( !feof( $fh ) ){
            $s = fread( $fh, 1024 );
            if ( is_string( $s ) ){
              $data .= $s;
            }
        }
        fclose( $fh );
    }
    $calculated_hmac    = base64_encode( hash_hmac( 'sha256', $data, $client_secret, true ) );
    
    $e_data = escape_string( $data );
    
    //fwrite( $file, "HMAC Header: " . $hmac_header . "\n" );
    //fwrite( $file, "Calculated HMAC: " . $calculated_hmac . "\n" );
    //fwrite( $file, "Data: " . $data . "\n" );
    
    
    // How to receive a webhook response: https://medium.com/@SonuTechWeb/how-to-receive-webhook-response-of-shopify-and-verify-in-php-73d6e1946e2b
    if( !isset( $_SERVER[ 'HTTP_X_SHOPIFY_HMAC_SHA256' ] ) ){       // How to read header: https://stackoverflow.com/questions/541430/how-do-i-read-any-request-header-in-php
        http_response_code(401);
        $raw_headers = json_encode( $_SERVER );
        $sql = "INSERT INTO shopify_webhook_response( `remarks`, `raw_content`, `raw_headers` ) "
            . "VALUES( 'This call has not been originated from Shopify', '$e_data', '$raw_headers' )";
        insertQuery( $sql );
        send_json_mime_type_header();
        echo createJSONMessage(GENERAL_ERROR_MESSAGE, __FUNCTION__, "This call has not been originated from Shopify" );
        return;
    }
    
    
    //$calculated_hmac    = base64_encode( hash_hmac( 'sha256', $data, '2e94ac93aafd16155812f8db95bde8c2bc71bb77ae0b7d928483e2e248fbfcb2', true ) );
    //fwrite( $file, "hash_equals: " . intval( hash_equals( $hmac_header, $calculated_hmac ) ) . "\n" );
    //fwrite( $file, "!= " . intval( $hmac_header != $calculated_hmac ) . "\n" );
    //fwrite( $file, "\n ----\n" );
    
    if ( hash_equals( $hmac_header, $calculated_hmac ) === false ) {
        http_response_code(401);
        $raw_headers = json_encode( $_SERVER );
        $sql = "INSERT INTO shopify_webhook_response( `remarks`, `raw_content`, `raw_headers` ) "
            . "VALUES( 'HMAC verification failed', '$e_data', '$raw_headers' )";
        insertQuery( $sql );
        send_json_mime_type_header();
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "HMAC verification failed" );
        return;
    }
    
    http_response_code(200);
    
    // Store the Webhook data into a database table for records
    $shopify_headers = array();
    foreach ( $_SERVER as $key => $value) {
        if( strpos( $key, 'HTTP_X_SHOPIFY' ) === 0 ){
            $shopify_headers[ $key ] =  $value;
        }
    }
    
    
    
    $shopify_headers_json = json_encode( $shopify_headers );
    
    /*
    $sql = "INSERT INTO shopify_webhook_response( `raw_content`, `raw_headers` ) "
            . "VALUES( '$data', '$shopify_headers_json' )";
    insertQuery( $sql );
    */
    //fwrite( $file, $shopify_headers_json );
    //fwrite( $file, "\n ----\n" );
    
    
    //fwrite( $file, "Before Count: " + count( $shopify_headers ) );
    //fwrite( $file, "\n ----\n" );
    
    // Store Shopify Headers
    if( count( $shopify_headers ) > 0 ){
        //fwrite( $file, "Inside Count: " + count( $shopify_headers ) );
        //fwrite( $file, "\n ----\n" );
        
        $x_shopify_webhook_id       = '';
        $x_shopify_triggered_at     = '';
        $x_shopify_topic            = '';
        $x_shopify_test             = '';
        $x_shopify_shop_domain      = '';
        $x_shopify_hmac_sha256      = '';
        $x_shopify_event_id         = '';
        $x_shopify_api_version      = '';
        
        // Discard storing the data for duplicate webhook trigger
        // There are rare instances where a webhook is called more than once for the same event/
        // We compare the event id first
        if( isset( $shopify_headers[ 'HTTP_X_SHOPIFY_EVENT_ID' ] ) ){ $x_shopify_event_id = $shopify_headers[ 'HTTP_X_SHOPIFY_EVENT_ID' ]; }
        if( $x_shopify_event_id !== '' ){
            // Check if this event id already exist in the table
            $sql = "SELECT x_shopify_event_id FROM shopify_webhook_response WHERE x_shopify_event_id='$x_shopify_event_id'";
            $result_set = selectQuery( $sql );
            if( mysqli_num_rows( $result_set ) > 0 ){
                send_json_mime_type_header();
                echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "This is a duplicate call for the same event once more" );
                return;                
            }
        }
        
       // fwrite( $file, 'event id new' );
        //fwrite( $file, "\n ----\n" );
        
        if( isset( $shopify_headers[ 'HTTP_X_SHOPIFY_WEBHOOK_ID' ] ) ){  $x_shopify_webhook_id = $shopify_headers[ 'HTTP_X_SHOPIFY_WEBHOOK_ID' ]; }
        if( isset( $shopify_headers[ 'HTTP_X_SHOPIFY_TRIGGERED_AT' ] ) ){ $x_shopify_triggered_at = $shopify_headers[ 'HTTP_X_SHOPIFY_TRIGGERED_AT' ]; }
        if( isset( $shopify_headers[ 'HTTP_X_SHOPIFY_TOPIC' ] ) ){ $x_shopify_topic = $shopify_headers[ 'HTTP_X_SHOPIFY_TOPIC' ]; }
        if( isset( $shopify_headers[ 'HTTP_X_SHOPIFY_TEST' ] ) ){ 
            $x_shopify_test = $shopify_headers[ 'HTTP_X_SHOPIFY_TEST' ]; 
            $x_shopify_test = ($x_shopify_test==="true")?1:0;
        }
        if( isset( $shopify_headers[ 'HTTP_X_SHOPIFY_SHOP_DOMAIN' ] ) ){ $x_shopify_shop_domain = $shopify_headers[ 'HTTP_X_SHOPIFY_SHOP_DOMAIN' ]; }
        if( isset( $shopify_headers[ 'HTTP_X_SHOPIFY_HMAC_SHA256' ] ) ){ $x_shopify_hmac_sha256 = $shopify_headers[ 'HTTP_X_SHOPIFY_HMAC_SHA256' ]; }
        if( isset( $shopify_headers[ 'HTTP_X_SHOPIFY_API_VERSION' ] ) ){ $x_shopify_api_version = $shopify_headers[ 'HTTP_X_SHOPIFY_API_VERSION' ]; }
        
        // Store the received headers as database table columns
        $sql = "INSERT INTO shopify_webhook_response( `x_shopify_webhook_id`, `x_shopify_triggered_at`, `x_shopify_topic`, `x_shopify_shop_domain`, `x_shopify_hmac_sha256`, `x_shopify_event_id`, `x_shopify_api_version`, `x_shopify_test`, `remarks`, `raw_content`, `raw_headers` ) "
            . "VALUES( '$x_shopify_webhook_id', '$x_shopify_triggered_at', '$x_shopify_topic', '$x_shopify_shop_domain', '$x_shopify_hmac_sha256', '$x_shopify_event_id', '$x_shopify_api_version', '$x_shopify_test', 'Data stored successfully !', '$e_data', '$shopify_headers_json' )";
        insertQuery( $sql );
        
        //fwrite( $file, "insert query: " . $sql );
        //fwrite( $file, "\n -------------------------------------------------------------------------------------------------------------\n\n" );
        
        //echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, "Webhook event successfully stored in the DB" );
        //return; 
    }
    
    // Process the Response based on the $topic
    $topic = $shopify_headers[ 'HTTP_X_SHOPIFY_TOPIC' ];
    
    if( $topic == "customers/create" ){
        processShopifyCustomerCreateWebhookResponse( $data );
    }
    else if( $topic == "customers/update" ){
        processShopifyCustomerUpdateWebhookResponse( $data );
    }
    else if( $topic == "orders/create" ){
        processShopifyOrderCreateWebhookResponse( $data );
    }
    
}

/**
 * A simulation of shopify_webhook_response without the headers and validation
 */
function simulate_shopify_webhook_response(){
    global $store_name, $store_url, $client_id, $client_secret, $api_version;
    
    display_php_errors();
    
    $app_id      = request( 'app_id' );
    
    validateEmptyString( $app_id, __FUNCTION__, "App ID is required" );
    
    $e_app_id        = escape_string( $app_id );
    
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
    
    $data = '';
    $fh   = @fopen( 'php://input', 'r' );
    if ( $fh ){
        while ( !feof( $fh ) ){
            $s = fread( $fh, 1024 );
            if ( is_string( $s ) ){
              $data .= $s;
            }
        }
        fclose( $fh );
    }
    
    //$e_data = escape_string( $data );
    
    // Store the Webhook data into a database table for records
    $shopify_headers = array();
    foreach ( $_SERVER as $key => $value) {
        if( strpos( $key, 'HTTP_X_SHOPIFY' ) === 0 ){
            $shopify_headers[ $key ] =  $value;
        }
    }
    
    $shopify_headers_json = json_encode( $shopify_headers );
    
    // Store Shopify Headers
    if( count( $shopify_headers ) > 0 ){
        //fwrite( $file, "Inside Count: " + count( $shopify_headers ) );
        //fwrite( $file, "\n ----\n" );
        
        $x_shopify_webhook_id       = '';
        $x_shopify_triggered_at     = '';
        $x_shopify_topic            = '';
        $x_shopify_test             = '';
        $x_shopify_shop_domain      = '';
        $x_shopify_hmac_sha256      = '';
        $x_shopify_event_id         = '';
        $x_shopify_api_version      = '';
        
        // Discard storing the data for duplicate webhook trigger
        // There are rare instances where a webhook is called more than once for the same event/
        // We compare the event id first
        if( isset( $shopify_headers[ 'HTTP_X_SHOPIFY_EVENT_ID' ] ) ){ $x_shopify_event_id = $shopify_headers[ 'HTTP_X_SHOPIFY_EVENT_ID' ]; }
        
        
        if( isset( $shopify_headers[ 'HTTP_X_SHOPIFY_WEBHOOK_ID' ] ) ){  $x_shopify_webhook_id = $shopify_headers[ 'HTTP_X_SHOPIFY_WEBHOOK_ID' ]; }
        if( isset( $shopify_headers[ 'HTTP_X_SHOPIFY_TRIGGERED_AT' ] ) ){ $x_shopify_triggered_at = $shopify_headers[ 'HTTP_X_SHOPIFY_TRIGGERED_AT' ]; }
        if( isset( $shopify_headers[ 'HTTP_X_SHOPIFY_TOPIC' ] ) ){ $x_shopify_topic = $shopify_headers[ 'HTTP_X_SHOPIFY_TOPIC' ]; }
        if( isset( $shopify_headers[ 'HTTP_X_SHOPIFY_TEST' ] ) ){
            $x_shopify_test = $shopify_headers[ 'HTTP_X_SHOPIFY_TEST' ]; 
            $x_shopify_test = ($x_shopify_test==="true")?1:0;
        }
        if( isset( $shopify_headers[ 'HTTP_X_SHOPIFY_SHOP_DOMAIN' ] ) ){ $x_shopify_shop_domain = $shopify_headers[ 'HTTP_X_SHOPIFY_SHOP_DOMAIN' ]; }
        if( isset( $shopify_headers[ 'HTTP_X_SHOPIFY_HMAC_SHA256' ] ) ){ $x_shopify_hmac_sha256 = $shopify_headers[ 'HTTP_X_SHOPIFY_HMAC_SHA256' ]; }
        if( isset( $shopify_headers[ 'HTTP_X_SHOPIFY_API_VERSION' ] ) ){ $x_shopify_api_version = $shopify_headers[ 'HTTP_X_SHOPIFY_API_VERSION' ]; }
        
        
    }
    
    // Process the Response based on the $topic
    $topic = $shopify_headers[ 'HTTP_X_SHOPIFY_TOPIC' ];
    
    if( $topic == "customers/create" ){
        processShopifyCustomerCreateWebhookResponse( $data );
    }
    else if( $topic == "customers/update" ){
        processShopifyCustomerUpdateWebhookResponse( $data );
    }
    else if( $topic == "orders/create" ){
        processShopifyOrderCreateWebhookResponse( $data );
    }
    else if( $topic == "orders/cancel" ){
        processShopifyOrderCancelledWebhookResponse( $data );
    }
    
}

function processShopifyOrderCancelledWebhookResponse( $data ){
    require_once PLU_PATH . FILE_SEPARATOR . "appstv_crm_customer/includes" . FILE_SEPARATOR . "constants.php";
    require_once PLU_PATH . FILE_SEPARATOR . "appstv_crm_customer/includes" . FILE_SEPARATOR . "customer.php";
    
    $shopify_data = json_decode( $data, true );
    //print_r( $shopify_data );
    
    // Grab the Shopify Order ID in the response
    $shopify_order_id = data_exists( SHOPIFY_ORDER_ID, $shopify_data )?$shopify_data[ SHOPIFY_ORDER_ID ]:'';
    
    // Check if the crm_order_id exist in the CRM, 
    $existing_crm_order_id = OrderMeta::getOrderIdFromOrderMetaKeyValue( OrderMeta::DB_ORDER_META_KEY_SHOPIFY_ORDER_ID, $shopify_order_id );
    if( $existing_crm_order_id !== NULL ){
        // if it does not exist, then create this order in the CRM
        processShopifyOrderCreateWebhookResponse( $data );
        //return;
    }
    
    $responseMessages = array();
    
    // Capture the order data into variables
    $cancelled_at             = data_exists( SHOPIFY_ORDER_CANCELLED_AT, $shopify_data )?$shopify_data[ SHOPIFY_ORDER_CANCELLED_AT ]:'';
    $is_cancelled             = ( $cancelled_at != "" )?"1":"0";
    $financial_status         = data_exists( SHOPIFY_ORDER_FINANCIAL_STATUS, $shopify_data )?$shopify_data[ SHOPIFY_ORDER_FINANCIAL_STATUS ]:'';
    $updated_at               = data_exists( SHOPIFY_ORDER_UPDATED_AT, $shopify_data )?$shopify_data[ SHOPIFY_ORDER_UPDATED_AT ]:'';
    
    // Create the Order Object
    $order = new Order();
    $order->setOrderID( $existing_crm_order_id );
    
    // Edit the Order Object data
    $order->setIsCancelled( $is_cancelled );
    
    if( $is_cancelled === "1" ){
        $order->setOrderStatus( Order::ORDER_STATUS_CANCELLED );
    }
    
    if( $financial_status === "voided" ){
        $order->setOrderStatus( Order::ORDER_STATUS_CANCELLED );
    }
    
    $order->setFinancialStatus( $financial_status );
    $order->setUpdatedAt( $updated_at );
    $order->updateOrder();
    
    $cancel_reason            = data_exists( SHOPIFY_ORDER_CANCEL_REASON, $shopify_data )?$shopify_data[ SHOPIFY_ORDER_CANCEL_REASON ]:'';    
    
    $e_cancel_reason          = escape_string( $cancel_reason );
    
    if( ($is_cancelled == "1") || ($financial_status === "voided") ){
        OrderMeta::setOrderMetaValue( $order->getOrderID(), OrderMeta::DB_ORDER_META_KEY_SHOPIFY_ORDER_CANCEL_REASON, $e_cancel_reason );
        OrderMeta::setOrderMetaValue( $order->getOrderID(), OrderMeta::DB_ORDER_META_KEY_SHOPIFY_ORDER_CANCELLED_AT, $cancelled_at );
    }
    
    // Update the status in order_status_history
    OrderStatusHistory::addToOrderStatusHistory( $existing_crm_order_id, "SHOPIFY_WEBHOOK", Order::ORDER_STATUS_CANCELLED );
    
    // Void the Email on QuickBooks
    /**
     * 1. Retrieve the Invoice ID of the QuickBooks from the OrderMeta table
     * 2. Void the Invoice on QuickBooks using its API
     */
    $quickbooks_invoice_id = OrderMeta::getOrderMetaValue( $existing_crm_order_id, OrderMeta::DB_ORDER_META_KEY_QUICKBOOKS_INVOICE_ID );
    if( $quickbooks_invoice_id !== NULL ){
        $dataService = getQuickBooksDataService();
        $invoiceParams = array(
            "Id" => $quickbooks_invoice_id
        );
        $invoiceObj = \QuickBooksOnline\API\Facades\Invoice::create( $invoiceParams );
        $resultingObj = $dataService->Void( $invoiceObj );
        $error = $dataService->getLastError();
        if( $error ) {
            //echo "The Status code is: " . $error->getHttpStatusCode() . "\n";
            //echo "The Helper message is: " . $error->getOAuthHelperError() . "\n";
            //echo "The Response message is: " . $error->getResponseBody() . "\n";
            $responseMessages[] = "Failed to void invoice on QuickBooks: " . $error->getResponseBody();
        }
        else{
            $responseMessages[] = "Voided Invoice Id={$resultingObj->Id}. Reconstructed response body:\n\n";
        }
    }
    else{
        $responseMessages[] = "Invoice does not exist in QuickBooks for this Order !";
    }
    
    // Send an email to the orders@appstv.com.sg 
    $mail = sendMailObject();
    $mail->isHTML( true );
    $mail->setFrom( "orders@appstv.com.sg", 'Orders | ' . $siteConfig->site_name );
    $mail->AddAddress( "orders@appstv.com.sg" );
    $mail->addBCC( 'sohail@excel.com.sg' );
    $mail->Subject = "Order has been cancelled on appstv.com.sg";

    $message = file_get_contents( "templates/email/myappstv_order_cancelled_from_shopify.php" );
    $message = str_replace( $message, "{{crm_order_id}}", $existing_crm_order_id );
    $message = str_replace( $message, "{{shopify_order_id}}", $shopify_order_id );
    //$message = str_replace( $message, "{{delivery_messages}}", $shopify_order_id );

    $mail->Body = $message;
    
    if ( !$mail->Send() ) {
        $responseMessages[] = "Email did not send on the orders@appstv.com.sg !";

        // Log here about email not sending out        
    }
    
    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, "Order has been cancelled" );
}

function processShopifyOrderCreateWebhookResponse( $data ){
    require_once PLU_PATH . FILE_SEPARATOR . "appstv_crm_customer/includes" . FILE_SEPARATOR . "constants.php";
    require_once PLU_PATH . FILE_SEPARATOR . "appstv_crm_customer/includes" . FILE_SEPARATOR . "customer.php";
    
    $shopify_data = json_decode( $data, true );
    //print_r( $shopify_data );
    
    // Grab the Shopify Order ID in the response
    $shopify_order_id = data_exists( SHOPIFY_ORDER_ID, $shopify_data )?$shopify_data[ SHOPIFY_ORDER_ID ]:'';
    
    // Check if it is the repeat data received for an order that already exist
    $existing_crm_order_id = OrderMeta::getOrderIdFromOrderMetaKeyValue( OrderMeta::DB_ORDER_META_KEY_SHOPIFY_ORDER_ID, $shopify_order_id );
    if( $existing_crm_order_id !== NULL ){
        // Do not proceed to create the order as this order details already exist
        send_json_mime_type_header();
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "This order id already exist" );
        return;
    }
    
    // Capture the order data into variables
    //$cancelled_at = 
    $created_at               = data_exists( SHOPIFY_ORDER_CREATED_AT, $shopify_data )?$shopify_data[ SHOPIFY_ORDER_CREATED_AT ]:'';
    $currency                 = data_exists( SHOPIFY_ORDER_CURRENCY, $shopify_data )?$shopify_data[ SHOPIFY_ORDER_CURRENCY ]:'';
    $contact_email            = data_exists( SHOPIFY_ORDER_CONTACT_EMAIL, $shopify_data )?$shopify_data[ SHOPIFY_ORDER_CONTACT_EMAIL ]:'';
    $cancelled_at             = data_exists( SHOPIFY_ORDER_CANCELLED_AT, $shopify_data )?$shopify_data[ SHOPIFY_ORDER_CANCELLED_AT ]:'';
    $is_cancelled             = ( $cancelled_at != "" )?"1":"0";
    $email                    = data_exists( SHOPIFY_ORDER_EMAIL, $shopify_data )?$shopify_data[ SHOPIFY_ORDER_EMAIL ]:'';
    $financial_status         = data_exists( SHOPIFY_ORDER_FINANCIAL_STATUS, $shopify_data )?$shopify_data[ SHOPIFY_ORDER_FINANCIAL_STATUS ]:'';
    $fulfillment_status       = data_exists( SHOPIFY_ORDER_FULFILLMENT_STATUS, $shopify_data )?$shopify_data[ SHOPIFY_ORDER_FULFILLMENT_STATUS ]:'';
    $processed_at             = data_exists( SHOPIFY_ORDER_PROCESSED_AT, $shopify_data )?$shopify_data[ SHOPIFY_ORDER_PROCESSED_AT ]:'';
        $products                 = data_exists( SHOPIFY_ORDER_PRODUCTS, $shopify_data )?$shopify_data[ SHOPIFY_ORDER_PRODUCTS ]:'';                // Store as JSON
    $total_products_price     = data_exists( SHOPIFY_ORDER_TOTAL_LINE_ITEMS_PRICE, $shopify_data )?$shopify_data[ SHOPIFY_ORDER_TOTAL_LINE_ITEMS_PRICE ]:'';
        $shipping_information     = data_exists( SHOPIFY_ORDER_SHIPPING_LINES, $shopify_data )?$shopify_data[ SHOPIFY_ORDER_SHIPPING_LINES ]:'';    // Store as JSON
    $subtotal_price           = data_exists( SHOPIFY_ORDER_SUBTOTAL_PRICE, $shopify_data )?$shopify_data[ SHOPIFY_ORDER_SUBTOTAL_PRICE ]:'';
        $taxes_included           = data_exists( SHOPIFY_ORDER_TAXES_INCLUDED, $shopify_data )?$shopify_data[ SHOPIFY_ORDER_TAXES_INCLUDED ]:'0';    // boolean
        $test                     = data_exists( SHOPIFY_ORDER_TEST, $shopify_data )?$shopify_data[ SHOPIFY_ORDER_TEST ]:'0';    // boolean
    $total_price              = data_exists( SHOPIFY_ORDER_TOTAL_PRICE, $shopify_data )?$shopify_data[ SHOPIFY_ORDER_TOTAL_PRICE ]:'';
    $total_discounts          = data_exists( SHOPIFY_ORDER_TOTAL_DISCOUNTS, $shopify_data )?$shopify_data[ SHOPIFY_ORDER_TOTAL_DISCOUNTS ]:'';
        $total_shipping_price     = data_exists( SHOPIFY_ORDER_TOTAL_SHIPPING_PRICE, $shopify_data )?$shopify_data[ SHOPIFY_ORDER_TOTAL_SHIPPING_PRICE ]:'';        // // Process and retrieve the price
    $total_tax                = data_exists( SHOPIFY_ORDER_TOTAL_TAX, $shopify_data )?$shopify_data[ SHOPIFY_ORDER_TOTAL_TAX ]:'';
    $total_weight             = data_exists( SHOPIFY_ORDER_TOTAL_WEIGHT, $shopify_data )?$shopify_data[ SHOPIFY_ORDER_TOTAL_WEIGHT ]:'';
    $updated_at               = data_exists( SHOPIFY_ORDER_UPDATED_AT, $shopify_data )?$shopify_data[ SHOPIFY_ORDER_UPDATED_AT ]:'';
    // $                     = data_exists( '', $shopify_data )?$shopify_data[ '' ]:'';
    
    $e_products                = escape_string( json_encode( $products ) );
    $e_shipping_information    = escape_string( json_encode( $shipping_information ) );
    
    // Set order status according to the financial status
    $order_status = 'processed';
    if( $financial_status == "paid" ){
        $order_status = 'confirmed';
    }
    else if( $financial_status == "pending" ){
        $order_status = 'processing';
    }
    else if( $financial_status == "voided" ){
        $order_status = 'cancelled';
    }
    
    // Retrieve the shipping method name
    $shipping_method_name = $shipping_information[ 0 ][ 'title' ];
    
    $e_shipping_method_name = escape_string( $shipping_method_name );
    
    // Retrieve the total shipping price
    $total_shipping_price = $total_shipping_price[ 'shop_money' ][ 'amount' ];
    
    // Retrieve the total shipping tax
    if( isset( $shipping_information[ 0 ][ 'tax_lines' ][ 0 ][ 'price' ] ) ){
        $total_shipping_tax = $shipping_information[ 0 ][ 'tax_lines' ][ 0 ][ 'price' ];
    }
    else{
        $total_shipping_tax = "0.00";
    }
    
    // Capture the customer data into variables
    $shopify_customer           = data_exists( SHOPIFY_ORDER_CUSTOMER, $shopify_data )?$shopify_data[ SHOPIFY_ORDER_CUSTOMER ]:array();
    
    $shopify_customer_id        = data_exists( SHOPIFY_CUSTOMER_ID, $shopify_customer )?$shopify_customer[ SHOPIFY_CUSTOMER_ID ]:'';
    if( $shopify_customer_id === "" ){
        // Store this data in error logs in DB
        echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, "Customer ID is missing in the Shopify Order Response" );
        return; 
    }
        
    // Update the Customer information
    processShopifyCustomerUpdateWebhookResponse( json_encode( $shopify_customer ) );
    
    // Store the order details in the orders table
    $customer_id = CustomerMeta::getCustomerIdFromCustomerMetaKeyValue( CustomerMeta::DB_CUSTOMER_META_KEY_SHOPIFY_CUSTOMER_ID, $shopify_customer_id );
    $order_id    = Order::generateOrderID();
    
    $order = new Order();
    $order->setOrderID( $order_id );
    $order->setCustomerID( $customer_id );
    $order->setOrderSource( 's7YtgHu79D_so' );          // s7YtgHu79D_so -> Shopify store in sources table
    $order->setCreatedAt( $created_at );
    $order->setOrderStatus( $order_status );
    $order->setIsCancelled( $is_cancelled );
    $order->setCurrency( $currency );
    $order->setContactEmail( $contact_email );
    $order->setEmail( $email );
    $order->setFinancialStatus( $financial_status );
    $order->setFulfillmentStatus( $fulfillment_status );
    $order->setProcessedAt( $processed_at );
    $order->setProducts( $e_products );
    $order->setShippingInformation( $e_shipping_information );
    $order->setShippingMethodName( $e_shipping_method_name );
    $order->setTotalProductsPrice( $total_products_price );
    $order->setSubtotalPrice( $subtotal_price );
    $order->setTaxesIncluded( $taxes_included );
    $order->setTest( $test );
    $order->setTotalPrice( $total_price );
    $order->setTotalDiscounts( $total_discounts );
    $order->setTotalShippingPrice( $total_shipping_price );
    $order->setTotalShippingTax( $total_shipping_tax );
    $order->setTotalTax( $total_tax );
    $order->setTotalWeight( $total_weight );
    $order->setUpdatedAt( $updated_at );
    $order->createOrder();
    
    // Update the status in order_status_history
    OrderStatusHistory::addToOrderStatusHistory( $order_id, "SHOPIFY_WEBHOOK", Order::ORDER_STATUS_CANCELLED );
    
    // Capture the Order Meta data into variables
    // Store other values as order_meta
    // Check if the order has been cancelled
    $cancel_reason            = data_exists( SHOPIFY_ORDER_CANCEL_REASON, $shopify_data )?$shopify_data[ SHOPIFY_ORDER_CANCEL_REASON ]:'';    
    $order_number             = data_exists( SHOPIFY_ORDER_ORDER_NUMBER, $shopify_data )?$shopify_data[ SHOPIFY_ORDER_ORDER_NUMBER ]:'';
    $order_name               = data_exists( SHOPIFY_ORDER_ORDER_NAME, $shopify_data )?$shopify_data[ SHOPIFY_ORDER_ORDER_NAME ]:'';
    $confirmed                = data_exists( SHOPIFY_ORDER_CONFIRMED, $shopify_data )?$shopify_data[ SHOPIFY_ORDER_CONFIRMED ]:'true';      // boolean
    $order_status_url         = data_exists( SHOPIFY_ORDER_ORDER_STATUS_URL, $shopify_data )?$shopify_data[ SHOPIFY_ORDER_ORDER_STATUS_URL ]:'';
    $discount_codes           = data_exists( SHOPIFY_ORDER_DISCOUNT_CODES, $shopify_data )?$shopify_data[ SHOPIFY_ORDER_DISCOUNT_CODES ]:array();      // store as json
    $payment_gateway_names    = data_exists( SHOPIFY_ORDER_PAYMENT_GATEWAY_NAMES, $shopify_data )?$shopify_data[ SHOPIFY_ORDER_PAYMENT_GATEWAY_NAMES ]:array();   // store as json
    
    $e_cancel_reason            = escape_string( $cancel_reason );
    $e_discount_codes           = escape_string( json_encode( $discount_codes ) );
    $e_payment_gateway_names    = escape_string( json_encode( $payment_gateway_names ) );
    
    if( $is_cancelled == "1" ){
        OrderMeta::setOrderMetaValue( $order->getOrderID(), OrderMeta::DB_ORDER_META_KEY_SHOPIFY_ORDER_CANCEL_REASON, $e_cancel_reason );
        OrderMeta::setOrderMetaValue( $order->getOrderID(), OrderMeta::DB_ORDER_META_KEY_SHOPIFY_ORDER_CANCELLED_AT, $cancelled_at );
    }
    OrderMeta::setOrderMetaValue( $order->getOrderID(), OrderMeta::DB_ORDER_META_KEY_SHOPIFY_ORDER_ID, $shopify_order_id );
    OrderMeta::setOrderMetaValue( $order->getOrderID(), OrderMeta::DB_ORDER_META_KEY_SHOPIFY_ORDER_NUMBER, $order_number );
    OrderMeta::setOrderMetaValue( $order->getOrderID(), OrderMeta::DB_ORDER_META_KEY_SHOPIFY_ORDER_NAME, $order_name );
    OrderMeta::setOrderMetaValue( $order->getOrderID(), OrderMeta::DB_ORDER_META_KEY_SHOPIFY_ORDER_CONFIRMED, $confirmed );
    OrderMeta::setOrderMetaValue( $order->getOrderID(), OrderMeta::DB_ORDER_META_KEY_SHOPIFY_ORDER_STATUS_URL, $order_status_url );
    OrderMeta::setOrderMetaValue( $order->getOrderID(), OrderMeta::DB_ORDER_META_KEY_SHOPIFY_ORDER_DISCOUNT_CODES, $e_discount_codes );
    OrderMeta::setOrderMetaValue( $order->getOrderID(), OrderMeta::DB_ORDER_META_KEY_SHOPIFY_ORDER_PAYMENT_GATEWAY_NAMES, $e_payment_gateway_names );
    // OrderMeta::setOrderMetaValue( $order->getOrderID(), , );
    
    // Capture Order Address (Billing) data into variables
    $billing_address        = data_exists( SHOPIFY_BILLING_ADDRESS, $shopify_data )?$shopify_data[ SHOPIFY_BILLING_ADDRESS ]:'';
    $ba_first_name          = data_exists( SHOPIFY_FIRST_NAME, $billing_address )?$billing_address[ SHOPIFY_FIRST_NAME ]:'';
    $ba_last_name           = data_exists( SHOPIFY_LAST_NAME, $billing_address )?$billing_address[ SHOPIFY_LAST_NAME ]:'';
    $ba_name_on_order       = data_exists( SHOPIFY_NAME_ON_ORDER, $billing_address )?$billing_address[ SHOPIFY_NAME_ON_ORDER ]:'';
    $ba_address1            = data_exists( SHOPIFY_ADDRESS1, $billing_address )?$billing_address[ SHOPIFY_ADDRESS1 ]:'';
    $ba_address2            = data_exists( SHOPIFY_ADDRESS2, $billing_address )?$billing_address[ SHOPIFY_ADDRESS2 ]:'';
    $ba_contact             = data_exists( SHOPIFY_PHONE, $billing_address )?$billing_address[ SHOPIFY_PHONE ]:'';
    $ba_city                = data_exists( SHOPIFY_CITY, $billing_address )?$billing_address[ SHOPIFY_CITY ]:'';
    $ba_state               = data_exists( SHOPIFY_STATE, $billing_address )?$billing_address[ SHOPIFY_STATE ]:'';
    $ba_province            = data_exists( SHOPIFY_PROVINCE, $billing_address )?$billing_address[ SHOPIFY_PROVINCE ]:'';
    $ba_zip                 = data_exists( SHOPIFY_ZIP, $billing_address )?$billing_address[ SHOPIFY_ZIP ]:'';
    $ba_latitude            = data_exists( SHOPIFY_LATITUDE, $billing_address )?$billing_address[ SHOPIFY_LATITUDE ]:'';
    $ba_longitude           = data_exists( SHOPIFY_LONGITUDE, $billing_address )?$billing_address[ SHOPIFY_LONGITUDE ]:'';
    $ba_country             = data_exists( SHOPIFY_COUNTRY, $billing_address )?$billing_address[ SHOPIFY_COUNTRY ]:'';
    $ba_country_code        = data_exists( SHOPIFY_COUNTRY_CODE, $billing_address )?$billing_address[ SHOPIFY_COUNTRY_CODE ]:'';
    
    $e_ba_first_name           = escape_string( $ba_first_name );
    $e_ba_last_name            = escape_string( $ba_last_name );
    $e_ba_name_on_order        = escape_string( $ba_name_on_order );
    $e_ba_address1             = escape_string( $ba_address1 );
    $e_ba_address2             = escape_string( $ba_address2 );
    $e_ba_contact              = escape_string( $ba_contact );
    $e_ba_city                 = escape_string( $ba_city );
    $e_ba_state                = escape_string( $ba_state );
    $e_ba_province             = escape_string( $ba_province );
    $e_ba_zip                  = escape_string( $ba_zip );
    
    // Store Order Address in order address table
    $orderAddressBilling = new OrderAddress();    
    $orderAddressBilling->setOrderAddressID( OrderAddress::generateOrderAddressID() );
    $orderAddressBilling->setOrderID( $order->getOrderID() );
    $orderAddressBilling->setAddressType( OrderAddress::DB_FIELD_ORDER_ADDRESS_ADDRESS_TYPE_BILLING );
    $orderAddressBilling->setFirstName( $e_ba_first_name );
    $orderAddressBilling->setLastName( $e_ba_last_name);
    $orderAddressBilling->setNameOnOrder( $e_ba_name_on_order );
    $orderAddressBilling->setAddress1( $e_ba_address1 );
    $orderAddressBilling->setAddress2( $e_ba_address2 );
    $orderAddressBilling->setContact( $e_ba_contact );
    $orderAddressBilling->setCity( $e_ba_city );
    $orderAddressBilling->setState( $e_ba_state );
    $orderAddressBilling->setProvince( $e_ba_province );
    $orderAddressBilling->setZip( $e_ba_zip );
    $orderAddressBilling->setLatitude( $ba_latitude );
    $orderAddressBilling->setLongitude( $ba_longitude );
    $orderAddressBilling->setCountry( $ba_country );
    $orderAddressBilling->setCountryCode( $ba_country_code );
    $orderAddressBilling->createOrderAddress();
    
    
    // Capture Order Address (Shipping) data into variables
    $shipping_address       = data_exists( SHOPIFY_SHIPPING_ADDRESS, $shopify_data )?$shopify_data[ SHOPIFY_SHIPPING_ADDRESS ]:'';
    $sa_first_name          = data_exists( SHOPIFY_FIRST_NAME, $shipping_address )?$shipping_address[ SHOPIFY_FIRST_NAME ]:'';
    $sa_last_name           = data_exists( SHOPIFY_LAST_NAME, $shipping_address )?$shipping_address[ SHOPIFY_LAST_NAME ]:'';
    $sa_name_on_order       = data_exists( SHOPIFY_NAME_ON_ORDER, $shipping_address )?$shipping_address[ SHOPIFY_NAME_ON_ORDER ]:'';
    $sa_address1            = data_exists( SHOPIFY_ADDRESS1, $shipping_address )?$shipping_address[ SHOPIFY_ADDRESS1 ]:'';
    $sa_address2            = data_exists( SHOPIFY_ADDRESS2, $shipping_address )?$shipping_address[ SHOPIFY_ADDRESS2 ]:'';
    $sa_contact             = data_exists( SHOPIFY_PHONE, $shipping_address )?$shipping_address[ SHOPIFY_PHONE ]:'';
    $sa_city                = data_exists( SHOPIFY_CITY, $shipping_address )?$shipping_address[ SHOPIFY_CITY ]:'';
    $sa_state               = data_exists( SHOPIFY_STATE, $shipping_address )?$shipping_address[ SHOPIFY_STATE ]:'';
    $sa_province            = data_exists( SHOPIFY_PROVINCE, $shipping_address )?$shipping_address[ SHOPIFY_PROVINCE ]:'';
    $sa_zip                 = data_exists( SHOPIFY_ZIP, $shipping_address )?$shipping_address[ SHOPIFY_ZIP ]:'';
    $sa_latitude            = data_exists( SHOPIFY_LATITUDE, $shipping_address )?$shipping_address[ SHOPIFY_LATITUDE ]:'';
    $sa_longitude           = data_exists( SHOPIFY_LONGITUDE, $shipping_address )?$shipping_address[ SHOPIFY_LONGITUDE ]:'';
    $sa_country             = data_exists( SHOPIFY_COUNTRY, $shipping_address )?$shipping_address[ SHOPIFY_COUNTRY ]:'';
    $sa_country_code        = data_exists( SHOPIFY_COUNTRY_CODE, $shipping_address )?$shipping_address[ SHOPIFY_COUNTRY_CODE ]:'';
    
    $e_sa_first_name           = escape_string( $sa_first_name );
    $e_sa_last_name            = escape_string( $sa_last_name );
    $e_sa_name_on_order        = escape_string( $sa_name_on_order );
    $e_sa_address1             = escape_string( $sa_address1 );
    $e_sa_address2             = escape_string( $sa_address2 );
    $e_sa_contact              = escape_string( $sa_contact );
    $e_sa_city                 = escape_string( $sa_city );
    $e_sa_state                = escape_string( $sa_state );
    $e_sa_province             = escape_string( $sa_province );
    $e_sa_zip                  = escape_string( $sa_zip );
    
    // Store Order Address in order address table
    $orderAddressShipping = new OrderAddress();    
    $orderAddressShipping->setOrderAddressID( OrderAddress::generateOrderAddressID() );
    $orderAddressShipping->setOrderID( $order->getOrderID() );
    $orderAddressShipping->setAddressType( OrderAddress::DB_FIELD_ORDER_ADDRESS_ADDRESS_TYPE_SHIPPING );
    $orderAddressShipping->setFirstName( $e_sa_first_name );
    $orderAddressShipping->setLastName( $e_sa_last_name);
    $orderAddressShipping->setNameOnOrder( $e_sa_name_on_order );
    $orderAddressShipping->setAddress1( $e_sa_address1 );
    $orderAddressShipping->setAddress2( $e_sa_address2 );
    $orderAddressShipping->setContact( $e_sa_contact );
    $orderAddressShipping->setCity( $e_sa_city );
    $orderAddressShipping->setState( $e_sa_state );
    $orderAddressShipping->setProvince( $e_sa_province );
    $orderAddressShipping->setZip( $e_sa_zip );
    $orderAddressShipping->setLatitude( $sa_latitude );
    $orderAddressShipping->setLongitude( $sa_longitude );
    $orderAddressShipping->setCountry( $sa_country );
    $orderAddressShipping->setCountryCode( $sa_country_code );
    $orderAddressShipping->createOrderAddress();
    
    // Create the Invoice in the QuickBooks
    $customer                   = new Customer();
    $customer->readCustomer( $order->getCustomerID() );
    $quickbooks_customer_id = CustomerMeta::getCustomerMetaValue( $customer->getCustomerID() , CustomerMeta::DB_CUSTOMER_META_KEY_QUICKBOOKS_CUSTOMER_ID );
    
    $dataService                = getQuickBooksDataService();
    $quickbooks_line_items      = array();
    $order_products_array       = $products;//json_decode( $order->getProducts(), true );
    $totalAmount                = 0;
    foreach ( $order_products_array as $shopifyProduct ) {
        
        $shopify_product_id     = $shopifyProduct[ 'product_id' ];
        
        /*
        // Retrieve CRM Product ID from ProductMeta table using Shopify Product ID
        $crm_product_id             = ProductMeta::getProductIdFromProductMetaKeyValue( ProductMeta::DB_PRODUCT_META_KEY_SHOPIFY_PRODUCT_ID, $shopifyProduct[ 'product_id' ] );
        */
        
        $sku                    = $shopifyProduct[ SHOPIFY_PRODUCT_SKU ];
        
        $crmProduct = new Product();
        $crmProduct->readProductUsingSKU( $sku );
        
        // Use the product_name from the shopify, because we have variants now that gives us a different product name 
        $product_name           = $shopifyProduct[ 'name' ]; //$crmProduct->getName();
        
        $qty                    = intval( $shopifyProduct[ 'quantity' ] );
        $unit_price             = round( floatval( $shopifyProduct[ SHOPIFY_PRODUCT_PRICE ] ), 2 );
        $amount                 = round( $qty * $unit_price, 2 );                           // 2 decimal places after . 
        
        
        // Unit Price exclusive of GST 9%
        $unit_price_exc_gst = round(($unit_price/1.09), 4);
        $amount_exc_gst     = round( $qty * $unit_price_exc_gst, 4 ); 
        
        
        // Retrieve QuickBooks Product ID from ProductMeta table using CRM product_id
        $quickbooks_product_id  = ProductMeta::getQuickBooksProductID( $crmProduct->getProductID() );
                
        $tempLineItem = array(
            "DetailType" => "SalesItemLineDetail",
            "SalesItemLineDetail" => array(
                "ItemRef" => array(
                    "value" => $quickbooks_product_id,
                    "name" => $product_name
                ),
                "Qty" => $qty,
                "UnitPrice" => $unit_price_exc_gst,
                "TaxCodeRef" => array(
                    "value" => "44"         // -> GST SR 9% in QuickBooks TaxCode
                )
            ),
            "Amount" => $amount_exc_gst,
            "Description" => $shopifyProduct[ 'variant_title' ]
        );
        array_push( $quickbooks_line_items, $tempLineItem );
        
        //$totalAmount += $amount;
    }
    //$totalAmount = round( $totalAmount, 2 );
    
    // Discount Line Items
    $discount_codes_json    = OrderMeta::getOrderMetaValue( $order->getOrderID(), OrderMeta::DB_ORDER_META_KEY_SHOPIFY_ORDER_DISCOUNT_CODES );
    $discount_codes_array   = json_decode( $discount_codes_json, true );
    //$discount_line_item     = array();
    foreach ( $discount_codes_array as $discount ) {
        $tempDiscountLineItem = array(
            "DetailType" => "DiscountLineDetail",
            "DiscountLineDetail" => array(
                "PercentBased" => false,
                "DiscountAccountRef" => array(
                    "value" => "40"
                )
            ),
            "Amount" => $discount[ 'amount' ],
            "Description" => 'Coupon code: ' . $discount[ 'code' ]
        );
        array_push( $quickbooks_line_items, $tempDiscountLineItem );
    }
    
    // Shipping Line Items
    $total_shipping_price   = $order->getTotalShippingPrice();
    $shipping_price_exc_tax = round(($total_shipping_price/1.09), 4);
    $shippingLineItem       = array(
        "DetailType" => "SalesItemLineDetail",
        "SalesItemLineDetail" => array(
            "TaxInclusiveAmt" => $total_shipping_price,
            "ItemRef" => array(
                "value" => "SHIPPING_ITEM_ID"
            ),
            "TaxCodeRef" => array(
                "value" => "44"
            )
        ),
        "Amount" => $shipping_price_exc_tax
    );
    array_push( $quickbooks_line_items, $shippingLineItem );
    
    $invoiceParams = array(
        "Line" => $quickbooks_line_items,
        //"TotalAmt" => $totalAmount,           // Dont need to specify, QuickBooks auto calculates it        
        "CustomerRef" => array(
            "name" => $customer->getFirstName() . " " . $customer->getLastName(),
            "value" => $quickbooks_customer_id
        ),
        "BillEmail" => array(
            "Address" => $order->getEmail()
        ),
        "BillEmailBcc" => array(
            "Address" => "unknown0492@gmail.com"
        ),
        "BillAddr" => array(
            "Line1" => $orderAddressBilling->getFirstName() . " " . $orderAddressBilling->getLastName(),
            "Line2" => $orderAddressBilling->getAddress1(),
            "Line3" => $orderAddressBilling->getAddress2(),
            "Line4" => $orderAddressBilling->getCity() . ", " . $orderAddressBilling->getState() . ", " . $orderAddressBilling->getCountry(),
            "Line5" => $orderAddressBilling->getZip(),
            "Lat" => $orderAddressBilling->getLatitude(),
            "Long" => $orderAddressBilling->getLongitude(),
        ),
        "ShipAddr" => array(
            "Line1" => $orderAddressShipping->getAddress1(),
            "Line2" => $orderAddressShipping->getAddress2(),
            "City" => $orderAddressShipping->getCity(),
            "PostalCode" => $orderAddressShipping->getZip(),
            "Lat" => $orderAddressShipping->getLatitude(),
            "Long" => $orderAddressShipping->getLongitude(),
        ),
        "ApplyTaxAfterDiscount" => false,
        "GlobalTaxCalculation" => "TaxInclusive",
        /*
        "TxnTaxDetail" => array(            
            //"TxnTaxCodeRef" => array(
            //    "value"=> "2"
            //),            
            //"TotalTax"=> 0,
            "TaxLine"=> array(
                array(
                    //"Amount"=> 0,
                    "DetailType"=> "TaxLineDetail",
                    "TaxLineDetail"=> array(
                        "TaxRateRef"=> array(
                            "value"=> "49"
                        ),
                        "PercentBased" => true,
                        //"TaxPercent"=> 0,
                        //"NetAmountTaxable"=> $totalAmount
                    ),
                )
            )
        )*/
    );
    print_r( $invoiceParams );
    echo json_encode( $invoiceParams );
    $invoiceObj = \QuickBooksOnline\API\Facades\Invoice::create($invoiceParams
        /*    
        "Discount" => [],
        // CustomeFields to be used to update the invoice with the serial numbers of the products
        "CustomField" => array(
            array(
                "Type" => "StringType",
                "Name" => "Serial Number",
                "StringValue" => "xxxxxxxxx"
            )
        )
        */
    );
    $resultingObj = $dataService->Add( $invoiceObj );
    $error = $dataService->getLastError();
     if ($error) {
         //echo "The Status code is: " . $error->getHttpStatusCode() . "\n";
         //echo "The Helper message is: " . $error->getOAuthHelperError() . "\n";
         //echo "The Response message is: " . $error->getResponseBody() . "\n";
         echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Failed to create invoice on QuickBooks: " . $error->getResponseBody() );
     }
     else {
         // Store the Invoice ID in the DB Table
         OrderMeta::setOrderMetaValue( $order->getOrderID(), OrderMeta::DB_ORDER_META_KEY_QUICKBOOKS_INVOICE_ID, $resultingObj->Id );
         OrderMeta::setOrderMetaValue( $order->getOrderID(), OrderMeta::DB_ORDER_META_KEY_QUICKBOOKS_INVOICE_NUMBER, $resultingObj->DocNumber );
         echo "Created Id={$resultingObj->Id}. Reconstructed response body:\n\n";
         //$xmlBody = QuickBooksOnline\API\Core\Http\Serialization\XmlObjectSerializer::getPostXmlFromArbitraryEntity($resultingObj, $urlResource);
         //echo $xmlBody . "\n";
        }
    
    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, "Order has been created" );
    
}

function processShopifyCustomerUpdateWebhookResponse( $data ){
    $shopify_data = json_decode( $data, true );
    
    // Check if no customer_id is received in the response
    $shopify_customer_id = data_exists( 'id', $shopify_data )?$shopify_data[ 'id' ]:'';
    if( $shopify_customer_id === '' ){
        // Store this data in error logs in DB
        echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, "Customer ID is missing in the Shopify Response" );
        return; 
    }
    
    // Check if this crm customer id exist in CRM system
    $existing_crm_customer_id = CustomerMeta::getCustomerIdFromCustomerMetaKeyValue(CustomerMeta::DB_CUSTOMER_META_KEY_SHOPIFY_CUSTOMER_ID, $shopify_customer_id );
    if( $existing_crm_customer_id === NULL ){
        // This means that the customers/create call was missed for this customer, so forward this call to the create function
        sleep( 10 );
        processShopifyCustomerCreateWebhookResponse( $data );
        //send_json_mime_type_header();
        //echo createJSONMessage(GENERAL_ERROR_MESSAGE, __FUNCTION__, "This is not an update customer, this is a new customer. Forwarding the data to create method" );
        return;
    }
    
    // Check if it is the repeat data received for a customer that already exist
    //$shopify_customer_email                     = data_exists( 'email', $shopify_data )?$shopify_data[ 'email' ]:'';
    //$shopify_customer_created_at                = data_exists( 'created_at', $shopify_data )?$shopify_data[ 'created_at' ]:'';
    $shopify_customer_updated_at                = data_exists( 'updated_at', $shopify_data )?$shopify_data[ 'updated_at' ]:'';
    $shopify_customer_first_name                = data_exists( 'first_name', $shopify_data )?$shopify_data[ 'first_name' ]:'';
    $shopify_customer_last_name                 = data_exists( 'last_name', $shopify_data )?$shopify_data[ 'last_name' ]:'';
    $shopify_customer_state                     = data_exists( 'state', $shopify_data )?$shopify_data[ 'state' ]:'';
    $shopify_customer_currency                  = data_exists( 'currency', $shopify_data )?$shopify_data[ 'currency' ]:'';
    
    $shopify_customer_email_marketing_consent   = data_exists( 'email_marketing_consent', $shopify_data )?$shopify_data[ 'email_marketing_consent' ]:'';
    
    // Retrieve User ID for the shopify customer id
    $customer = new Customer();
    $customer->readCustomer( $existing_crm_customer_id );
    $user_id = $customer->getUserID(); 
    
    // Update the first name and last name in the Users Table
    $sql = "UPDATE users SET fname='$shopify_customer_first_name', lname='$shopify_customer_last_name' WHERE user_id='$user_id'";
    updateQuery( $sql );
    
    // Update the Customer Table records
    $customerUpdate = new Customer();
    $customerUpdate->setCustomerID( $existing_crm_customer_id );
    $customerUpdate->setFirstName( $shopify_customer_first_name );
    $customerUpdate->setLastName( $shopify_customer_last_name );
    $customerUpdate->setState( $shopify_customer_state );
    $customerUpdate->setCurrency( $shopify_customer_currency );
    $customerUpdate->setUpdatedAt( $shopify_customer_updated_at );
    $customerUpdate->updateCustomer();
    
    // Update Customer Meta
    CustomerMeta::setCustomerMetaValue( $customerUpdate->getCustomerID(), CustomerMeta::DB_CUSTOMER_META_KEY_SHOPIFY_EMAIL_MARKETING_CONSENT, json_encode( $shopify_customer_email_marketing_consent ) );
    
    
    // Update the same customer account in QuickBooks
    // Fetch the existing customer object from QuickBooks
    $quickbooks_customer_id         = CustomerMeta::getCustomerMetaValue( $customer->getCustomerID(), CustomerMeta::DB_CUSTOMER_META_KEY_QUICKBOOKS_CUSTOMER_ID );
    
    $dataService              = getQuickBooksDataService();
    $customerQuickBooksObject = $dataService->Query( "SELECT * FROM Customer where Id='$quickbooks_customer_id'" );
    $customerQuickBooksObject = reset( $customerQuickBooksObject );
    $error = $dataService->getLastError();
    if ( $error ) {
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "No such customer exist on QuickBooks: " . $error->getResponseBody() );
        return;
    }
    
    if( empty( $customerQuickBooksObject ) ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "No such customer exist on QuickBooks" );
        return;
    }
    $syncToken = $customerQuickBooksObject->SyncToken;
    
    $customerUpdateParams = array(
        "sparse" => 'true',
        "SyncToken" => $syncToken
    );
    
    if( $shopify_customer_first_name!=="" ){        
        $customerUpdateParams[ "GivenName" ] = $shopify_customer_first_name;        
    }
    if( $shopify_customer_last_name!=="" ){        
        $customerUpdateParams[ "FamilyName" ] = $shopify_customer_last_name;        
    }
    if( ($shopify_customer_first_name !== "") || ($shopify_customer_last_name !== "") ){
        $customerUpdateParams[ "DisplayName" ] = "$shopify_customer_first_name $shopify_customer_last_name " . $customer->getUserID();
    }
    
    $customerUpdateParams[ "CurrencyRef" ] = array(
        "Name" => $customer->getCurrency(),
        "value" => $customer->getCurrency()
    );
    $customerUpdateParams[ 'sparse' ] = true;
    /*
    $customerQuickBooksObject = QuickBooksOnline\API\Facades\Customer::create(array(
        "Id" => $quickbooks_customer_id,
        "SyncToken" => "1"
    ));
    */
    $quickbooksCustomerUpdateObject   = QuickBooksOnline\API\Facades\Customer::update( $customerQuickBooksObject, $customerUpdateParams );
    $dataService                      = getQuickBooksDataService();
    $resultingCustomerObj             = $dataService->Update( $quickbooksCustomerUpdateObject );
    //print_r( $resultingCustomerObj );
    $error                            = $dataService->getLastError();
    if ( $error ) {
        //echo "The Status code is: " . $error->getHttpStatusCode() . "\n";
        //echo "The Helper message is: " . $error->getOAuthHelperError() . "\n";
        //echo "The Response message is: " . $error->getResponseBody() . "\n";

        // Failed to create the customer on QuickBooks
        echo createJSONMessage(GENERAL_ERROR_MESSAGE, __FUNCTION__, "Failed to update the customer on QuickBooks: " . $error->getResponseBody() );
    }
    
    
    
    
    //send_json_mime_type_header();
    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, "Customer has been updated on CRM" );
    //return;
}

function processShopifyCustomerCreateWebhookResponse( $data ){
    $shopify_data = json_decode( $data, true );
    
    // Check if no customer_id is received in the response
    $shopify_customer_id = data_exists( 'id', $shopify_data )?$shopify_data[ 'id' ]:'';
    if( $shopify_customer_id === '' ){
        // Store this data in error logs in DB
        echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, "Customer ID is missing in the Shopify Response" );
        return; 
    }
    
    // Check if it is the repeat data received for a customer that already exist
    $existing_crm_customer_id = CustomerMeta::getCustomerIdFromCustomerMetaKeyValue( CustomerMeta::DB_CUSTOMER_META_KEY_SHOPIFY_CUSTOMER_ID, $shopify_customer_id );
    if( $existing_crm_customer_id !== NULL ){
        // Do not proceed to create the customer as this customer account already exist
        send_json_mime_type_header();
        echo createJSONMessage(GENERAL_ERROR_MESSAGE, __FUNCTION__, "This customer id already exist" );
        return;
    }
    
    
    $shopify_customer_email                     = data_exists( 'email', $shopify_data )?$shopify_data[ 'email' ]:'';
    $shopify_customer_created_at                = data_exists( 'created_at', $shopify_data )?$shopify_data[ 'created_at' ]:'';
    $shopify_customer_updated_at                = data_exists( 'updated_at', $shopify_data )?$shopify_data[ 'updated_at' ]:'';
    $shopify_customer_first_name                = data_exists( 'first_name', $shopify_data )?$shopify_data[ 'first_name' ]:'';
    $shopify_customer_last_name                 = data_exists( 'last_name', $shopify_data )?$shopify_data[ 'last_name' ]:'';
    $shopify_customer_state                     = data_exists( 'state', $shopify_data )?$shopify_data[ 'state' ]:'';
    $shopify_customer_currency                  = data_exists( 'currency', $shopify_data )?$shopify_data[ 'currency' ]:'';
    
    $shopify_customer_email_marketing_consent   = data_exists( 'email_marketing_consent', $shopify_data )?$shopify_data[ 'email_marketing_consent' ]:'';
    
    if( $shopify_customer_first_name == "" ){
        $shopify_customer_first_name = 'APPSTV';
    }
    if( $shopify_customer_last_name == "" ){
        $shopify_customer_last_name = 'Customer';
    }
    
    // Create User in the users table
    // Check if the customer email is already associated with a user in the users table
    $user_id = '';
    $sql = "SELECT user_id, email FROM users WHERE email='{$shopify_customer_email}'";
    $result_set = selectQuery( $sql );
    if( mysqli_num_rows( $result_set ) > 0 ){
        $val = mysqli_fetch_object( $result_set );
        $user_id = $val->user_id;
    }
    else{
        // Check if the generated user_id is available        
        do{
            $user_id = createRandomUserID();
            $sql = "SELECT user_id FROM users WHERE user_id = '$user_id'";
            $result_set = selectQuery( $sql );        
            if ( mysqli_num_rows( $result_set ) === 0 ) {
                break;
            }
        }while( true );
        
        // Generate public-private key pair for this user
        $keys           = generateAsymmetricKeyPair();
        $public_key     = $keys[ 'publicKey' ];
        $private_key    = $keys[ 'privateKey' ];
        
        $password       = getRandomString( 15 );
        $password_hash  = hashPassword( $password );
        $currentMillis  = currentTimeMilliseconds();
        
        $role_id        = 8;            // Role ID for Shopify Store Customer

        $sql = "INSERT into users( `user_id`, `password`, `email`, `fname`, `lname`, `nickname`, `public_key`, `private_key`, `role_id`, `registered_on` ) "
                . "VALUES( '$user_id', '$password_hash', '{$shopify_customer_email}', '{$shopify_customer_first_name}', '{$shopify_customer_last_name}', '', '$public_key', '$private_key', $role_id, '$currentMillis' )";
        $rows = insertQuery( $sql );
        if ( $rows == 0 ) {
            // Store this data in error logs in DB
            send_json_mime_type_header();
            echo createJSONMessage(GENERAL_ERROR_MESSAGE, __FUNCTION__, "This user id cannot be created" );
            return;
        }
        
        // Store User Meta information
        setUserMetaValue( $user_id, _NEW_ACCOUNT_ACTIVATION_STATUS, _NEW_ACCOUNT_ACTIVATION_STATUS_ACTIVE );
        setUserMetaValue( $user_id, _NEW_ACCOUNT_ACTIVATION_TIMESTAMP, $currentMillis );
        setUserMetaValue( $user_id, _NEW_ACCOUNT_ACTIVATION_METHOD, _NEW_ACCOUNT_ACTIVATION_METHOD_SHOPIFY_VERIFIED );
    
        // Create Customer (This is done before email sending because it takes a while to send email, and customer/update webhook is called in split second, causes 2nd user account generation for the same user)
        $customer = new Customer();
        $customer->setCustomerID( Customer::generateCustomerID() );
        $customer->setCustomerSource( 's7YtgHu79D_so' );             // This Source ID is for the Shopify Store
        $customer->setUserID( $user_id );                                 
        $customer->setFirstName( $shopify_customer_first_name );
        $customer->setLastName( $shopify_customer_last_name );
        $customer->setState( $shopify_customer_state );
        $customer->setCurrency( $shopify_customer_currency );
        $customer->setPrimaryEmail( $shopify_customer_email );
        $customer->setCreatedAt( $shopify_customer_created_at );
        $customer->setUpdatedAt( $shopify_customer_updated_at );
        $customer->createCustomer();
        
        // Customer meta
        CustomerMeta::setCustomerMetaValue( $customer->getCustomerID(), CustomerMeta::DB_CUSTOMER_META_KEY_SHOPIFY_CUSTOMER_ID, $shopify_customer_id );
        CustomerMeta::setCustomerMetaValue( $customer->getCustomerID(), CustomerMeta::DB_CUSTOMER_META_KEY_SHOPIFY_EMAIL_MARKETING_CONSENT, json_encode( $shopify_customer_email_marketing_consent ) );

        // Create the same customer account in QuickBooks
        $quickbooksCustomerObject = QuickBooksOnline\API\Facades\Customer::create([
            "Notes" =>  "Customer registered from Shopify",
            "GivenName"=>  $shopify_customer_first_name,
            "FamilyName"=>  $shopify_customer_last_name,
            "DisplayName"=>  "$shopify_customer_first_name $shopify_customer_last_name $user_id",
            "PrimaryEmailAddr"=>  [
                "Address" => $shopify_customer_email
            ],
            "CurrencyRef"=>  [
                "Name" => $shopify_customer_currency,
                "value" => $shopify_customer_currency
            ]
        ]);
        $dataService            = getQuickBooksDataService();
        $resultingCustomerObj   = $dataService->Add( $quickbooksCustomerObject );
        //print_r( $resultingCustomerObj );
        $error                  = $dataService->getLastError();
        if ( $error ) {
            //echo "The Status code is: " . $error->getHttpStatusCode() . "\n";
            //echo "The Helper message is: " . $error->getOAuthHelperError() . "\n";
            //echo "The Response message is: " . $error->getResponseBody() . "\n";
            
            // Failed to create the customer on QuickBooks
            echo createJSONMessage(GENERAL_ERROR_MESSAGE, __FUNCTION__, "Failed to create the customer on QuickBooks: " . $error->getResponseBody() );
        }
        else{
            $quickbooks_customer_id = $resultingCustomerObj->Id;
            
            // Customer meta
            CustomerMeta::setCustomerMetaValue( $customer->getCustomerID(), CustomerMeta::DB_CUSTOMER_META_KEY_QUICKBOOKS_CUSTOMER_ID, $quickbooks_customer_id );
        
        }
        
        $mail = sendMailObject();
        $mail->isHTML( true );
        $mail->setFrom( EMAIL_NOREPLY, 'myappstv.com' );
        $mail->AddAddress( $shopify_customer_email );
        $mail->Subject = 'Welcome aboard on MyAPPSTV !';

        $message = file_get_contents( "templates/email/myappstv_user_account_credentials.php" );
        $message = str_replace( "{{url_portal}}", WEBSITE_PROTOCOL . "://" . WEBSITE_DOMAIN_NAME, $message );
        $message = str_replace( "{{user_id}}", $user_id, $message );
        $message = str_replace( "{{password}}", $password, $message );

        $mail->Body = $message;
        
        $email_customer_with_new_account_credentials = intval( getConfigurationValue( EMAIL_CUSTOMER_WITH_NEW_ACCOUNT_CREDENTIALS, "0" ) );
        /*
        $path = BASE_PATH . "/plugins/appstv_crm_apps/data/logs/test.logs";
        $file = fopen( $path, "w+" );
        fwrite( $file, "email_customer_with_new_account_credentials: $email_customer_with_new_account_credentials \n\n" );
        */
        if( $email_customer_with_new_account_credentials === 1 ){
            //fwrite( $file, "email_customer_with_new_account_credentials: $email_customer_with_new_account_credentials \n\n" );
            //fwrite( $file, "inside \n\n" );
            if ( !$mail->Send() ) {
                //fwrite( $file, "mail not sent \n\n" );
                // Store this data in error logs in DB
                //send_json_mime_type_header();
                echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Failed to send email" );
                return;
            }
        }
        else{
            //fwrite( $file, "New account customer email sending has been disabled in the configurations \n\n" );
            echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "New account customer email sending has been disabled in the configurations" );
        }
        //fclose( $file );
    }
    
    
    
    
    //send_json_mime_type_header();
    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, "Customer has been created on CRM" );
    //return;
    
}

/**
 * Retrieve all the webhooks that we have created with the shopify store
 */
function get_all_shopify_webhooks(){
    
}

function test_hmac(){
    global $client_secret;
    
     
    //print_r( $_REQUEST );
    //print_r( $_SERVER );
    //print_r( $_COOKIE );
    //print_r( $_ENV );
    //print_r( $_FILES );
    //print_r( $_POST );
    //print_r( $_GET );
    //print_r( $_SESSION );
    
    $hmac_header = $_REQUEST[ 'hmac_header' ];
    
    //$data               = file_get_contents_utf8( 'php://input' );
    $data = '';
    $fh   = @fopen('php://input', 'r');
    if ( $fh ){
        while ( !feof( $fh ) ){
            $s = fread( $fh, 1024 );
            if ( is_string( $s ) ){
              $data .= $s;
            }
        }
        fclose( $fh );
    }
    //$data = http_get_request_body();
    $calculated_hmac    = base64_encode( hash_hmac( 'sha256', $data, $client_secret, true ) );
    
    //echo var_dump( hash_equals( $hmac_header, $calculated_hmac ) );
    
    echo $calculated_hmac;
}

function file_get_contents_utf8($fn) {
     $content = file_get_contents($fn);
      return mb_convert_encoding($content, 'UTF-8',
          mb_detect_encoding($content, 'UTF-8, ISO-8859-1', true));
}
