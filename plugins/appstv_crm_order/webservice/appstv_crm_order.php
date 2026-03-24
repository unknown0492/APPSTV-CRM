<?php

//include PLU_PATH . FILE_SEPARATOR . "appstv_crm_order/includes" . FILE_SEPARATOR . "order.php";
//include PLU_PATH . FILE_SEPARATOR . "appstv_crm_order/includes" . FILE_SEPARATOR . "delivery.php";
//include PLU_PATH . FILE_SEPARATOR . "cron_management/includes" . FILE_SEPARATOR . "constants.php";

/**
 * Get all the orders from the table `orders`
 */
function get_crm_orders(){
    checkAuthorizationForFunction( __FUNCTION__ );
    
    $responseData = array();
    //send_json_mime_type_header();
    
    // Filter out test orders if this user does not have see_crm_test_orders
    $where_query_filter = '';
    if( !hasAuthorization( 'see_crm_test_orders' ) ){
        $where_query_filter = "AND (test='0') ";
    }
    
    $sql = "SELECT "
            . "id, "
            . "order_id, "
            . "order_source, "
            . "source_name, "
            . "created_at, "
            . "order_status, "
            . "products, "
            . "is_cancelled, "
            . "test, "
            . "total_price "
        . "FROM orders, sources "
            . "WHERE (orders.order_source=sources.source_id) $where_query_filter "
            . "ORDER BY id DESC";
    $result_set = selectQuery( $sql );
    
    // No orders exist yet
    if( mysqli_num_rows( $result_set ) == 0 ){
        $responseData[ 'message' ] = "No orders exist";
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, $responseData );
        return;
    }
    
    // Prepare orders array to send as the response
    $orders = array();
    while( ( $val = mysqli_fetch_assoc( $result_set ) ) !== NULL ){
        $val[ 'products' ] = base64_encode( $val[ 'products' ] );
        array_push( $orders, $val );
    }
    
    $responseData[ 'message' ] = 'Orders have been retrieved';
    $responseData[ 'data' ] = $orders;
    
    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $responseData );
    
}


/**
 * Retrieve all the data for the provided order_id
 */
function get_crm_order_information(){
    checkAuthorizationForFunction( __FUNCTION__ );
    
    $responseData = array();
    //send_json_mime_type_header();
    
    $order_id = request( 'order_id' );
    
    validateEmptyDigitString( $order_id, __FUNCTION__, "Order ID is required" );
    validate( $order_id, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), "Order ID is invalid" );
    
    $e_order_id = escape_string( $order_id );
    
    // Check if the order_id exist in the system
    $sql        = "SELECT *, source_name FROM orders, sources WHERE (order_id='$e_order_id') AND (order_source=source_id)";
    $result_set = selectQuery( $sql );
    if( mysqli_num_rows( $result_set ) === 0 ){
        $responseData[ 'message' ] = "No information exist for the given order id";
        echo createJSONMessage(GENERAL_ERROR_MESSAGE, __FUNCTION__, $responseData );
        return;
    }
    
    $order_information = mysqli_fetch_assoc( $result_set );
    $order_meta = array();
    
    // Retrieve the order_meta for the given order_id
    $sql = "SELECT order_meta_key, order_meta_value FROM order_meta WHERE order_id='$e_order_id'";
    $result_set = selectQuery( $sql );
    if( mysqli_num_rows( $result_set ) > 0 ){
        while( ( $val = mysqli_fetch_assoc( $result_set ) ) !== NULL ){
            $order_meta[] = $val;
        }
    }
    
    // Retrieve the order_address for the given order_id
    $billing_address    = array();
    $shipping_address   = array();
    $sql = "SELECT * FROM order_address WHERE order_id='$e_order_id'";
    $result_set = selectQuery( $sql );    
    if( mysqli_num_rows( $result_set ) > 0 ){
        while( ( $val = mysqli_fetch_assoc( $result_set ) ) !== NULL ){
            if( $val[ 'address_type' ] == OrderAddress::DB_FIELD_ORDER_ADDRESS_ADDRESS_TYPE_BILLING ){
                $billing_address = $val;
            }
            if( $val[ 'address_type' ] == OrderAddress::DB_FIELD_ORDER_ADDRESS_ADDRESS_TYPE_SHIPPING ){
                $shipping_address = $val;
            }
        }
    }
    
    //$order_information[ 'products' ] = $order_information[ 'products' ];
    $order_information[ 'order_meta' ] = $order_meta;
    $order_information[ OrderAddress::DB_FIELD_ORDER_ADDRESS_ADDRESS_TYPE_BILLING ] = $billing_address;
    $order_information[ OrderAddress::DB_FIELD_ORDER_ADDRESS_ADDRESS_TYPE_SHIPPING ] = $shipping_address;
    $responseData[ 'message' ] = 'Order information has been retrieved';
    $responseData[ 'data' ] = $order_information;
    
    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $responseData );
}


function get_prepare_order_information(){
    checkAuthorizationForFunction( __FUNCTION__ );
    
    $responseData = array();
    //send_json_mime_type_header();
    
    $order_id = request( 'order_id' );
    
    validateEmptyDigitString( $order_id, __FUNCTION__, "Order ID is required" );
    validate( $order_id, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), "Order ID is invalid" );
    
    $e_order_id = escape_string( $order_id );
    
    // Check if the order_id exist in the system
    $sql        = "SELECT order_id, created_at, products, order_status, source_name FROM orders, sources WHERE (order_id='$e_order_id') AND (order_source=source_id)";
    $result_set = selectQuery( $sql );
    if( mysqli_num_rows( $result_set ) === 0 ){
        $responseData[ 'message' ] = "No information exist for the given order id";
        $responseData[ 'modal_direction' ]  = "close_modal";
        
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, $responseData );
        return;
    }
    
    $order_information  = mysqli_fetch_assoc( $result_set );
    
    if( ($order_information[ 'order_status' ] === Order::ORDER_STATUS_DELIVERED) || 
           ($order_information[ 'order_status' ] === Order::ORDER_STATUS_CANCELLED) || 
            ($order_information[ 'order_status' ] === Order::ORDER_STATUS_PARTIALLY_DELIVERED) ){
        
        $responseData[ 'message' ]          = "You cannot prepare an order that has already been marked as delivered / cancelled / partially_delivered";
        $responseData[ 'modal_direction' ]  = "close_modal";
        
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, $responseData );
        return;
    } 
    
    // Show only products having SKU, because no sku products are services
    /*
    $all_products           = json_decode( $order_information[ 'products' ], true );
    $products               = array();
    $product_with_skus      = array();
    foreach ( $all_products as $product ) {
        
        if( $product[ 'sku' ] == '' ){
            continue;
        }
        
        $product_with_skus[ $product[ 'sku' ] ] = intval( $product[ 'quantity' ] );
        
        $tempProduct = array(
            "name" => $product[ 'name' ],
            "sku" => $product[ 'sku' ],
            "quantity" => $product[ 'quantity' ],
        );
        
        array_push( $products, $tempProduct );
    }
    
    $filtered_products = array();
    foreach( $product_with_skus as $sku => $quantity ){
        $filtered_products
    }
    */
    
    $products       = $order_information[ 'products' ];
    $products_arr   = json_decode( $products, true );
    
    // print_r( $products_arr );
    
    $all_products           = array();
    $all_sku_quantities     = array();

    foreach ( $products_arr as $product ) {
        //print_r( $product );
        $product_name   = $product[ 'name' ];
        $sku            = trim( $product[ 'sku' ] );
        $quantity       = $product[ 'quantity' ];

        if( $sku == '' ){
            //echo var_dump( $sku );
            continue;
        }
            //echo var_dump( $sku );

        $temp_product_array = array(
            'name' => $product_name,
            'sku' => $sku,
            'quantity' => $quantity
        );

        //array_push( $all_products, $temp_product_array )
        if( isset( $all_sku_quantities[ $sku ] ) ){
            $all_sku_quantities[ $sku ] = intval( $all_sku_quantities[ $sku ] ) + intval( $quantity );
        }
        else{
            $all_sku_quantities[ $sku ] = intval( $quantity );
        }

        $all_products[ $sku ] = array( "name" => $product_name );
    }

    $products = array();
    foreach( $all_products as $sku => $product ){
        $all_products[ $sku ][ 'quantity' ] = $all_sku_quantities[ $sku ];
        $all_products[ $sku ][ 'sku' ] = $sku;
        $products[] = $all_products[ $sku ];
    }
    
    
    $order_information[ 'products' ] = $products;
    
    $delivery_deadline  = 3;                                    // 3 working days
    
    // Retrieve the shipping address for this order
    $sql        = "SELECT * FROM order_address WHERE (order_id='$e_order_id') AND (address_type='".OrderAddress::DB_FIELD_ORDER_ADDRESS_ADDRESS_TYPE_SHIPPING."')";
    $result_set = selectQuery( $sql );
    if( mysqli_num_rows( $result_set ) === 0 ){
        $responseData[ 'message' ] = "No shipping address exist for this order";
        $responseData[ 'modal_direction' ]  = "do_not_close_modal";
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, $responseData );
        return;
    }
    
    $order_address  = mysqli_fetch_assoc( $result_set );
    
    // Check from prepare_delivery_order table if this order has already been prepared
    $sql            = "SELECT order_id, remarks FROM prepare_delivery_order WHERE order_id='$e_order_id'";
    $result_set     = selectQuery( $sql );
    $order_prepared         = "0";
    $order_prepared_remarks = "";
    if( mysqli_num_rows( $result_set ) > 0 ){
        $val = mysqli_fetch_assoc( $result_set );
        $order_prepared = "1";
        $order_prepared_remarks = $val[ 'remarks' ];
    }
    
    $order_information[ 'order_prepared' ] = $order_prepared;
    $order_information[ 'order_prepared_remarks' ] = $order_prepared_remarks;
    $order_information[ 'delivery_deadline' ] = $delivery_deadline;
    $order_information[ OrderAddress::DB_FIELD_ORDER_ADDRESS_ADDRESS_TYPE_SHIPPING ] = $order_address;
    
    $responseData[ 'message' ] = 'Order information has been retrieved';
    $responseData[ 'modal_direction' ]  = "do_not_close_modal";
    $responseData[ 'data' ] = $order_information;
    
    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $responseData );
}

function create_prepare_order(){
    global $globalAccessToken;
    
    checkAuthorizationForFunction( __FUNCTION__ );
    
    $responseData = array();
    //send_json_mime_type_header();
    
    $order_id   = request( 'order_id' );
    $remarks    = request( 'remarks' );
    
    validateEmptyDigitString( $order_id, __FUNCTION__, "Order ID is required" );
    //validateEmptyString( $remarks, __FUNCTION__, "Remarks is required" );
    validate( $order_id, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), "Order ID is invalid" );
    validate( $remarks, __FUNCTION__, getValidationRegex( "VLDTN_PREPARE_ORDER_REMARKS" ), "Some special characters are not allowed for the remarks" );
    
    $e_order_id     = escape_string( $order_id );
    $e_remarks      = escape_string( $remarks );
    
    // Check if the order_id exist in the system
    $sql        = "SELECT order_id, order_status, is_cancelled, financial_status, source_name FROM orders, sources WHERE (order_id='$e_order_id') AND (order_source=source_id)";
    $result_set = selectQuery( $sql );
    if( mysqli_num_rows( $result_set ) === 0 ){
        $responseData[ 'message' ] = "No information exist for the given order id";
        $responseData[ 'modal_direction' ] = "close_modal";
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, $responseData );
        return;
    }
    $order_information = mysqli_fetch_assoc( $result_set );
    
    $payload = getJWTPayload( $globalAccessToken );
    $user_id = $payload[ 'user_id' ];
    
    // Check if the user_id exist in the system
    $e_user_id = escape_string( $user_id );
    $sql = "SELECT user_id FROM users WHERE user_id='$e_user_id'";
    $result_set = selectQuery( $sql );
    if( mysqli_num_rows( $result_set ) === 0 ){
        $responseData[ 'message' ] = "Bad User";
        $responseData[ 'modal_direction' ] = "close_modal";
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, $responseData );
        return;
    }
    
    // Check if the order status is already set as prepared
    if( ($order_information[ 'order_status' ] === "prepared") ){
        $responseData[ 'message' ] = "This order has already been set as Prepared";
        $responseData[ 'modal_direction' ] = "close_modal";
        echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $responseData );
        return;
    }
    
    // Check if the order status is delivered/cancel/dispatched
    if( ($order_information[ 'order_status' ] === "delivered") || 
            ($order_information[ 'order_status' ] === "cancelled") || 
            ($order_information[ 'order_status' ] === "dispatched") ){
        $responseData[ 'message' ] = "You cannot prepare delivery for a " . strtoupper( $order_information[ 'order_status' ] ) . " order !";
        $responseData[ 'modal_direction' ] = "close_modal";
        echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $responseData );
        return;
    }
    
    // Check if the financial status is not paid
    if( ($order_information[ 'financial_status' ] !== "paid")  ){
        $responseData[ 'message' ] = "You cannot prepare delivery for an an UNPAID order !";
        $responseData[ 'modal_direction' ] = "close_modal";
        echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $responseData );
        return;
    }
    
    // Check if the order is_cancelled
    if( ($order_information[ 'is_cancelled' ] === "1")  ){
        $responseData[ 'message' ] = "You cannot prepare delivery for a cancelled order !";
        $responseData[ 'modal_direction' ] = "close_modal";
        echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $responseData );
        return;
    }
    
    // Creat an entry for prepare_delivery
    $created_at = create_iso_8601_datetime();
    $sql = "INSERT INTO prepare_delivery_order( `order_id`, `created_at`, `user_id`, `remarks` ) VALUES("
            . "'$e_order_id', "
            . "'$created_at', "
            . "'$user_id', "
            . "'$e_remarks' )";
    $rows = insertQuery( $sql );
    if( $rows == 0 ){
        $responseData[ 'message' ] = "Something went wrong, please try again. If the problem persist, report to the administrator immediately !";
        $responseData[ 'modal_direction' ] = "do_not_close_modal";
        echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $responseData );
        return;
    }
    
    // Update the orders table order_status
    $sql = "UPDATE orders SET order_status='prepared' WHERE order_id='$e_order_id'";
    updateQuery( $sql );
    
    // Create an entry into the Order_status_history table
    OrderStatusHistory::addToOrderStatusHistory( $e_order_id, $e_user_id, Order::ORDER_STATUS_PREPARED );
    
    
    // At this point, mark the order as fulfilled on Shopify (Note that we are marking the entire order for fulfillment and not for specific items at this point)
    if( strtolower( $order_information[ 'source_name' ] ) === "shopify" ){
        // 1. Retrieve the fulfillment_order_id from Shopify using API (There might be multiple fulfillment order ids for a single order, so retrieve all those ids)
        // 2. Use the fulfillment_order_id from step-1 
        global $store_url, $api_version;
        
        $shopify_order_id = OrderMeta::getOrderMetaValue( $e_order_id, OrderMeta::DB_ORDER_META_KEY_SHOPIFY_ORDER_ID );
        
        $paramsGetFulfillmentOrderIDs = array(
            "api_url" => "https://$store_url/admin/api/$api_version/orders/$shopify_order_id/fulfillment_orders.json",
            "custom_request" => "GET"
        );
        $fulfillment_orders_json = shopifyPerformCurlRequest( $paramsGetFulfillmentOrderIDs );
        //echo $fulfillment_orders_json;
        $fulfillment_orders      = json_decode( $fulfillment_orders_json, true );
        if( $fulfillment_orders === NULL ){
            // Fulfillment Orders does not exist for this order
        }
        else{
            //$fulfillment_orders = $fulfillment_orders[ 'fulfillment_orders' ];
            if( count( $fulfillment_orders ) === 0 ){
                // Fulfillment Orders does not exist for this order
            }
            else{
                $line_items_by_fulfillment_order = array();
                $fulfillment_orders = $fulfillment_orders[ 'fulfillment_orders' ];
                //print_r( $fulfillment_orders );
                foreach ( $fulfillment_orders as $fulfillment_order ) {
                    $fulfillment_order_id = $fulfillment_order[ 'id' ];
                    
                    $tempArray = array(
                        "fulfillment_order_id" => $fulfillment_order_id
                    );
                    
                    array_push( $line_items_by_fulfillment_order, $tempArray );
                }
                
                $fulfillment = array(
                    "line_items_by_fulfillment_order" => $line_items_by_fulfillment_order,
                    "notify_customer" => true
                );
                
                $fulfillments = array(
                    "fulfillment" => $fulfillment
                );
                
                $fulfillments_json = json_encode( $fulfillments );
                //echo $fulfillments_json;
                $paramsFulfillOrder = array(
                    "api_url" => "https://$store_url/admin/api/$api_version/fulfillments.json",
                    "method" => "POST",
                    "custom_request" => "POST",
                    "url_params" => $fulfillments_json
                );
                shopifyPerformCurlRequest( $paramsFulfillOrder );
                
            }
        }
        
    }
    
    
    $order_information[ 'order_status' ]    = Order::ORDER_STATUS_PREPARED;
    $responseData[ 'message' ]              = "The order has been prepared for delivery. Make sure to load the items/products into the van !";
    $responseData[ 'modal_direction' ]      = "close_modal";
    $responseData[ 'data' ]                 = $order_information;
    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $responseData );
    return;
}

/**
 * This function is used to unprepare the prepared order
 */
function update_prepare_order(){
    require_once PLU_PATH . FILE_SEPARATOR . PLUGIN_NAME_CRON_MANAGEMENT . FILE_SEPARATOR . "includes" . FILE_SEPARATOR . "cron.php";
    
    global $globalAccessToken;
    
    checkAuthorizationForFunction( __FUNCTION__ );
    
    $responseData = array();
    //send_json_mime_type_header();
    
    $order_id   = request( 'order_id' );
    
    validateEmptyDigitString( $order_id, __FUNCTION__, "Order ID is required" );
    validate( $order_id, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), "Order ID is invalid" );
    
    $e_order_id     = escape_string( $order_id );
    
    
    /**
     * - Check if the order_id is valid
     * - The order_status should be prepared
     * - The order_id should be present inside the prepare_delivery_order Table
     * - Update the order_status to unprepared
     * - Add to order_status_history
     * - Delete order_id from prepare_delivery_order Table
     * 
     */
    
    // Check if the order_id exist in the system
    $sql        = "SELECT order_id, order_status, is_cancelled, financial_status FROM orders WHERE order_id='$e_order_id'";
    $result_set = selectQuery( $sql );
    if( mysqli_num_rows( $result_set ) === 0 ){
        $responseData[ 'message' ] = "No information exist for the given order id";
        $responseData[ 'modal_direction' ] = "close_modal";
        echo createJSONMessage(GENERAL_ERROR_MESSAGE, __FUNCTION__, $responseData );
        return;
    }
    $order_information = mysqli_fetch_assoc( $result_set );
    
    
    // The order_status should be prepared
    if( $order_information[ 'order_status' ] !== Order::ORDER_STATUS_PREPARED ){
        $responseData[ 'message' ] = "You cannot Unprepare an order that is not yet prepared for delivery";
        $responseData[ 'modal_direction' ] = "do_not_close_modal";
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, $responseData );
        return;
    }
    
    $payload = getJWTPayload( $globalAccessToken );
    $user_id = $payload[ 'user_id' ];
    
    $e_user_id = escape_string( $user_id );
    
    // The user can only unprepare the order that was prepared by himself
    $sql        = "SELECT * FROM prepare_delivery_order WHERE (order_id='$e_order_id') AND (user_id='$e_user_id')";
    $result_set = selectQuery( $sql );
    if( mysqli_num_rows( $result_set ) === 0 ){
        $responseData[ 'message' ] = "You cannot Unprepare an order that has not been prepared by you !";
        $responseData[ 'modal_direction' ] = "do_not_close_modal";
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, $responseData );
        return;
    }
    
    // Delete order_id from prepare_delivery_order Table
    $sql = "DELETE FROM prepare_delivery_order WHERE order_id='$e_order_id'";
    $rows = deleteQuery( $sql );
    if( $rows === 0 ){
        $responseData[ 'message' ] = "Failed to unprepare the order. Please report to the administrator";
        $responseData[ 'modal_direction' ] = "do_not_close_modal";
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, $responseData );
        return;
    }
    
    // Update the order_status to unprepared
    $sql = "UPDATE orders SET order_status='". Order::ORDER_STATUS_UNPREPARED ."' WHERE order_id='$e_order_id'";
    updateQuery( $sql );
    
    // Add to order_status_history
    OrderStatusHistory::addToOrderStatusHistory( $e_order_id, $e_user_id, Order::ORDER_STATUS_UNPREPARED );
    
    $order_information[ 'order_status' ]    = Order::ORDER_STATUS_UNPREPARED;
    $responseData[ 'order_prepared' ]       = "0";
    $responseData[ 'message' ]              = "The order has been marked as Unprepared. Please do not carry this order in the Van !";
    $responseData[ 'modal_direction' ]      = "close_modal";
    $responseData[ 'data' ]                 = $order_information;
    
    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $responseData );
    
    return;
}

/**
 * This function is called by the CRON job every 12.10am and deletes all those order entries from prepare_delivery_order  table that were prepared but undelivered
 */
function delete_prepared_but_undelivered_orders(){
    require_once PLU_PATH . FILE_SEPARATOR . PLUGIN_NAME_CRON_MANAGEMENT . FILE_SEPARATOR . "includes" . FILE_SEPARATOR . "cron.php";
    
    display_php_errors();
    
    $app_id      = request( 'app_id' );
    
    validateEmptyString( $app_id, __FUNCTION__, "App ID is required" );
    
    $e_app_id        = escape_string( $app_id );
    
    $executed_at     = create_iso_8601_datetime();
    $cron_params = array(
        "functionality_name" => __FUNCTION__,
        "executed_at" => $executed_at        
    );
    
    $result = "";
    
    // Check if the app_id is valid
    $sql = "SELECT app_id FROM apps WHERE app_id='$e_app_id'";
    $result_set = selectQuery( $sql );
    if( mysqli_num_rows( $result_set ) === 0 ){
        
        $cron_params[ 'status' ] = GENERAL_ERROR_MESSAGE;
        $cron_params[ 'result' ] = "App is invalid: " . $e_app_id;
        
        Cron::log( $cron_params );
        
        //send_json_mime_type_header();
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "App is invalid: " . $e_app_id );
        return;
    }
    
    $sql = "SELECT rt.access_token, rt.is_expired FROM refresh_tokens rt, app_tokens at WHERE (at.app_id='$e_app_id') AND (rt.token_id=at.token_id)";
    $result_set = selectQuery( $sql );
    if( mysqli_num_rows( $result_set ) === 0 ){
        
        $cron_params[ 'status' ] = GENERAL_ERROR_MESSAGE;
        $cron_params[ 'result' ] = "App is invalid: " . $e_app_id;
        
        Cron::log( $cron_params );
        
        //send_json_mime_type_header();
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "App is invalid: " . $e_app_id );
        return;
    }
    
    // Generate current date object and compare with dates retrieved from the prepare_delivery_order table
    $ts_now     = convert_iso_8601_datetime_to_millis( $executed_at );
    $date_now   = date( 'j', $ts_now/1000 );
    
    //echo "Date Now: ". $ts_now ."<br />";
    //echo "Date Now: ". $executed_at ."<br />";
    //echo "Date Now: $date_now <br />";
    
    $sql            = "SELECT order_id, created_at FROM prepare_delivery_order";
    $result_set     = selectQuery( $sql );
    if( mysqli_num_rows( $result_set ) === 0 ){
        
        $cron_params[ 'status' ] = GENERAL_SUCCESS_MESSAGE;
        $cron_params[ 'result' ] = "No orders have been left undelivered after being prepared !";
        
        Cron::log( $cron_params );
        
        //send_json_mime_type_header();
        echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, "No orders have been left undelivered after being prepared !" );
        return;
    }
    
    while( ($val = mysqli_fetch_assoc( $result_set )) !== NULL ){
        // 1. Convert the created_at to milliseconds, then milliseconds to date
        $ts_created_at              = convert_iso_8601_datetime_to_millis( $val[ 'created_at' ] );
        $date_created_at            = date( 'j', $ts_created_at/1000 );
        
        //echo "Date Created At: ". $ts_created_at ."<br />";
        //echo "Date Created At: $date_created_at <br />";
        
        // 2. Check if Now and Created_at dates are different
        if( $date_now == $date_created_at ){
            //do nothing
            continue;
        }
        
        $order_id   = $val[ 'order_id' ];
        $user_id    = "CRON";
        
        // Delete the order_id from the prepare_delivery_order table
        $sql = "DELETE FROM prepare_delivery_order WHERE order_id='$order_id'";
        $rows = deleteQuery( $sql );
        if( $rows > 0 ){
            //$cron_params[ 'status' ] = GENERAL_SUCCESS_MESSAGE;
            //$cron_params[ 'result' ] = "Order ID $order_id has been deleted from prepare_delivery_order table !";
            $result .= "Order ID $order_id has been deleted from prepare_delivery_order table ! \n";

            //Cron::log( $cron_params );
        }
        else{
            //$cron_params[ 'status' ] = GENERAL_ERROR_MESSAGE;
            //$cron_params[ 'result' ] = "Order ID $order_id has not been deleted from prepare_delivery_order table !";
            $result .= "Order ID $order_id has not been deleted from prepare_delivery_order table ! \n";

            //Cron::log( $cron_params );
        }
        
        
        // Insert an entry into order_status_history user_id-> CRON
        OrderStatusHistory::addToOrderStatusHistory( $order_id, $user_id, ORDER::ORDER_STATUS_PREPARED_UNDELIVERED );
        
        // UPDATE orders table set order_status = 'prepared_undelivered' where order_id='' and order_status='prepared' (Only those order whose status was prepared)
        $sql = "UPDATE orders SET order_status='prepared_undelivered' WHERE (order_id='$order_id') AND (order_status='". ORDER::ORDER_STATUS_PREPARED ."')";
        $rows = updateQuery( $sql );
        if( $rows > 0 ){
            //$cron_params[ 'status' ] = GENERAL_SUCCESS_MESSAGE;
            //$cron_params[ 'result' ] = "Order ID $order_id status has been changed to 'prepared_undelivered' in orders table !";
            $result .= "Order ID $order_id status has been changed to 'prepared_undelivered' in orders table ! \n";

            //Cron::log( $cron_params );
        }
        else{
            // $cron_params[ 'status' ] = GENERAL_ERROR_MESSAGE;
            //$cron_params[ 'result' ] = "Order ID $order_id status has not been changed to 'prepared_undelivered' in orders table !";
            $result .= "Order ID $order_id status has not been changed to 'prepared_undelivered' in orders table ! \n";

            // Cron::log( $cron_params );
        }
    }
    
    $result .= "CRON executed successfully !";
    $cron_params[ 'status' ] = GENERAL_SUCCESS_MESSAGE;
    $cron_params[ 'result' ] = $result;
    
    Cron::log( $cron_params );
    
    //send_json_mime_type_header();
    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, "CRON executed successfully !" );
    return;
}


function get_prepared_order_summary(){
    global $globalAccessToken;
    
    checkAuthorizationForFunction( __FUNCTION__ );
    
    $payload = getJWTPayload( $globalAccessToken );
    $user_id = $payload[ 'user_id' ];
    
    $responseData = array();
    
    // Check if the user_id exist in the system
    $e_user_id = escape_string( $user_id );
    $sql = "SELECT user_id FROM users WHERE user_id='$e_user_id'";
    $result_set = selectQuery( $sql );
    if( mysqli_num_rows( $result_set ) === 0 ){
        $responseData[ 'message' ] = "Bad User";
        $responseData[ 'modal_direction' ] = "close_modal";
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, $responseData );
        return;
    }
    
    // Retrieve only those prepare orders that are prepared by this user_id
    $sql = "SELECT order_id FROM prepare_delivery_order WHERE user_id='$e_user_id'";
    $result_set = selectQuery( $sql );
    if( mysqli_num_rows( $result_set ) === 0 ){
        $responseData[ 'message' ] = "No orders have been prepared for delivery by you";
        $responseData[ 'modal_direction' ] = "close_modal";
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, $responseData );
        return;
    }
    
    /**
     * - All Order IDs
     * - Total Number of orders
     * - Total number of products/items (Do not count those items/products without SKU, it means its a service)
     * - Total quantity of each product across all orders
     * 
     */
    $order_ids = array();
    while( ( $val = mysqli_fetch_assoc( $result_set ) ) !== NULL ){
        
        $order_id = $val[ 'order_id' ];
        array_push( $order_ids, $order_id );
        
    }
    
    $total_orders = count( $order_ids );
    
    // Retrieve products column for each order
    $all_products           = array();
    $all_sku_quantities     = array();
    $total_products_count   = 0;
    if( $total_orders > 0 ){
        
        $order_ids_csv = implode( ",", $order_ids );
        $sql = "SELECT products FROM orders WHERE order_id IN ($order_ids_csv)";
        $result_set = selectQuery( $sql );
        
        while( ( $val = mysqli_fetch_assoc( $result_set ) ) !== NULL ){

            $products       = $val[ 'products' ];
            $products_arr   = json_decode( $products, true );
            
            foreach ( $products_arr as $product ) {
                //print_r( $product );
                $product_name   = $product[ 'name' ];
                $sku            = trim( $product[ 'sku' ] );
                $quantity       = $product[ 'quantity' ];
                
                if( $sku == '' ){
                    //echo var_dump( $sku );
                    continue;
                }
                    //echo var_dump( $sku );
                
                $temp_product_array = array(
                    'product_name' => $product_name,
                    'sku' => $sku,
                    'quantity' => $quantity
                );
                
                //array_push( $all_products, $temp_product_array )
                if( isset( $all_sku_quantities[ $sku ] ) ){
                    $all_sku_quantities[ $sku ] = intval( $all_sku_quantities[ $sku ] ) + intval( $quantity );
                }
                else{
                    $all_sku_quantities[ $sku ] = intval( $quantity );
                }
                 
                $all_products[ $sku ] = array( "product_name" => $product_name );
            }
        }
        
        //print_r($all_sku_quantities);
        //print_r($all_products);
        
        $products = array();
        foreach ( $all_products as $sku => $product ) {
            $all_products[ $sku ][ 'quantity' ] = $all_sku_quantities[ $sku ];
            $all_products[ $sku ][ 'sku' ] = $sku;
            $products[] = $all_products[ $sku ];
            $total_products_count += $all_sku_quantities[ $sku ];
        }        
    }
    
    //print_r( $all_products );
    
    $responseData[ 'message' ]                  = "Response has been received";
    $responseData[ 'total_orders_count' ]       = $total_orders;
    $responseData[ 'order_ids' ]                = $order_ids_csv;
    $responseData[ 'total_products_count' ]     = $total_products_count;
    $responseData[ 'products' ]                 = $products;
    $responseData[ 'modal_direction' ]          = "do_not_close_modal";
    
    //sleep( 3 );
    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $responseData );
}

/**
 * This function is called when the Delivery Person clicks on the Confirm Delivery button besides the order after arriving at the customer
 * 
 */
function get_confirm_delivery_information(){
    global $globalAccessToken;
    
    
    
    /**
     * Data to Send in response
     * - Customer Contact (Phone)
     * - Customer Full address with name
     * - Set of all the products including non-sku ones from the Order, add the partial serial number in the response
     * 
     */
    
    
    checkAuthorizationForFunction( __FUNCTION__ );
    
    $responseData = array();
    //send_json_mime_type_header();
    
    $order_id   = request( 'order_id' );
    
    validateEmptyDigitString( $order_id, __FUNCTION__, "Order ID is required" );
    validate( $order_id, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), "Order ID is invalid" );
    
    $e_order_id     = escape_string( $order_id );
    
    
    /**
     * - Check if the order_id is valid
     * - The order_status should not be cancelled/delivered
     * - Retrieve the Customer Contact and Shipping Address
     * - Retrieve the Products from the orders table
     * 
     */
    
    // Check if the order_id exist in the system
    $sql        = "SELECT order_id, order_status, is_cancelled, financial_status, products FROM orders WHERE order_id='$e_order_id'";
    $result_set = selectQuery( $sql );
    if( mysqli_num_rows( $result_set ) === 0 ){
        $responseData[ 'message' ] = "No order information exist for the given order id";
        $responseData[ 'modal_direction' ] = "close_modal";
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, $responseData );
        return;
    }
    $order_information = mysqli_fetch_assoc( $result_set );
    
    
    // The order_status should not be canncelled/delivered
    if( ($order_information[ 'order_status' ] === Order::ORDER_STATUS_CANCELLED) || 
            ($order_information[ 'order_status' ] === Order::ORDER_STATUS_DELIVERED) ){
        $responseData[ 'message' ] = "You cannot deliver this order as it has been marked as Delivered/Cancelled";
        $responseData[ 'modal_direction' ] = "close_modal";
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, $responseData );
        return;
    }
    
    // Retrieve the Customer Contact and Shipping Address
    $sql            = "SELECT * FROM order_address WHERE (order_id='$e_order_id') AND (address_type='". OrderAddress::DB_FIELD_ORDER_ADDRESS_ADDRESS_TYPE_SHIPPING ."')";
    $result_set     = selectQuery( $sql );
    if( mysqli_num_rows( $result_set ) === 0 ){
        $responseData[ 'message' ] = "No customer information exist for the given order id";
        $responseData[ 'modal_direction' ] = "close_modal";
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, $responseData );
        return;
    }
    $order_address = mysqli_fetch_assoc( $result_set );
    
    // Retrieve the Products from the orders table
    $sql            = "SELECT products FROM orders WHERE (order_id='$e_order_id')";
    $result_set     = selectQuery( $sql );
    if( mysqli_num_rows( $result_set ) === 0 ){
        $responseData[ 'message' ] = "No information exist for the given order id";
        $responseData[ 'modal_direction' ] = "close_modal";
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, $responseData );
        return;
    }
    
    // Map the sku of products from orders table to the products sku from products table to retrive the value of has_sn field
    /**
    * - Get all unique SKUs from the orders->products json
    * - Map the SKUs with products->sku table to retrieve has_sn
    * - Create a new array of products with only required fields for response
    * 
    */
    $products       = $order_information[ 'products' ];
    $products_arr   = json_decode( $products, TRUE );
    $unique_skus    = array();
    foreach( $products_arr as $product ){
        if( $product[ 'sku' ] == '' ){
            continue;
        }
        $unique_skus[ $product[ 'sku' ] ] = $product[ 'sku' ]; 
    }
    
    // Get all unique SKUs from the orders->products json
    $unique_skus_str = implode( "','", $unique_skus );
    $unique_skus_str = "'" . $unique_skus_str . "'";
    
    // Map the SKUs with products->sku table to retrieve has_sn
    $products_db    = array();
    $sql            = "SELECT product_id, sku, has_sn FROM products WHERE sku IN ($unique_skus_str)";
    $result_set     = selectQuery( $sql );
    while( ($val = mysqli_fetch_assoc( $result_set ) ) !== NULL ){
        $products_db[ $val[ 'sku' ] ] = $val;
    }
    
    // Create individual products array with both SKU and non-SKU ones
    // Create indivudual array items with 1 quantity each of all the products
    $all_products = array();
    foreach( $products_arr as $product ) {
        if( $product[ 'sku' ] == '' ){
            //if( ($qty = intval( $product[ 'quantity' ] )) > 1 ){
            $qty = intval( $product[ 'quantity' ] );
            for( $i = 1; $i <= $qty ; $i++ ){
                $all_products[ 'without_sku' ][] = array(
                    "name" => $product[ 'name' ],
                    "sku" => $product[ 'sku' ],
                    "has_sn" => 0,
                    "product_id" => "NA",
                );//$products_db[ $product[ 'sku' ] ];
            }
            //}
            //$all_products[ 'without_sku' ][] = $product;
        }
        if( $product[ 'sku' ] != '' ){
            $qty = intval( $product[ 'quantity' ] );
            for( $i = 1; $i <= $qty ; $i++ ){
                $all_products[ 'with_sku' ][] = array(
                    "name" => $product[ 'name' ],
                    "sku" => $product[ 'sku' ],
                    "has_sn" => $products_db[ $product[ 'sku' ] ][ 'has_sn' ],
                    "product_id" => $products_db[ $product[ 'sku' ] ][ 'product_id' ]
                );
            }
            //$all_products[ 'with_sku' ][] = $product;
        }
    }
    
    /*
    $payload = getJWTPayload( $globalAccessToken );
    $user_id = $payload[ 'user_id' ];
    
    $e_user_id = escape_string( $user_id );
    */
    
    //print_r( $all_products );
    $data = array(
        "shipping_address" => $order_address,
        "products" => $all_products
    );
    
    $responseData[ 'message' ] = "Information has been retrieved";
    $responseData[ 'modal_direction' ] = "do_not_close_modal";
    $responseData[ 'data' ] = $data;
    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $responseData );
    return;
}

function update_confirm_delivery(){
    global $globalAccessToken;
    
    //require_once PLU_PATH . FILE_SEPARATOR . PLUGIN_NAME . FILE_SEPARATOR . "includes" . FILE_SEPARATOR . "cron.php";
    //print_r( $_FILES[ 'pis' ][ 'name' ] );
    //print_r( $_FILES );
    //print_r( $_FILES[ 'pics' ] );
    //print_r( $_REQUEST );
    
    /**
     * - Check if the order_id is valid
     * - Check if the order_id is not already delivered/cancelled/partially_delivered
     * - Ensure that all products that has has_sn=1 has a Product Serial Number in the request
     * - Check if the uploaded pictures are jpeg/png only and within the maximum size limit of 15mb per picture
     * - Check if the Serial Number entered exist in the table of serial numbers
     * - Check if the Serial Number has not already been allotted to another customer
     * 
     * - Reduce the picture dimensions to 1920x1080 px using GD Library
     * - Store the images in the path /plugins/appstv_crm_order/data/orders/confirm_delivery/
     * - The names of the pictures to be in the format confirm_delivery_1.jpg ... etc
     * 
     * - Make an entry into the order_status_history table
     * - Make an entry into the deliver_order table
     * - Change the status of the order to delivered
     * - Change the product_sn alloted_to_customer to 1
     * - Add an entry into orders_product_sn table
     * - Remove the entry from prepare_delivery_order table
     * 
     */
    
    checkAuthorizationForFunction( __FUNCTION__ );
    
    $responseData = array();
    $responseMessages = array();
    
    $order_id   = request( 'order_id' );
    
    validateEmptyDigitString( $order_id, __FUNCTION__, "Order ID is required" );
    validate( $order_id, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), "Order ID is invalid" );
    
    // Validate the products json
    $products_json = $_REQUEST[ 'products' ];
    $products      = json_decode( $products_json, TRUE );
    if( $products === NULL ){
        $responseData[ 'message' ] = "The products are missing in the request";
        $responseData[ 'modal_direction' ] = "do_not_close_modal";
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, $responseData );
        return;
    }
    // If the products array do not contain the keys product_id and has_sn, then show error
    foreach ( $products as $product ) {
        if( !isset( $product[ 'product_id' ] ) && !isset( $product[ 'has_sn' ] ) ){
            $responseData[ 'message' ] = "The products are missing necesasry information";
            $responseData[ 'modal_direction' ] = "do_not_close_modal";
            echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, $responseData );
            return;
        }
        if( isset( $product[ 'sn' ] ) && ($product[ 'has_sn' ] !== "0") ){
            $sn = escape_string( $product[ 'sn' ] );
            
            validate( $sn, __FUNCTION__, getValidationRegex( "VLDTN_PRODUCT_SERIAL_NUMBER" ), "Product Serial Number `$sn` is in invalid format" );
        }
    }
    
    // There should be at least one picture selected
    if( !isset( $_FILES[ 'pictures' ] ) ){
        $responseData[ 'message' ] = "Please capture/upload at least one picture of the delivery";
        $responseData[ 'modal_direction' ] = "do_not_close_modal";
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, $responseData );
        return;
    }
    // Loading all selected pictures from $_FILES array to $pictures array
    $pictures = array();
    for( $i = 0 ; $i < count( $_FILES[ 'pictures' ][ 'name' ] ) ; $i++ ){
        $tempArray = array();
        foreach ( $_FILES[ 'pictures' ] as $key => $value ) {
            $tempArray[ $key ] = $value[ $i ];
        }
        array_push( $pictures, $tempArray );
    }
    // Validate the picture format, size and error
    foreach( $pictures as $key => $picture ){
        $position = intval($key+1);
        if( $picture[ 'error' ] > 0 ){            
            $responseData[ 'message' ] = "Picture at `Position $position` contains errors. Please change the picture or remove it !";
            $responseData[ 'modal_direction' ] = "do_not_close_modal";
            echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, $responseData );
            return;
        }
        if( ($picture[ 'type' ] !== "image/jpeg") && ($picture[ 'type' ] !== "image/png") ){
            $responseData[ 'message' ] = "Only JPEG and PNG pictures can be uploaded. Picture at `Position $position` is in invalid format. Please change the picture or remove it !";
            $responseData[ 'modal_direction' ] = "do_not_close_modal";
            echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, $responseData );
            return;
        }
        if( $picture[ 'size' ] > 15000000 ){    // 15 MB
            $size = round( intval($picture[ 'size' ])/1000000, 0 );
            $responseData[ 'message' ] = "The size of each picture should be less than 15 MB. Picture at `Position $position` has size of $size MB. Please change the picture or remove it !";
            $responseData[ 'modal_direction' ] = "do_not_close_modal";
            echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, $responseData );
            return;
        }
    }
    
    
    $e_order_id     = escape_string( $order_id );
    
    // Check if the order_id is valid
    $sql        = "SELECT order_id, order_status, is_cancelled, financial_status, products FROM orders WHERE order_id='$e_order_id'";
    $result_set = selectQuery( $sql );
    if( mysqli_num_rows( $result_set ) === 0 ){
        $responseData[ 'message' ] = "No order information exist for the given order id";
        $responseData[ 'modal_direction' ] = "close_modal";
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, $responseData );
        return;
    }
    $order_information = mysqli_fetch_assoc( $result_set );
    
    
    // Check if the order_id is not already delivered/cancelled/partially_delivered
    if( ($order_information[ 'order_status' ] === Order::ORDER_STATUS_DELIVERED) || 
            ($order_information[ 'order_status' ] === Order::ORDER_STATUS_CANCELLED) || 
            ($order_information[ 'order_status' ] === Order::ORDER_STATUS_PARTIALLY_DELIVERED) ){
        $responseData[ 'message' ] = "You cannot deliver this order as it has been marked as Delivered/Cancelled/Partially Delivered";
        $responseData[ 'modal_direction' ] = "close_modal";
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, $responseData );
        return;
    }
     
    // Check if the Serial Number entered exist in the table of serial numbers
    foreach( $products as $key => $product ) {
        if( isset( $product[ 'sn' ] ) && ( $product[ 'has_sn' ] === "1" ) ){
            $e_sn = escape_string( $product[ 'sn' ] );
            
            //$productObj = new Product();
            //$productObj->readProductUsingSKU( $product[ 'sku' ] );
            
            $productSN = new ProductSN();
            $productSN->setProductID( $product[ 'product_id' ] );
            $productSN->setSerialNumber( $e_sn );
            
            if( !$productSN->exists() ){
                $responseData[ 'message' ] = "The serial number `$e_sn` which you have entered for one of the product does not exist in the system. Please report to the administrator immediately !";
                $responseData[ 'modal_direction' ] = "do_not_close_modal";
                echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, $responseData );
                exit();
            }
            
            // Check if the Serial Number has not already been allotted to another customer
            if( $productSN->isAllottedToCustomer() ){
                $responseData[ 'message' ] = "The serial number `$e_sn` which you have entered for one of the product has already been allotted to another Customer. Please report to the administrator immediately !";
                $responseData[ 'modal_direction' ] = "do_not_close_modal";
                echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, $responseData );
                exit();
            }
            
        }
    }
    
    
    // Create the directories that are necessary to store pictures
    $pictureStorageDirPath      = "appstv_crm_order" . FILE_SEPARATOR . "data" . FILE_SEPARATOR . "orders" . FILE_SEPARATOR . $e_order_id . FILE_SEPARATOR . "confirm_delivery" . FILE_SEPARATOR;
    $pictureStorageDirPathAbs   = PLU_PATH . FILE_SEPARATOR . $pictureStorageDirPath;
    
    if( !file_exists( $pictureStorageDirPathAbs ) ){
        if( !mkdir( $pictureStorageDirPathAbs, 0777, true ) ){
            $responseData[ 'message' ] = "Failed to create directories to store pictures. Please report to administrator immediately !";
            $responseData[ 'modal_direction' ] = "do_not_close_modal";
            echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, $responseData );
            exit();
        }
    }
    //print_r( $pictures );
    //return;
    // Reduce the picture dimensions to 1920x1080 px using GD Library, the dimensions are larger than this size
    $pictureUploadedPaths = array();
    $pictureIndex = 1;
    foreach( $pictures as $key => $picture ){
        // - The picture storage path should be /plugins/appstv_crm_order/data/orders/{order_id}/confirm_delivery/pic_1.jpg ...
        
        $pictureExtension = getFileExtension( $picture[ 'name' ] );
        $pictureName = '';
        $picturePathRel = '';
        $picturePathAbs = '';
        do{            
            $pictureName = "pic_$pictureIndex" . "." . $pictureExtension;
            $picturePathRel = $pictureStorageDirPath . FILE_SEPARATOR . $pictureName;
            $picturePathAbs = $pictureStorageDirPathAbs . FILE_SEPARATOR . $pictureName;
            
            if( !file_exists( $picturePathAbs ) ){
                break;
            }
            else{
                $pictureIndex++;
            }
        }while( true );
        
        $image = new Imagick( $picture[ 'tmp_name' ] );

        // Resize the image to proportionate ratio if they are bigger than 1920 x 1080
        list( $width, $height ) = getimagesize( $picture[ 'tmp_name' ] );
        if( $width > 1920 ){
            $x = 1920/$width;
            $width  = 1920;
            $height = round( $x * $height, 0 );
        }
        else if( $height > 1080 ){
            $y = 1080/$height;
            $width  = round( $y * $width, 0 );
            $height = 1080;
        }
        
        $image->resizeImage( $width, $height, Imagick::FILTER_LANCZOS, 1 );

        // Write the image to disk
        if( $image->writeImage( $picturePathAbs ) === true ){
            // Store the image path in the array, which will later be stored in the DB
            array_push( $pictureUploadedPaths, $picturePathRel );
        }
        
    }
    
    //print_r( $products );
    //return;
    
    
    
  
    // - Change the status of the order to delivered
    $order = new Order();
    $order->setOrderID( $e_order_id );
    //$order_products = $order->getProducts();    
    $order->setOrderStatus( Order::ORDER_STATUS_DELIVERED );
    $order->updateOrder();
    
    $payload = getJWTPayload( $globalAccessToken );
    $user_id = $payload[ 'user_id' ];
    
    $e_user_id = escape_string( $user_id );
    
    // - Make an entry into the order_status_history table
    $orderStatusHistory = new OrderStatusHistory();
    $orderStatusHistory->addToOrderStatusHistory( $e_order_id, $e_user_id, Order::ORDER_STATUS_DELIVERED );
    
    // - Make an entry into the deliver_order table
    $productsDelivered       = escape_string( json_encode( $products ) );
    $productsNotDelivered    = escape_string( json_encode( array() ) );
    $picturesJSON            = escape_string( json_encode( $pictureUploadedPaths ) );
    
    $deliverOrder = new DeliverOrder();
    $deliverOrder->setOrderID( $e_order_id );
    $deliveredAt = create_iso_8601_datetime();
    $deliverOrder->setDeliveredAt( $deliveredAt );
    $deliverOrder->setRemarks( '' );
    $deliverOrder->setStatus( Order::ORDER_STATUS_DELIVERED );
    $deliverOrder->setUserID( $e_user_id );
    $deliverOrder->setProductsDelivered( $productsDelivered );
    $deliverOrder->setProductsNotDelivered( $productsNotDelivered );
    $deliverOrder->setPictures( $picturesJSON );
    $deliverOrder->deliverOrder();
    
    // Remove the entry from prepare_delivery_order table
    $pdo = new PrepareDeliveryOrder();
    $pdo->setOrderID( $e_order_id );
    $pdo->deletePrepareDeliveryOrder( $e_order_id );
    
    // - Change the product_sn alloted_to_customer to 1   
    $allSerialNumbersForInvoice = array();
    foreach( $products as $key => $product ) {
        if( isset( $product[ 'sn' ] ) && ( $product[ 'has_sn' ] === "1" ) ){
            $e_sn = escape_string( $product[ 'sn' ] );
            
            $productSN = new ProductSN();
            $productSN->setProductID( $product[ 'product_id' ] );
            $productSN->setSerialNumber( $e_sn );
            $productSN->readProductSerialNumber();
            $productSN->markSerialNumberAsAllotted();
            
            // - Add an entry into orders_product_sn table
            $productSN->addToOrderProductSerialNumber( $e_order_id );
            
            // Collect the Serial Number into an array
            array_push( $allSerialNumbersForInvoice, $e_sn );
            
        }
    }
    
    // - Update the Invoice with the Serial Number on the QuickBooks
    // Retrieve the quickbooks_invoice_id from order_meta using order_id
    $invoiceUpdated = true;
    $quickbooks_invoice_id = OrderMeta::getOrderMetaValue( $e_order_id, OrderMeta::DB_ORDER_META_KEY_QUICKBOOKS_INVOICE_ID );
    if( $quickbooks_invoice_id === NULL ){
        $responseMessages[]    = "Failed to update the Serial Number on the Invoice as the Invoice does not exist yet for this order. Please generate an invoice manually !";
        $invoiceUpdated = false;
    }
    else{
        // Using the Invoice ID, sparse update the invoice on the QuickBooks, if there are serial numbers in this delivery
        $dataService              = getQuickBooksDataService();
        if( count( $allSerialNumbersForInvoice ) > 0 ){
            $invoiceQuickBooksObject  = $dataService->Query( "SELECT * FROM Invoice where Id='$quickbooks_invoice_id'" );
            $invoiceQuickBooksObject  = reset( $invoiceQuickBooksObject );
            $error = $dataService->getLastError();
            if ( $error ) {
                $responseMessages[] = "No such invoice exist on QuickBooks: " . $error->getResponseBody();
                $invoiceUpdated = false;
                goto exitInv;
            }

            $syncToken = $invoiceQuickBooksObject->SyncToken;

            $invoiceUpdateParams = array(
                "sparse" => 'true',
                "SyncToken" => $syncToken
            );

            // Loop through the Serial Numbers and create a single string
            $sns = '';
            foreach( $allSerialNumbersForInvoice as $sn ) {
                $sns .= "S/N: " . $sn . " \n";
            }
            
            // The QuickBooks invoice lines updating:
            // We cannot just add a new line to existing lines, we need to grab all the existing Lines, and add a line to them, and put it back and send in request
            
            // New Line to be added to existing Line of QB Invoice
            $invoiceNewDescriptionLine = array(
                "Description" => $sns,
                "DetailType" => "DescriptionOnly"
            );
            
            // Fetch all existing Lines
            $existingQBLines    = $invoiceQuickBooksObject->Line;
            
            // Create new line object for QB
            $newQBLine          = new QuickBooksOnline\API\Data\IPPLine( $invoiceNewDescriptionLine );

            // Add the new line object into existing lines array
            array_push( $existingQBLines, $newQBLine );

            // Replace the existing lines in QB Invoice object with the new lines array
            $invoiceQuickBooksObject->Line = $existingQBLines;
            
            $quickbooksInvoiceUpdateObject    = QuickBooksOnline\API\Facades\Invoice::update( $invoiceQuickBooksObject, $invoiceUpdateParams );
            $resultingInvoiceObj              = $dataService->Update( $quickbooksInvoiceUpdateObject );
            //print_r( $resultingCustomerObj );
            $error                            = $dataService->getLastError();
            if ( $error ) {
                //echo "The Status code is: " . $error->getHttpStatusCode() . "\n";
                //echo "The Helper message is: " . $error->getOAuthHelperError() . "\n";
                //echo "The Response message is: " . $error->getResponseBody() . "\n";

                // Failed to update the Invoice on QuickBooks
                $responseMessages[] = "Failed to update the Invoice on QuickBooks: " . $error->getResponseBody();
                $invoiceUpdated = false;
            }
        }
        exitInv:
    }
    
    // Send an email to the Customer with the invoice directly from QuickBooks API
    if( $invoiceUpdated ){
        $dataService = getQuickBooksDataService();
        $invoiceObj = QuickBooksOnline\API\Facades\Invoice::create([
            'Id' => $quickbooks_invoice_id
        ]);
        
        // Create the directories that are necessary to store Invoice
        $invoiceStorageDirPath      = "appstv_crm_order" . FILE_SEPARATOR . "data" . FILE_SEPARATOR . "orders" . FILE_SEPARATOR . $e_order_id . FILE_SEPARATOR . "invoice" . FILE_SEPARATOR;
        $invoiceStorageDirPathAbs   = PLU_PATH . FILE_SEPARATOR . $invoiceStorageDirPath;
        
        if( !file_exists( $invoiceStorageDirPathAbs ) ){
            if( !mkdir( $invoiceStorageDirPathAbs, 0777, true ) ){
                $responseMessages[] = "Failed to create directories to store invoices. Please report to administrator immediately !";
            }
        }
        
        $invoiceFileName        = "Invoice_$e_order_id" . ".pdf";
        $invoiceStoragePathAbs  = $invoiceStorageDirPathAbs . FILE_SEPARATOR . $invoiceFileName;
        
        $downloadedFilePath = $dataService->DownloadPDF( $invoiceObj, $invoiceStorageDirPathAbs );
        $downloadedFileName = basename( $downloadedFilePath );
        
        if( file_exists( $invoiceStoragePathAbs ) ){
            @unlink( $invoiceStoragePathAbs );
        }
        
        rename( $downloadedFilePath, $invoiceStoragePathAbs );
        
        if( file_exists( $downloadedFilePath ) ){
            @unlink( $downloadedFilePath );
        }
        
        
        // Send the Invoice to the Customer Billing Address Email using Scodezy
        $siteConfig = getSiteConfig();
        
        $order = new Order();
        $order->readOrder( $e_order_id );
        
        $mail = sendMailObject();
        $mail->isHTML( true );
        $mail->setFrom( EMAIL_NOREPLY, 'Invoice | ' . $siteConfig->site_name );
        $mail->AddAddress( $order->getContactEmail() );
        $mail->addBCC( 'support@appstv.com.sg' );
        $mail->Subject = "Invoice for your order #$e_order_id with APPSTV";

        $message = file_get_contents( "templates/email/myappstv_automatic_invoice_to_customer.php" );

        $mail->Body = $message;
        $mail->addAttachment( $invoiceStoragePathAbs );

        /*
        // Temporarily Disabling Invoice sending feature to Customer, as requested by Eunice on 24 Nov 2024 on WhatsApp group
        if ( !$mail->Send() ) {
            $responseMessages[] = "Invoice did not sent to the customer on email ! ";

            // Log here about email not sending out
        }
         * 
         */
        
    }
    
    // Send an email to the admin@appstv.com.sg  stating that the order has been delivered, email should contain all the images as attachments, and all the responsemessages[]
    $siteConfig = getSiteConfig();
        
    $order = new Order();
    $order->readOrder( $e_order_id );

    $mail = sendMailObject();
    $mail->isHTML( true );
    $mail->setFrom( EMAIL_NOREPLY, 'Order Delivered | ' . $siteConfig->site_name );
    $mail->AddAddress( 'admin@appstv.com.sg' );
    $mail->addBCC( 'support@appstv.com.sg' );
    $mail->Subject = "Order #$e_order_id has been delivered to the customer";

    $message = file_get_contents( "templates/email/myappstv_order_delivered_to_customer.php" );
    if( count( $responseMessages ) > 0 ){
        $message = str_replace( "{{delivery_messages}}", implode( "<br />", $responseMessages ), $message );
    }
    else{
        $message = str_replace( "{{delivery_messages}}", "None", $message );
    }
    
    $mail->Body = $message;
    foreach( $pictureUploadedPaths as $picture ) {
        $mail->addAttachment( PLU_PATH . FILE_SEPARATOR . $picture );        
    }

    if ( !$mail->Send() ) {
        $responseMessages[] = "Failed to send email to the admin ! ";

        // Log here about email not sending out
    }
    
    $order = new Order();
    $o = $order->readOrder( $e_order_id, true );
    
    $data = array(
        "response_messages" => $responseMessages,
        "order_status" => $order->getOrderStatus()
    );
    
    
    $responseData[ 'message' ] = "The order has been marked as delivered";
    $responseData[ 'modal_direction' ] = "close_modal";
    $responseData[ 'data' ] = $data;
    
    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $responseData );
    
    
}

?>