<?php
/**
 * QuickBooks invoice of a CRM order (APPSTV CRM 1.0.39).
 *
 * Used by the Shopify "orders/create" webhook and by "Create QuickBooks Invoice" on the Orders page. The invoice is
 * built from the order stored in the CRM (customer, addresses, items, discount codes, shipping), the same invoice the
 * webhook made before 1.0.39.
 *
 * Every line item needs a QuickBooks item. The CRM product of the item's SKU holds its QuickBooks item ID
 * (product_meta "quickbooks_product_id"). When it is missing, the QuickBooks item with the same SKU is looked up and
 * linked, so a new product doesn't make invoices fail. Before 1.0.39 a missing link made QuickBooks refuse the
 * invoice and nobody saw it: now the reason is stored on the order (order_meta "quickbooks_invoice_error") and emailed.
 */

// load-all.php includes this file on every request: the QuickBooks SDK (quickbooks_app.php) is only loaded when an
// invoice is made (createQuickBooksInvoiceForOrder), never here.
require_once PLU_PATH . FILE_SEPARATOR . "appstv_crm_customer/includes" . FILE_SEPARATOR . "constants.php";
require_once PLU_PATH . FILE_SEPARATOR . "appstv_crm_customer/includes" . FILE_SEPARATOR . "customer.php";

/** Receives the failure emails, and a BCC copy of every Shopify invoice QuickBooks sends to the customer */
if( !defined( 'QUICKBOOKS_INVOICE_NOTIFY_EMAIL' ) ){
    define( 'QUICKBOOKS_INVOICE_NOTIFY_EMAIL', 'unknown0492@gmail.com' );
}

/** order_meta: the reason the last invoice attempt failed, with its time (removed once an invoice is made) */
if( !defined( 'ORDER_META_QUICKBOOKS_INVOICE_ERROR' ) ){
    define( 'ORDER_META_QUICKBOOKS_INVOICE_ERROR', 'quickbooks_invoice_error' );
}

/** order_meta: "1" when the invoice was made by hand in QuickBooks (the CRM then never makes one) */
if( !defined( 'ORDER_META_QUICKBOOKS_INVOICE_MANUAL' ) ){
    define( 'ORDER_META_QUICKBOOKS_INVOICE_MANUAL', 'quickbooks_invoice_manual' );
}

/**
 * @description Loads quickbooks_app.php (getQuickBooksDataService and the QuickBooks SDK) when it isn't loaded yet.
 *              webservice.php loads it for every webservice call; a CLI test or cron may not.
 */
function quickbooksLoadSdk(){
    if( !function_exists( 'getQuickBooksDataService' ) ){
        require_once PLU_PATH . FILE_SEPARATOR . "appstv_crm_apps/webservice" . FILE_SEPARATOR . "quickbooks_app.php";
    }
}

/**
 * @description All QuickBooks items (Id, Name, Sku, Active), read once per request. Read only.
 * @param object $dataService - QuickBooks DataService
 * @param string &$error - Set to the reason when QuickBooks could not be read
 * @return array|null Items (IPPItem objects), or NULL on error
 */
function getAllQuickBooksItems( $dataService, &$error ){
    static $cache = NULL;             // Per DataService object; an entry goes with its object (PHP 8 WeakMap)

    if( $cache === NULL ){
        $cache = new WeakMap();
    }
    if( isset( $cache[ $dataService ] ) ){
        return $cache[ $dataService ];
    }
    ob_start();                       // The SDK may print notices: keep them out of the JSON answer
    try{
        $result = $dataService->Query( "SELECT * FROM Item STARTPOSITION 1 MAXRESULTS 1000" );
        $fault  = $dataService->getLastError();
    }
    finally{
        ob_end_clean();
    }
    if( $fault ){
        $error = "QuickBooks items could not be read: " . quickbooksFaultMessage( $fault );
        return NULL;
    }
    $cache[ $dataService ] = is_array( $result ) ? $result : array();
    return $cache[ $dataService ];
}

/**
 * @description Active QuickBooks items whose SKU is exactly $sku. Read only.
 * @param object $dataService - QuickBooks DataService
 * @param string $sku - SKU
 * @param string &$error - Set to the reason when QuickBooks could not be read
 * @return array|null Array of array( 'id', 'name' ), or NULL on error
 */
function quickbooksItemsBySku( $dataService, $sku, &$error ){
    $items = getAllQuickBooksItems( $dataService, $error );
    if( $items === NULL ){
        return NULL;
    }
    $matches = array();
    foreach( $items as $item ){
        if( ( trim( (string) $item->Sku ) === trim( (string) $sku ) ) && ( (string) $item->Active === 'true' ) ){
            $matches[] = array( 'id' => (string) $item->Id, 'name' => (string) $item->Name );
        }
    }
    return $matches;
}

/**
 * @description QuickBooks item ID for a SKU: the one stored on the CRM product, else the active QuickBooks item with
 *              that SKU (then stored on the product). Never guesses between several items.
 * @param object $dataService - QuickBooks DataService
 * @param string $sku - SKU of the order line
 * @param string &$reason - Set to the reason when no item can be used
 * @return string|null QuickBooks item ID, or NULL
 */
function quickbooksResolveItemForSku( $dataService, $sku, &$reason ){
    if( trim( (string) $sku ) === '' ){
        $reason = "An item of the order has no SKU";
        return NULL;
    }

    $product = new Product();
    $product->readProductUsingSKU( escape_string( $sku ) );
    $product_id = $product->getProductID();
    if( ( $product_id === NULL ) || ( $product_id === '' ) ){
        $reason = "No CRM product has SKU " . $sku;
        return NULL;
    }

    $stored = ProductMeta::getQuickBooksProductID( $product_id );
    if( ( $stored !== NULL ) && ( $stored !== '' ) ){
        return $stored;
    }

    $error   = '';
    $matches = quickbooksItemsBySku( $dataService, $sku, $error );
    if( $matches === NULL ){
        $reason = $error;
        return NULL;
    }
    if( count( $matches ) === 0 ){
        $reason = "SKU " . $sku . " is not linked to a QuickBooks item, and no active QuickBooks item has this SKU";
        return NULL;
    }
    if( count( $matches ) > 1 ){
        $reason = "SKU " . $sku . " is not linked to a QuickBooks item, and " . count( $matches ) . " QuickBooks items have this SKU (IDs "
                . implode( ', ', array_column( $matches, 'id' ) ) . ")";
        return NULL;
    }

    ProductMeta::setProductMetaValue( $product_id, ProductMeta::DB_PRODUCT_META_KEY_QUICKBOOKS_PRODUCT_ID, escape_string( $matches[ 0 ][ 'id' ] ) );
    return $matches[ 0 ][ 'id' ];
}

/**
 * @description Short readable text of a QuickBooks error (Message: Detail), without markup
 * @param object $fault - Error from $dataService->getLastError()
 * @return string
 */
function quickbooksFaultMessage( $fault ){
    $body = (string) $fault->getResponseBody();
    $text = '';
    if( preg_match( '/<Message>(.*?)<\/Message>/s', $body, $m ) ){
        $text = $m[ 1 ];
        if( preg_match( '/<Detail>(.*?)<\/Detail>/s', $body, $d ) ){
            $text .= ': ' . $d[ 1 ];
        }
    }
    else{
        $json = json_decode( $body, true );
        if( isset( $json[ 'Fault' ][ 'Error' ][ 0 ] ) ){
            $e    = $json[ 'Fault' ][ 'Error' ][ 0 ];
            $text = ( isset( $e[ 'Message' ] ) ? $e[ 'Message' ] : '' ) . ( isset( $e[ 'Detail' ] ) ? ': ' . $e[ 'Detail' ] : '' );
        }
    }
    if( $text === '' ){
        $text = trim( strip_tags( $body ) );
    }
    $text = html_entity_decode( $text, ENT_QUOTES );
    return 'HTTP ' . $fault->getHttpStatusCode() . ' - ' . mb_substr( $text, 0, 400 );
}

/**
 * @description Stores the reason on the order and emails it to QUICKBOOKS_INVOICE_NOTIFY_EMAIL
 * @param Order $order - The order (read)
 * @param string $reason - Why the invoice was not made
 * @param string $trigger - What tried: "Shopify order" or "Create QuickBooks Invoice on the Orders page"
 */
function recordQuickBooksInvoiceFailure( $order, $reason, $trigger ){
    $order_id = $order->getOrderID();
    $stamp    = gmdate( 'Y-m-d\TH:i:s\Z' );
    OrderMeta::setOrderMetaValue( $order_id, ORDER_META_QUICKBOOKS_INVOICE_ERROR, escape_string( $stamp . ' ' . $reason ) );

    $shopify_name = OrderMeta::getOrderMetaValue( $order_id, OrderMeta::DB_ORDER_META_KEY_SHOPIFY_ORDER_NAME );
    $customer     = new Customer();
    $customer->readCustomer( $order->getCustomerID() );
    $siteConfig   = getSiteConfig();
    $orders_url   = $siteConfig->protocol . '://' . $siteConfig->domain_name . '/adminpanel.php?what_do_you_want=manage_orders';
    $h            = function( $s ){ return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' ); };

    $message = '<p>The QuickBooks invoice of this order was <b>not</b> created.</p>'
             . '<table cellpadding="4" style="border-collapse:collapse">'
             . '<tr><td><b>CRM order</b></td><td>' . $h( $order_id ) . '</td></tr>'
             . '<tr><td><b>Shopify order</b></td><td>' . $h( $shopify_name !== NULL ? $shopify_name : '-' ) . '</td></tr>'
             . '<tr><td><b>Customer</b></td><td>' . $h( trim( $customer->getFirstName() . ' ' . $customer->getLastName() ) ) . '</td></tr>'
             . '<tr><td><b>Total</b></td><td>' . $h( $order->getCurrency() . ' ' . $order->getTotalPrice() ) . '</td></tr>'
             . '<tr><td><b>Tried by</b></td><td>' . $h( $trigger ) . '</td></tr>'
             . '<tr><td><b>Reason</b></td><td>' . $h( $reason ) . '</td></tr>'
             . '</table>'
             . '<p>Fix the reason, then use <i>Create QuickBooks Invoice</i> on the order, or make the invoice in QuickBooks and use '
             . '<i>Mark Invoice Made by Hand</i>: <a href="' . $h( $orders_url ) . '">Orders</a></p>';

    // Defined only by local tests, which must never send a real email
    if( defined( 'CRM_TEST_NO_EMAIL' ) ){
        $GLOBALS[ 'crm_test_emails' ][] = array( 'subject' => 'QuickBooks invoice NOT created: order ' . $order_id, 'body' => $message );
        return;
    }

    try{
        $mail = sendMailObject();
        $mail->isHTML( true );
        $mail->setFrom( EMAIL_NOREPLY, 'myappstv.com' );
        $mail->AddAddress( QUICKBOOKS_INVOICE_NOTIFY_EMAIL );
        $mail->Subject = 'QuickBooks invoice NOT created: order ' . ( $shopify_name !== NULL ? $shopify_name . ' / ' : '' ) . $order_id;
        $mail->Body    = $message;
        if( !$mail->Send() ){
            error_log( "QuickBooks invoice failure email for order $order_id was not sent" );
        }
    }
    catch( Throwable $e ){
        error_log( "QuickBooks invoice failure email for order $order_id was not sent: " . get_class( $e ) );
    }
}

/**
 * @description Creates the QuickBooks invoice of a stored CRM order. Refuses an order that already has an invoice,
 *              or whose invoice was made by hand. A failure is stored on the order and emailed.
 * @param string $order_id - CRM order ID
 * @param string $trigger - What tried (shown in the failure email)
 * @param object|null $dataService - QuickBooks DataService (NULL = the CRM's own connection)
 * @return array array( 'ok' => bool, 'message' => string, 'invoice_id' => string, 'invoice_number' => string )
 */
function createQuickBooksInvoiceForOrder( $order_id, $trigger, $dataService = NULL ){
    $e_order_id = escape_string( $order_id );
    $order      = new Order();
    if( $order->readOrder( $e_order_id ) === NULL ){
        return array( 'ok' => false, 'message' => "No such order", 'invoice_id' => '', 'invoice_number' => '' );
    }

    $existing = OrderMeta::getOrderMetaValue( $e_order_id, OrderMeta::DB_ORDER_META_KEY_QUICKBOOKS_INVOICE_ID );
    if( ( $existing !== NULL ) && ( $existing !== '' ) ){
        return array( 'ok' => false, 'message' => "This order already has a QuickBooks invoice", 'invoice_id' => $existing,
                      'invoice_number' => (string) OrderMeta::getOrderMetaValue( $e_order_id, OrderMeta::DB_ORDER_META_KEY_QUICKBOOKS_INVOICE_NUMBER ) );
    }
    if( OrderMeta::getOrderMetaValue( $e_order_id, ORDER_META_QUICKBOOKS_INVOICE_MANUAL ) === '1' ){
        return array( 'ok' => false, 'message' => "The invoice of this order was made by hand in QuickBooks", 'invoice_id' => '', 'invoice_number' => '' );
    }

    $fail = function( $reason ) use ( $order, $trigger ){
        recordQuickBooksInvoiceFailure( $order, $reason, $trigger );
        return array( 'ok' => false, 'message' => $reason, 'invoice_id' => '', 'invoice_number' => '' );
    };

    // Anything the QuickBooks SDK prints (e.g. PHP 8.5 "Deprecated: curl_close()" when error display is on) is
    // discarded, so the Orders page's JSON answer stays valid (1.0.41)
    ob_start();
    try{
        return createQuickBooksInvoiceForOrderQuiet( $order, $e_order_id, $dataService, $fail );
    }
    finally{
        ob_end_clean();
    }
}

/**
 * @description The work of createQuickBooksInvoiceForOrder, run while its output is discarded
 * @param Order $order - The order (read)
 * @param string $e_order_id - Escaped order ID
 * @param object|null $dataService - QuickBooks DataService (NULL = the CRM's own connection)
 * @param callable $fail - Stores + emails a failure reason and returns the failure result
 * @return array Same as createQuickBooksInvoiceForOrder
 */
function createQuickBooksInvoiceForOrderQuiet( $order, $e_order_id, $dataService, $fail ){
    try{
        if( $dataService === NULL ){
            quickbooksLoadSdk();
            $dataService = getQuickBooksDataService();
        }
        if( $dataService === NULL ){
            return $fail( "The CRM is not connected to QuickBooks (no QuickBooks tokens)" );
        }

        // Customer
        $customer = new Customer();
        $customer->readCustomer( $order->getCustomerID() );
        $quickbooks_customer_id = CustomerMeta::getCustomerMetaValue( $customer->getCustomerID(), CustomerMeta::DB_CUSTOMER_META_KEY_QUICKBOOKS_CUSTOMER_ID );
        if( ( $quickbooks_customer_id === NULL ) || ( $quickbooks_customer_id === '' ) ){
            return $fail( "The customer of this order has no QuickBooks customer ID" );
        }

        // Addresses
        $billing  = new OrderAddress();
        $shipping = new OrderAddress();
        $addresses = $billing->readAllOrderAddressForOrderID( $e_order_id );
        foreach( (array) $addresses as $address ){
            if( $address->getAddressType() === OrderAddress::DB_FIELD_ORDER_ADDRESS_ADDRESS_TYPE_BILLING ){
                $billing = $address;
            }
            else if( $address->getAddressType() === OrderAddress::DB_FIELD_ORDER_ADDRESS_ADDRESS_TYPE_SHIPPING ){
                $shipping = $address;
            }
        }

        // Item lines: every item needs its QuickBooks item first, so nothing is sent with a missing one
        $products   = json_decode( (string) $order->getProducts(), true );
        if( !is_array( $products ) || ( count( $products ) === 0 ) ){
            return $fail( "The order has no items" );
        }
        $line_items = array();
        $problems   = array();
        foreach( $products as $shopifyProduct ){
            $sku    = isset( $shopifyProduct[ SHOPIFY_PRODUCT_SKU ] ) ? (string) $shopifyProduct[ SHOPIFY_PRODUCT_SKU ] : '';
            $reason = '';
            $quickbooks_product_id = quickbooksResolveItemForSku( $dataService, $sku, $reason );
            if( $quickbooks_product_id === NULL ){
                $problems[] = $reason;
                continue;
            }

            // Use the product name from Shopify: variants give a different product name
            $product_name       = $shopifyProduct[ 'name' ];
            $qty                = intval( $shopifyProduct[ 'quantity' ] );
            $unit_price         = round( floatval( $shopifyProduct[ SHOPIFY_PRODUCT_PRICE ] ), 2 );

            // Unit price exclusive of GST 9%
            $unit_price_exc_gst = round( ( $unit_price / 1.09 ), 4 );
            $amount_exc_gst     = round( $qty * $unit_price_exc_gst, 4 );

            $line_items[] = array(
                "DetailType" => "SalesItemLineDetail",
                "SalesItemLineDetail" => array(
                    "ItemRef" => array(
                        "value" => $quickbooks_product_id,
                        "name" => $product_name
                    ),
                    "Qty" => $qty,
                    "UnitPrice" => $unit_price_exc_gst,
                    "TaxCodeRef" => array(
                        "value" => "44"         // GST SR 9% in QuickBooks TaxCode
                    )
                ),
                "Amount" => $amount_exc_gst,
                "Description" => isset( $shopifyProduct[ 'variant_title' ] ) ? $shopifyProduct[ 'variant_title' ] : ''
            );
        }
        if( count( $problems ) > 0 ){
            return $fail( implode( '; ', array_unique( $problems ) ) );
        }

        // Discount lines
        $discount_codes = json_decode( (string) OrderMeta::getOrderMetaValue( $e_order_id, OrderMeta::DB_ORDER_META_KEY_SHOPIFY_ORDER_DISCOUNT_CODES ), true );
        foreach( (array) $discount_codes as $discount ){
            $line_items[] = array(
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
        }

        // Shipping line
        $total_shipping_price   = $order->getTotalShippingPrice();
        $shipping_price_exc_tax = round( ( floatval( $total_shipping_price ) / 1.09 ), 4 );
        $line_items[] = array(
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

        $invoiceParams = array(
            "Line" => $line_items,
            "CustomerRef" => array(
                "name" => $customer->getFirstName() . " " . $customer->getLastName(),
                "value" => $quickbooks_customer_id
            ),
            "BillEmail" => array(
                "Address" => $order->getEmail()
            ),
            "BillEmailBcc" => array(
                "Address" => QUICKBOOKS_INVOICE_NOTIFY_EMAIL
            ),
            "BillAddr" => array(
                "Line1" => $billing->getFirstName() . " " . $billing->getLastName(),
                "Line2" => $billing->getAddress1(),
                "Line3" => $billing->getAddress2(),
                "Line4" => $billing->getCity() . ", " . $billing->getState() . ", " . $billing->getCountry(),
                "Line5" => $billing->getZip(),
                "Lat" => $billing->getLatitude(),
                "Long" => $billing->getLongitude(),
            ),
            "ShipAddr" => array(
                "Line1" => $shipping->getAddress1(),
                "Line2" => $shipping->getAddress2(),
                "City" => $shipping->getCity(),
                "PostalCode" => $shipping->getZip(),
                "Lat" => $shipping->getLatitude(),
                "Long" => $shipping->getLongitude(),
            ),
            "ApplyTaxAfterDiscount" => false,
            "GlobalTaxCalculation" => "TaxInclusive"
        );

        $invoiceObj   = \QuickBooksOnline\API\Facades\Invoice::create( $invoiceParams );
        $resultingObj = $dataService->Add( $invoiceObj );
        $fault        = $dataService->getLastError();
        if( $fault || !$resultingObj ){
            return $fail( $fault ? "QuickBooks refused the invoice: " . quickbooksFaultMessage( $fault ) : "QuickBooks gave no answer" );
        }
    }
    catch( Throwable $e ){
        return $fail( "Error while creating the invoice: " . mb_substr( $e->getMessage(), 0, 300 ) );
    }

    OrderMeta::setOrderMetaValue( $e_order_id, OrderMeta::DB_ORDER_META_KEY_QUICKBOOKS_INVOICE_ID, escape_string( $resultingObj->Id ) );
    OrderMeta::setOrderMetaValue( $e_order_id, OrderMeta::DB_ORDER_META_KEY_QUICKBOOKS_INVOICE_NUMBER, escape_string( $resultingObj->DocNumber ) );
    OrderMeta::deleteOrderMetaValue( $e_order_id, ORDER_META_QUICKBOOKS_INVOICE_ERROR );

    return array( 'ok' => true, 'message' => "QuickBooks invoice " . $resultingObj->DocNumber . " created",
                  'invoice_id' => (string) $resultingObj->Id, 'invoice_number' => (string) $resultingObj->DocNumber );
}
