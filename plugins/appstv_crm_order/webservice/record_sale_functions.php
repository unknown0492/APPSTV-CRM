<?php

/*
 * Record Sale (v1.0.6) - webservice actions of the Record Sale page (Orders plugin)
 *
 * A sale = one customer + one invoice + product lines, each line = one TV model + its serial numbers.
 * Every TV gets: a product_sn record (an unsold stock record of the same model is reused, otherwise a new one),
 * an invoice_product_sn link, a product_sn_warranty and optionally a product_sn_extended_warranty.
 * Dates are stored as 'YYYY-MM-DD 00:00:00' and warranty periods in days, the same as the Excel import (v1.0.5).
 *
 * Duplicate serial numbers are real (a manufacturer batch arrived with duplicates): a serial number that is
 * already sold, repeated in the form, or in stock under another model is a WARNING, and is saved as a separate
 * TV once the user confirms.
 */

define( 'APPSTV_SALE_MAX_SERIALS', 3000 );                    // Most TVs one sale may hold
define( 'APPSTV_SALE_MAX_DRAFT_BYTES', 2000000 );             // Largest draft accepted (about 2 MB of JSON)
define( 'APPSTV_SALE_SOURCE_DIRECT', '20365841' );            // sources.source_id of "Direct" (customers created here)

/**
 * A rule stopped the Record Sale save; the message is meant for the user (database errors are never shown).
 * Not a RuntimeException on purpose: mysqli_sql_exception is one.
 */
class AppstvSaleRuleException extends Exception {}


/**
 * @description Runs one query inside the Record Sale save and throws when it fails (so the transaction rolls back)
 * @param {string} $sql - The SQL to run
 * @returns {mysqli_result|bool} The query result
 */
function appstv_sale_db( $sql ){
    global $con;
    $result = mysqli_query( $con, $sql );
    if( $result === false ){
        throw new Exception( mysqli_error( $con ) );
    }
    return $result;
}

/**
 * @description Makes an ID in one of the system's shapes that is not used yet in $table.$column
 * @param {string} $table - Table that holds the ID
 * @param {string} $column - Column of the ID
 * @param {string} $shape - 'customer' (ddmmyy + 4 digits), 'product_sn' (dd + millis + 5 digits) or 'ten' (dd + 4 + 4 digits)
 * @returns {string} The new ID
 */
function appstv_sale_unique_id( $table, $column, $shape ){
    static $issued = array();                                  // IDs handed out in this request (not inserted yet)

    for( $attempt = 0; $attempt < 200; $attempt++ ){
        $now = new DateTime( "now", new DateTimeZone( "+0800" ) );
        if( $shape === 'customer' ){
            $id = $now->format( 'dmy' ) . rand( 1000, 9999 );
        }
        else if( $shape === 'product_sn' ){
            $id = $now->format( 'd' ) . $now->format( 'v' ) . rand( 10000, 99999 );
        }
        else {
            $id = $now->format( 'd' ) . sprintf( "%04d", (int)( fmod( microtime( true ), 1 ) * 10000 ) ) . rand( 1000, 9999 );
        }

        if( isset( $issued[ $table . '.' . $id ] ) ){
            continue;
        }
        $result = appstv_sale_db( "SELECT 1 FROM `$table` WHERE `$column` = '$id' LIMIT 1" );
        if( mysqli_num_rows( $result ) === 0 ){
            $issued[ $table . '.' . $id ] = true;
            return $id;
        }
    }
    throw new Exception( "Could not make a unique ID for $table" );
}

/**
 * @description Cleans one serial number: letters and digits only, upper case (same rule as the scanner)
 * @param {string} $raw - Serial number as typed, scanned or read from a file
 * @returns {string} The serial number, or '' when nothing valid is left or it is longer than 50
 */
function appstv_sale_clean_serial( $raw ){
    $raw = (string) $raw;
    if( preg_match( '/^\d+(\.\d+)?E\+\d+$/i', $raw ) ){       // Excel number shown as 1.0105E+13
        $raw = number_format( (float) $raw, 0, '', '' );
    }
    $cleaned = strtoupper( preg_replace( '/[^A-Za-z0-9]/', '', $raw ) );
    return ( strlen( $cleaned ) > 50 ) ? '' : $cleaned;
}

/**
 * @description Checks a 'YYYY-MM-DD' date
 * @param {string} $value - The date text
 * @returns {DateTime|null} The date at midnight, or null when it is not a real date
 */
function appstv_sale_parse_date( $value ){
    $date = DateTime::createFromFormat( '!Y-m-d', (string) $value );
    if( $date === false || $date->format( 'Y-m-d' ) !== $value ){
        return null;
    }
    return $date;
}

/**
 * @description Returns the user_id of the signed-in user (from the access token)
 * @returns {string} The user_id, '' when unknown
 */
function appstv_sale_user_id(){
    global $globalAccessToken;
    $payload = getJWTPayload( $globalAccessToken );
    return isset( $payload[ 'user_id' ] ) ? (string) $payload[ 'user_id' ] : '';
}

/**
 * @description Reads every product_sn record of the given serial numbers, with what each one is used for
 * @param {string[]} $serials - Clean serial numbers
 * @param {bool} $lock - True to lock the rows (inside the save transaction)
 * @returns {array} serial_number => list of records { product_sn_id, product_id, product_name, free, sold_to }
 */
function appstv_sale_read_serial_records( $serials, $lock ){
    $records = array();                                        // serial_number => records
    $ids_by_sn = array();                                      // product_sn_id => serial_number (for the sold-to lookup)

    foreach( array_chunk( array_values( array_unique( $serials ) ), 500 ) as $chunk ){
        $in = "'" . implode( "','", array_map( 'escape_string', $chunk ) ) . "'";
        $sql = "SELECT ps.product_sn_id, ps.serial_number, ps.product_id, ps.allotted_to_customer, p.name AS product_name,
                       EXISTS( SELECT 1 FROM invoice_product_sn i WHERE i.product_sn_id = ps.product_sn_id ) AS invoiced,
                       EXISTS( SELECT 1 FROM order_product_sn o WHERE o.product_sn_id = ps.product_sn_id ) AS in_order,
                       EXISTS( SELECT 1 FROM product_warranty_replacements r WHERE r.new_product_sn_id = ps.product_sn_id ) AS replacement
                FROM product_sn ps
                LEFT JOIN products p ON p.product_id = ps.product_id
                WHERE ps.serial_number IN ( $in )
                ORDER BY ps.id" . ( $lock ? " FOR UPDATE" : "" );
        $result = appstv_sale_db( $sql );
        while( ( $row = mysqli_fetch_assoc( $result ) ) !== null ){
            $sn = strtoupper( $row[ 'serial_number' ] );
            $records[ $sn ][] = array(
                'product_sn_id' => $row[ 'product_sn_id' ],
                'product_id'    => $row[ 'product_id' ],
                'product_name'  => (string) $row[ 'product_name' ],
                'free'          => ( (int) $row[ 'allotted_to_customer' ] === 0 && !$row[ 'invoiced' ] && !$row[ 'in_order' ] && !$row[ 'replacement' ] ),
                'sold_to'       => ''
            );
            $ids_by_sn[ $row[ 'product_sn_id' ] ] = $sn;
        }
    }

    // Who each used record went to (customer + invoice), for the warning text
    $sold_to = array();                                        // product_sn_id => "Customer (inv 123)"
    foreach( array_chunk( array_keys( $ids_by_sn ), 500 ) as $chunk ){
        $in = "'" . implode( "','", array_map( 'escape_string', $chunk ) ) . "'";
        $sql = "SELECT ipsn.product_sn_id, oi.invoice_no,
                       COALESCE( NULLIF( c.company_name, '' ), TRIM( CONCAT( c.first_name, ' ', c.last_name ) ) ) AS customer_name
                FROM invoice_product_sn ipsn
                JOIN order_invoices oi ON oi.invoice_id = ipsn.invoice_id
                LEFT JOIN customers c ON c.customer_id = oi.customer_id
                WHERE ipsn.product_sn_id IN ( $in )";
        $result = appstv_sale_db( $sql );
        while( ( $row = mysqli_fetch_assoc( $result ) ) !== null ){
            $label = trim( (string) $row[ 'customer_name' ] );
            $label = ( $label === '' ? 'a customer' : $label ) . ' (invoice ' . $row[ 'invoice_no' ] . ')';
            $sold_to[ $row[ 'product_sn_id' ] ][] = $label;
        }
    }

    foreach( $records as $sn => $list ){
        foreach( $list as $k => $record ){
            if( isset( $sold_to[ $record[ 'product_sn_id' ] ] ) ){
                $records[ $sn ][ $k ][ 'sold_to' ] = implode( ', ', $sold_to[ $record[ 'product_sn_id' ] ] );
            }
            else if( !$record[ 'free' ] ){
                $records[ $sn ][ $k ][ 'sold_to' ] = 'an order or a replacement';
            }
        }
    }

    return $records;
}

/**
 * @description Decides what happens to each serial number of a sale (the same rules the page shows)
 * @param {array} $lines - [ { product_id, serials: [ ... ] } ] with clean serial numbers
 * @param {array} $records - Output of appstv_sale_read_serial_records()
 * @returns {array} [ 'items' => [ { line, serial, product_id, action: new|stock, product_sn_id, warning } ], 'warnings' => count ]
 */
function appstv_sale_plan( $lines, $records ){
    $items = array();
    $warnings = 0;
    $seen = array();                                           // serial => times met in this sale
    $reused = array();                                         // product_sn_id => true once a stock record is taken

    foreach( $lines as $line_no => $line ){
        foreach( $line[ 'serials' ] as $sn ){
            $seen[ $sn ] = isset( $seen[ $sn ] ) ? $seen[ $sn ] + 1 : 1;
            $list = isset( $records[ $sn ] ) ? $records[ $sn ] : array();
            $item = array( 'line' => $line_no, 'serial' => $sn, 'product_id' => $line[ 'product_id' ], 'action' => 'new', 'product_sn_id' => null, 'warning' => '' );

            foreach( $list as $record ){                       // Unsold stock record of this model: reuse it
                if( $record[ 'free' ] && $record[ 'product_id' ] === $line[ 'product_id' ] && !isset( $reused[ $record[ 'product_sn_id' ] ] ) ){
                    $item[ 'action' ] = 'stock';
                    $item[ 'product_sn_id' ] = $record[ 'product_sn_id' ];
                    $reused[ $record[ 'product_sn_id' ] ] = true;
                    break;
                }
            }

            if( $seen[ $sn ] > 1 ){
                $item[ 'warning' ] = 'repeat';
            }
            else if( $item[ 'action' ] === 'new' ){
                foreach( $list as $record ){
                    if( !$record[ 'free' ] ){ $item[ 'warning' ] = 'sold'; break; }
                }
                if( $item[ 'warning' ] === '' && count( $list ) > 0 ){
                    $item[ 'warning' ] = 'other_model';        // Only in stock under another model
                }
            }

            if( $item[ 'warning' ] !== '' ){ $warnings++; }
            $items[] = $item;
        }
    }

    return array( 'items' => $items, 'warnings' => $warnings );
}


/**
 * Customer search for the Record Sale page (company, names, email, mobile or customer ID)
 */
function appstv_sale_search_customers(){
    checkAuthorizationForFunction( __FUNCTION__ );

    $query = request( 'q' );
    validateEmptyString( $query, __FUNCTION__, "Type a name, email, mobile or customer ID to search." );

    $e_query = escape_string( $query );
    $sql = "SELECT customer_id, company_name, first_name, last_name, primary_email, primary_mobile
            FROM customers
            WHERE company_name LIKE '%$e_query%' OR first_name LIKE '%$e_query%' OR last_name LIKE '%$e_query%'
               OR CONCAT( first_name, ' ', last_name ) LIKE '%$e_query%'
               OR primary_email LIKE '%$e_query%' OR primary_mobile LIKE '%$e_query%' OR customer_id = '$e_query'
            ORDER BY company_name, first_name
            LIMIT 20";
    $result_set = selectQuery( $sql );

    $customers = array();
    while( ( $row = mysqli_fetch_assoc( $result_set ) ) !== null ){
        $person = trim( $row[ 'first_name' ] . ' ' . $row[ 'last_name' ] );
        $customers[] = array(
            'customer_id'    => $row[ 'customer_id' ],
            'name'           => ( $row[ 'company_name' ] !== '' ) ? $row[ 'company_name' ] : $person,
            'person'         => ( $row[ 'company_name' ] !== '' ) ? $person : '',
            'primary_email'  => $row[ 'primary_email' ],
            'primary_mobile' => $row[ 'primary_mobile' ]
        );
    }

    $responseData = array(
        'message' => count( $customers ) . " customer(s) found.",
        'data'    => $customers
    );
    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $responseData );
}


/**
 * Looks up an invoice number: which customer(s) it belongs to and how many TVs it holds
 */
function appstv_sale_check_invoice(){
    checkAuthorizationForFunction( __FUNCTION__ );

    $invoice_no = request( 'invoice_no' );
    validateEmptyString( $invoice_no, __FUNCTION__, "Invoice No. is required." );

    $e_invoice_no = escape_string( $invoice_no );
    $sql = "SELECT oi.invoice_id, oi.invoice_date, oi.customer_id,
                   COALESCE( NULLIF( c.company_name, '' ), TRIM( CONCAT( c.first_name, ' ', c.last_name ) ) ) AS customer_name,
                   ( SELECT COUNT(*) FROM invoice_product_sn i WHERE i.invoice_id = oi.invoice_id ) AS tv_count
            FROM order_invoices oi
            LEFT JOIN customers c ON c.customer_id = oi.customer_id
            WHERE oi.invoice_no = '$e_invoice_no'
            ORDER BY oi.id";
    $result_set = selectQuery( $sql );

    $invoices = array();
    while( ( $row = mysqli_fetch_assoc( $result_set ) ) !== null ){
        $invoices[] = array(
            'invoice_id'    => $row[ 'invoice_id' ],
            'invoice_date'  => substr( (string) $row[ 'invoice_date' ], 0, 10 ),
            'customer_id'   => (string) $row[ 'customer_id' ],
            'customer_name' => (string) $row[ 'customer_name' ],
            'tv_count'      => (int) $row[ 'tv_count' ]
        );
    }

    $responseData = array(
        'message' => count( $invoices ) . " invoice(s) found.",
        'data'    => $invoices
    );
    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $responseData );
}


/**
 * Returns the existing records of a list of serial numbers, so the page can show New / In stock / warnings
 */
function appstv_sale_check_serials(){
    checkAuthorizationForFunction( __FUNCTION__ );

    $serials = json_decode( (string) request( 'serials' ), true );
    if( !is_array( $serials ) || count( $serials ) === 0 || count( $serials ) > APPSTV_SALE_MAX_SERIALS ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Send between 1 and " . APPSTV_SALE_MAX_SERIALS . " serial numbers." );
        return;
    }

    $clean = array();
    foreach( $serials as $sn ){
        $sn = appstv_sale_clean_serial( $sn );
        if( $sn !== '' ){ $clean[] = $sn; }
    }

    try {
        $records = appstv_sale_read_serial_records( $clean, false );
    } catch( Throwable $e ){
        error_log( __FUNCTION__ . ': ' . $e->getMessage() );
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "The serial numbers could not be checked. Please try again." );
        return;
    }

    $responseData = array(
        'message' => "Checked " . count( $clean ) . " serial number(s).",
        'data'    => (object) $records
    );
    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $responseData );
}


/**
 * Reads serial numbers from an uploaded .xlsx, .csv or .txt file and returns them column by column
 */
function appstv_sale_read_serial_file(){
    checkAuthorizationForFunction( __FUNCTION__ );

    if( !isset( $_FILES[ 'sn_file' ] ) || $_FILES[ 'sn_file' ][ 'error' ] !== UPLOAD_ERR_OK ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "The file could not be uploaded." );
        return;
    }
    $file = $_FILES[ 'sn_file' ];
    $ext  = strtolower( pathinfo( $file[ 'name' ], PATHINFO_EXTENSION ) );
    if( !in_array( $ext, array( 'xlsx', 'csv', 'txt' ) ) ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Only .xlsx, .csv and .txt files can be read." );
        return;
    }
    if( $file[ 'size' ] > 5 * 1024 * 1024 ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "The file is larger than 5 MB." );
        return;
    }

    // Rows of raw cell text
    $rows = array();
    if( $ext === 'xlsx' ){
        require_once BASE_PATH . FILE_SEPARATOR . 'library' . FILE_SEPARATOR . 'Classes' . FILE_SEPARATOR . 'SimpleXLSX.php';
        $xlsx = \Shuchkin\SimpleXLSX::parse( $file[ 'tmp_name' ] );
        if( !$xlsx ){
            echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "The Excel file could not be read." );
            return;
        }
        $rows = $xlsx->rows();
    }
    else {
        $text = (string) file_get_contents( $file[ 'tmp_name' ] );
        $text = preg_replace( '/^\xEF\xBB\xBF/', '', $text );  // UTF-8 byte order mark
        $lines = preg_split( '/\r\n|\r|\n/', $text );
        $first = isset( $lines[ 0 ] ) ? $lines[ 0 ] : '';
        $delimiter = ',';                                      // Most used of , ; and tab on the first line
        foreach( array( ";", "\t" ) as $candidate ){
            if( substr_count( $first, $candidate ) > substr_count( $first, $delimiter ) ){ $delimiter = $candidate; }
        }
        foreach( $lines as $line ){
            if( trim( $line ) === '' ){ continue; }
            $rows[] = ( $ext === 'txt' && strpos( $line, $delimiter ) === false ) ? preg_split( '/\s+/', trim( $line ) ) : str_getcsv( $line, $delimiter );
        }
    }

    $rows = array_slice( $rows, 0, 20000 );
    $width = 0;
    foreach( $rows as $row ){ $width = max( $width, count( $row ) ); }

    $columns = array();
    for( $c = 0; $c < $width; $c++ ){
        $first_raw = isset( $rows[ 0 ][ $c ] ) ? trim( (string) $rows[ 0 ][ $c ] ) : '';
        // The first row is a heading when it has spaces / signs or no digit at all (e.g. "Serial No.", "SN")
        $has_header = ( $first_raw !== '' && ( preg_match( '/[^A-Za-z0-9]/', $first_raw ) || !preg_match( '/\d/', $first_raw ) ) );
        $values = array();
        foreach( $rows as $r => $row ){
            if( $r === 0 && $has_header ){ continue; }
            $sn = appstv_sale_clean_serial( isset( $row[ $c ] ) ? trim( (string) $row[ $c ] ) : '' );
            if( $sn !== '' ){ $values[] = $sn; }
        }
        if( count( $values ) === 0 ){ continue; }
        $columns[] = array(
            'index'  => $c,
            'header' => $has_header ? $first_raw : 'Column ' . ( $c + 1 ),
            'values' => $values
        );
    }

    if( count( $columns ) === 0 ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "No serial numbers were found in the file." );
        return;
    }

    $responseData = array(
        'message' => count( $columns ) . " column(s) with values found.",
        'data'    => array( 'columns' => $columns )
    );
    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $responseData );
}


/**
 * Returns the signed-in user's unfinished Record Sale form, if any
 */
function appstv_sale_get_draft(){
    checkAuthorizationForFunction( __FUNCTION__ );

    $e_user_id = escape_string( appstv_sale_user_id() );
    $result_set = selectQuery( "SELECT draft_data, serial_count, updated_on FROM sale_drafts WHERE user_id = '$e_user_id' LIMIT 1" );
    $row = mysqli_fetch_assoc( $result_set );

    $responseData = array(
        'message' => ( $row === null ) ? "No draft." : "Draft found.",
        'data'    => ( $row === null ) ? null : array(
            'draft_data'   => $row[ 'draft_data' ],
            'serial_count' => (int) $row[ 'serial_count' ],
            'updated_on'   => $row[ 'updated_on' ]
        )
    );
    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $responseData );
}


/**
 * Saves (creates or replaces) the signed-in user's unfinished Record Sale form
 */
function appstv_sale_save_draft(){
    checkAuthorizationForFunction( __FUNCTION__ );

    $draft_data = (string) request( 'draft_data' );
    $serial_count = (int) request( 'serial_count' );
    if( strlen( $draft_data ) > APPSTV_SALE_MAX_DRAFT_BYTES || !is_array( json_decode( $draft_data, true ) ) ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "The draft could not be saved (too large or not valid)." );
        return;
    }

    $user_id = appstv_sale_user_id();
    if( $user_id === '' ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Please sign in again to save the draft." );
        return;
    }

    $e_user_id = escape_string( $user_id );
    $e_data    = escape_string( $draft_data );
    $now       = create_iso_8601_datetime();
    $sql = "INSERT INTO sale_drafts ( user_id, draft_data, serial_count, created_on, updated_on )
            VALUES ( '$e_user_id', '$e_data', $serial_count, '$now', '$now' )
            ON DUPLICATE KEY UPDATE draft_data = VALUES( draft_data ), serial_count = VALUES( serial_count ), updated_on = VALUES( updated_on )";
    if( insertQuery( $sql ) === null ){
        return;                                                // insertQuery already echoed the error
    }

    $responseData = array(
        'message' => "Draft saved.",
        'data'    => array( 'updated_on' => $now )
    );
    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $responseData );
}


/**
 * Deletes the signed-in user's unfinished Record Sale form
 */
function appstv_sale_discard_draft(){
    checkAuthorizationForFunction( __FUNCTION__ );

    $e_user_id = escape_string( appstv_sale_user_id() );
    deleteQuery( "DELETE FROM sale_drafts WHERE user_id = '$e_user_id'" );

    $responseData = array( 'message' => "Draft discarded." );
    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $responseData );
}


/**
 * Saves a sale in one transaction: customer (new or existing), invoice (new, or an existing one of the same customer
 * after confirmation), every TV with its invoice link and warranty, optional extended warranty. Nothing is saved
 * when any part fails.
 */
function appstv_sale_save(){
    checkAuthorizationForFunction( __FUNCTION__ );
    global $con;

    $sale = json_decode( (string) request( 'sale' ), true );
    if( !is_array( $sale ) ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, array( 'message' => "The form data was not received. Please try again." ) );
        return;
    }

    // ── 1. Validate the form ─────────────────────────────────────
    $errors = array();                                         // Messages for the user

    $customer_mode = isset( $sale[ 'customer_mode' ] ) ? $sale[ 'customer_mode' ] : '';
    $customer_id   = trim( (string) ( isset( $sale[ 'customer_id' ] ) ? $sale[ 'customer_id' ] : '' ) );
    $new_customer  = ( isset( $sale[ 'new_customer' ] ) && is_array( $sale[ 'new_customer' ] ) ) ? $sale[ 'new_customer' ] : array();
    $nc = array();                                             // Cleaned new customer fields
    foreach( array( 'company_name' => 200, 'first_name' => 100, 'last_name' => 100, 'primary_email' => 200, 'primary_mobile' => 20 ) as $field => $max ){
        $nc[ $field ] = trim( (string) ( isset( $new_customer[ $field ] ) ? $new_customer[ $field ] : '' ) );
        if( strlen( $nc[ $field ] ) > $max ){ $errors[] = "Customer " . str_replace( '_', ' ', $field ) . " is too long (max $max)."; }
    }
    if( $customer_mode === 'existing' ){
        if( $customer_id === '' ){ $errors[] = "Choose the customer."; }
    }
    else if( $customer_mode === 'new' ){
        if( $nc[ 'company_name' ] === '' && $nc[ 'first_name' ] === '' ){ $errors[] = "Enter the customer's company name or first name."; }
        if( $nc[ 'primary_email' ] !== '' && !filter_var( $nc[ 'primary_email' ], FILTER_VALIDATE_EMAIL ) ){ $errors[] = "The customer email is not valid."; }
        if( $nc[ 'primary_mobile' ] !== '' && !preg_match( '/^[0-9+\-() ]{6,20}$/', $nc[ 'primary_mobile' ] ) ){ $errors[] = "The customer mobile number is not valid."; }
    }
    else {
        $errors[] = "Choose an existing customer or add a new one.";
    }

    $invoice_no = trim( (string) ( isset( $sale[ 'invoice_no' ] ) ? $sale[ 'invoice_no' ] : '' ) );
    if( !preg_match( '/^[A-Za-z0-9][A-Za-z0-9 \/\-_.#]{0,49}$/', $invoice_no ) ){
        $errors[] = "Enter the invoice number (letters, digits, space / - _ . #, up to 50).";
    }
    $invoice_date   = appstv_sale_parse_date( isset( $sale[ 'invoice_date' ] ) ? $sale[ 'invoice_date' ] : '' );
    $warranty_start = appstv_sale_parse_date( isset( $sale[ 'warranty_start' ] ) ? $sale[ 'warranty_start' ] : '' );
    if( $invoice_date === null ){ $errors[] = "Enter the invoice date."; }
    if( $warranty_start === null ){ $errors[] = "Enter the delivery date (warranty start)."; }

    $warranty_months = (int) ( isset( $sale[ 'warranty_months' ] ) ? $sale[ 'warranty_months' ] : 0 );
    $extended_years  = (int) ( isset( $sale[ 'extended_years' ] ) ? $sale[ 'extended_years' ] : 0 );
    if( $warranty_months < 1 || $warranty_months > 120 ){ $errors[] = "Choose the warranty period."; }
    if( $extended_years < 0 || $extended_years > 10 ){ $errors[] = "The extended warranty must be 0 to 10 years."; }
    $remarks = trim( (string) ( isset( $sale[ 'remarks' ] ) ? $sale[ 'remarks' ] : '' ) );
    if( strlen( $remarks ) > 500 ){ $errors[] = "Remarks are too long (max 500)."; }
    $add_to_existing  = !empty( $sale[ 'add_to_existing' ] );
    $confirm_warnings = !empty( $sale[ 'confirm_warnings' ] );

    // Product lines
    $lines = array();
    $total = 0;
    $products = array();                                       // product_id => [ name, has_sn ]
    $raw_lines = ( isset( $sale[ 'lines' ] ) && is_array( $sale[ 'lines' ] ) ) ? $sale[ 'lines' ] : array();
    foreach( $raw_lines as $n => $raw_line ){
        $product_id = trim( (string) ( isset( $raw_line[ 'product_id' ] ) ? $raw_line[ 'product_id' ] : '' ) );
        $serials = array();
        foreach( ( isset( $raw_line[ 'serials' ] ) && is_array( $raw_line[ 'serials' ] ) ) ? $raw_line[ 'serials' ] : array() as $raw_sn ){
            $sn = appstv_sale_clean_serial( $raw_sn );
            if( $sn !== '' ){ $serials[] = $sn; }
        }
        if( count( $serials ) === 0 ){ continue; }             // Empty lines are ignored
        if( $product_id === '' ){ $errors[] = "Choose the TV model of product line " . ( $n + 1 ) . "."; continue; }
        if( !isset( $products[ $product_id ] ) ){
            $e_pid = escape_string( $product_id );
            $row = mysqli_fetch_assoc( selectQuery( "SELECT name, has_sn FROM products WHERE product_id = '$e_pid' LIMIT 1" ) );
            if( $row === null ){ $errors[] = "The TV model of product line " . ( $n + 1 ) . " does not exist."; continue; }
            $products[ $product_id ] = $row;
        }
        $lines[] = array( 'product_id' => $product_id, 'serials' => $serials );
        $total += count( $serials );
    }
    if( $total === 0 ){ $errors[] = "Add at least one serial number."; }
    if( $total > APPSTV_SALE_MAX_SERIALS ){ $errors[] = "One sale can hold up to " . APPSTV_SALE_MAX_SERIALS . " TVs."; }

    if( count( $errors ) > 0 ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, array( 'message' => implode( "\n", $errors ) ) );
        return;
    }

    // Warranty: start = delivery date, period in days up to start + N months; extended starts when it ends
    $warranty_end = clone $warranty_start;
    $warranty_end->modify( '+' . $warranty_months . ' months' );
    $warranty_days = (int) $warranty_start->diff( $warranty_end )->days;
    $extended_days = 0;
    if( $extended_years > 0 ){
        $extended_end = clone $warranty_end;
        $extended_end->modify( '+' . $extended_years . ' years' );
        $extended_days = (int) $warranty_end->diff( $extended_end )->days;
    }

    $user_id = appstv_sale_user_id();
    $user_row = mysqli_fetch_assoc( selectQuery( "SELECT fname, lname FROM users WHERE user_id = '" . escape_string( $user_id ) . "' LIMIT 1" ) );
    $user_name = ( $user_row !== null ) ? trim( $user_row[ 'fname' ] . ' ' . $user_row[ 'lname' ] ) : '';
    $warranty_remarks = 'Record Sale' . ( $user_name !== '' ? ' by ' . $user_name : '' ) . ( $remarks !== '' ? ': ' . $remarks : '' );

    // ── 2. Save everything in one transaction ────────────────────
    connect();
    $now = create_iso_8601_datetime();
    $start_text = $warranty_start->format( 'Y-m-d' ) . ' 00:00:00';
    $ext_start_text = $warranty_end->format( 'Y-m-d' ) . ' 00:00:00';

    try {
        mysqli_begin_transaction( $con );

        // 2a. Customer
        if( $customer_mode === 'existing' ){
            $e_cid = escape_string( $customer_id );
            $row = mysqli_fetch_assoc( appstv_sale_db( "SELECT customer_id FROM customers WHERE customer_id = '$e_cid' LIMIT 1" ) );
            if( $row === null ){
                throw new AppstvSaleRuleException( "The chosen customer no longer exists." );
            }
        }
        else {
            $customer_id = appstv_sale_unique_id( 'customers', 'customer_id', 'customer' );
            $e = array_map( 'escape_string', $nc );
            appstv_sale_db( "INSERT INTO customers ( customer_id, customer_source, user_id, title, company_name, first_name, middle_name, last_name,
                                                    primary_email, alternate_email, created_at, updated_at, state, primary_mobile, alternate_mobile, currency )
                             VALUES ( '$customer_id', '" . APPSTV_SALE_SOURCE_DIRECT . "', '', '', '{$e['company_name']}', '{$e['first_name']}', '', '{$e['last_name']}',
                                      '{$e['primary_email']}', '', '$now', '', '', '{$e['primary_mobile']}', '', '' )" );
        }
        $e_customer_id = escape_string( $customer_id );

        // 2b. Invoice: a new one, or the same customer's existing one after confirmation
        $e_invoice_no = escape_string( $invoice_no );
        $existing = array();
        $result = appstv_sale_db( "SELECT invoice_id, customer_id FROM order_invoices WHERE invoice_no = '$e_invoice_no' ORDER BY id FOR UPDATE" );
        while( ( $row = mysqli_fetch_assoc( $result ) ) !== null ){ $existing[] = $row; }
        $invoice_id = null;
        foreach( $existing as $row ){
            if( (string) $row[ 'customer_id' ] === $customer_id ){ $invoice_id = $row[ 'invoice_id' ]; break; }
        }
        if( count( $existing ) > 0 && $invoice_id === null ){
            throw new AppstvSaleRuleException( "Invoice $invoice_no already belongs to another customer. Check the invoice number." );
        }
        if( $invoice_id !== null && !$add_to_existing ){
            throw new AppstvSaleRuleException( "Invoice $invoice_no already exists for this customer. Confirm adding the TVs to it." );
        }
        $invoice_created = false;
        if( $invoice_id === null ){
            $invoice_id = appstv_sale_unique_id( 'order_invoices', 'invoice_id', 'ten' );
            $invoice_date_text = $invoice_date->format( 'Y-m-d' ) . ' 00:00:00';
            appstv_sale_db( "INSERT INTO order_invoices ( invoice_id, invoice_no, invoice_date, customer_id )
                             VALUES ( '$invoice_id', '$e_invoice_no', '$invoice_date_text', '$e_customer_id' )" );
            $invoice_created = true;
        }

        // 2c. Decide each TV (rows locked so two people cannot take the same stock record)
        $all_serials = array();
        foreach( $lines as $line ){ $all_serials = array_merge( $all_serials, $line[ 'serials' ] ); }
        $plan = appstv_sale_plan( $lines, appstv_sale_read_serial_records( $all_serials, true ) );
        if( $plan[ 'warnings' ] > 0 && !$confirm_warnings ){
            throw new AppstvSaleRuleException( $plan[ 'warnings' ] . " serial number(s) have warnings. Check them and confirm they are different TVs." );
        }

        // 2d. TVs, invoice links, warranties
        $e_remarks = escape_string( $warranty_remarks );
        $counts = array( 'new' => 0, 'stock' => 0, 'extended' => 0 );
        foreach( $plan[ 'items' ] as $item ){
            $e_pid = escape_string( $item[ 'product_id' ] );
            if( $item[ 'action' ] === 'stock' ){
                $product_sn_id = $item[ 'product_sn_id' ];
                appstv_sale_db( "UPDATE product_sn SET allotted_to_customer = 1 WHERE product_sn_id = '" . escape_string( $product_sn_id ) . "' AND allotted_to_customer = 0" );
                if( mysqli_affected_rows( $con ) !== 1 ){
                    throw new AppstvSaleRuleException( "Serial number {$item['serial']} was taken by someone else just now. Please save again." );
                }
                $counts[ 'stock' ]++;
            }
            else {
                $product_sn_id = appstv_sale_unique_id( 'product_sn', 'product_sn_id', 'product_sn' );
                $e_sn = escape_string( $item[ 'serial' ] );
                appstv_sale_db( "INSERT INTO product_sn ( product_sn_id, product_id, serial_number, created_on, allotted_to_customer )
                                 VALUES ( '$product_sn_id', '$e_pid', '$e_sn', '$now', 1 )" );
                $counts[ 'new' ]++;
            }

            $ipsn_id = appstv_sale_unique_id( 'invoice_product_sn', 'ipsn_id', 'ten' );
            appstv_sale_db( "INSERT INTO invoice_product_sn ( ipsn_id, invoice_id, product_sn_id ) VALUES ( '$ipsn_id', '$invoice_id', '$product_sn_id' )" );

            $psnw_id = appstv_sale_unique_id( 'product_sn_warranty', 'psnw_id', 'ten' );
            appstv_sale_db( "INSERT INTO product_sn_warranty ( psnw_id, product_sn_id, warranty_start_date, warranty_period, remarks )
                             VALUES ( '$psnw_id', '$product_sn_id', '$start_text', '$warranty_days', '$e_remarks' )" );

            if( $extended_days > 0 ){
                $psnexw_id = appstv_sale_unique_id( 'product_sn_extended_warranty', 'psnexw_id', 'ten' );
                appstv_sale_db( "INSERT INTO product_sn_extended_warranty ( psnexw_id, psnw_id, extended_warranty_start_date, extended_warranty_period, remarks )
                                 VALUES ( '$psnexw_id', '$psnw_id', '$ext_start_text', '$extended_days', '$e_remarks' )" );
                $counts[ 'extended' ]++;
            }
        }

        // 2e. Models that now carry serial numbers
        foreach( $products as $product_id => $product ){
            if( (int) $product[ 'has_sn' ] === 0 ){
                appstv_sale_db( "UPDATE products SET has_sn = 1 WHERE product_id = '" . escape_string( $product_id ) . "'" );
            }
        }

        // 2f. The draft is finished
        appstv_sale_db( "DELETE FROM sale_drafts WHERE user_id = '" . escape_string( $user_id ) . "'" );

        mysqli_commit( $con );
    } catch( AppstvSaleRuleException $e ){                     // A rule stopped the save: the message is for the user
        mysqli_rollback( $con );
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, array( 'message' => $e->getMessage() . " Nothing was saved." ) );
        return;
    } catch( Throwable $e ){                                   // Database error: logged, not shown
        mysqli_rollback( $con );
        error_log( __FUNCTION__ . ': ' . $e->getMessage() );
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, array( 'message' => "The sale could not be saved because of a server error. Nothing was saved." ) );
        return;
    }

    $responseData = array(
        'message' => "Sale saved: $total TV(s) on invoice $invoice_no.",
        'data'    => array(
            'customer_id'     => $customer_id,
            'invoice_id'      => $invoice_id,
            'invoice_no'      => $invoice_no,
            'invoice_created' => $invoice_created,
            'tv_count'        => $total,
            'new_records'     => $counts[ 'new' ],
            'stock_records'   => $counts[ 'stock' ],
            'extended'        => $counts[ 'extended' ],
            'warranty_end'    => $warranty_end->format( 'Y-m-d' )
        )
    );
    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $responseData );
}
