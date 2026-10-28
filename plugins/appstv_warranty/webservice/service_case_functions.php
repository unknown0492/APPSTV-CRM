<?php

/*
 * Service Cases (v1.0.7) - webservice actions of the Service Cases page and the service actions of the Warranty page
 *
 * A case = a customer's call: customer, contact, problem, zero or more TVs, an assigned staff member and a visit
 * date, a notes timeline. Status: open -> scheduled (visit date set) -> visited -> closed (outcome repaired /
 * replaced / no_fault / not_covered). Closing as repaired / replaced needs a service event recorded on one of the
 * case's TVs.
 *
 * Service events follow the existing model (v1.0.4 / v1.0.5): every event of a TV goes on the warranty it is
 * served under (its own, or the original TV's when it is a replacement) in product_warranty_replacements.
 * Repair = is_repairable 1, no new TV. Replacement = new_product_sn_id (an unsold stock record of that model is
 * reused, otherwise a new record; allotted, no invoice link of its own). Ref No = service invoice (order_invoices)
 * of the original customer, dated the day of service.
 */

define( 'APPSTV_CASE_STATUSES', array( 'open', 'scheduled', 'visited', 'closed' ) );
define( 'APPSTV_CASE_OUTCOMES', array( 'repaired', 'replaced', 'no_fault', 'not_covered' ) );
define( 'APPSTV_CASE_STAFF_ROLES', "'Administrator', 'APPSTV Admin'" );   // Roles whose users can be assigned to cases

/**
 * A rule stopped a service action; the message is meant for the user (database errors are never shown)
 */
class AppstvServiceRuleException extends Exception {}


/**
 * @description Runs one query and throws when it fails (inside transactions)
 * @param {string} $sql - The SQL
 * @returns {mysqli_result|bool} The result
 */
function appstv_case_db( $sql ){
    global $con;
    $result = mysqli_query( $con, $sql );
    if( $result === false ){
        throw new Exception( mysqli_error( $con ) );
    }
    return $result;
}

/**
 * @description Reads all rows of a query
 * @param {string} $sql - The SQL
 * @returns {array} Rows as associative arrays
 */
function appstv_case_rows( $sql ){
    $rows = array();
    $result = appstv_case_db( $sql );
    while( ( $row = mysqli_fetch_assoc( $result ) ) !== null ){ $rows[] = $row; }
    return $rows;
}

/**
 * @description Today in Singapore time as 'YYYY-MM-DD'
 * @returns {string}
 */
function appstv_case_today(){
    return ( new DateTime( 'now', new DateTimeZone( '+0800' ) ) )->format( 'Y-m-d' );
}

/**
 * @description The signed-in user's user_id (from the access token)
 * @returns {string} user_id, '' when unknown
 */
function appstv_case_user_id(){
    global $globalAccessToken;
    $payload = getJWTPayload( $globalAccessToken );
    return isset( $payload[ 'user_id' ] ) ? (string) $payload[ 'user_id' ] : '';
}

/**
 * @description Checks a 'YYYY-MM-DD' date
 * @param {string} $value - Date text
 * @returns {bool} True when it is a real date
 */
function appstv_case_valid_date( $value ){
    $date = DateTime::createFromFormat( '!Y-m-d', (string) $value );
    return ( $date !== false && $date->format( 'Y-m-d' ) === $value );
}

/**
 * @description Makes a 10 character ID (dd + 4 + 4 digits) not used yet in $table.$column
 * @param {string} $table - Table
 * @param {string} $column - ID column
 * @param {string} $shape - 'ten' or 'product_sn' (dd + millis + 5 digits)
 * @returns {string} The ID
 */
function appstv_case_unique_id( $table, $column, $shape = 'ten' ){
    for( $attempt = 0; $attempt < 200; $attempt++ ){
        $now = new DateTime( "now", new DateTimeZone( "+0800" ) );
        $id = ( $shape === 'product_sn' )
            ? $now->format( 'd' ) . $now->format( 'v' ) . rand( 10000, 99999 )
            : $now->format( 'd' ) . sprintf( "%04d", (int)( fmod( microtime( true ), 1 ) * 10000 ) ) . rand( 1000, 9999 );
        if( mysqli_num_rows( appstv_case_db( "SELECT 1 FROM `$table` WHERE `$column` = '$id' LIMIT 1" ) ) === 0 ){
            return $id;
        }
    }
    throw new Exception( "Could not make a unique ID for $table" );
}

/**
 * @description Display name of a customer row (company, else first + last name)
 * @param {array} $row - Has company_name, first_name, last_name
 * @returns {string}
 */
function appstv_case_customer_name( $row ){
    $company = trim( (string) ( isset( $row[ 'company_name' ] ) ? $row[ 'company_name' ] : '' ) );
    if( $company !== '' ){ return $company; }
    return trim( ( isset( $row[ 'first_name' ] ) ? $row[ 'first_name' ] : '' ) . ' ' . ( isset( $row[ 'last_name' ] ) ? $row[ 'last_name' ] : '' ) );
}

/**
 * @description Warranty chain of a TV: the warranty it is served under (its own, or the original TV's when it is a
 *              replacement), the original sale's customer, the TV in service now, the warranty dates and status
 * @param {string} $product_sn_id - The TV
 * @returns {array} { product_sn_id, serial_number, product_id, product_name, psnw_id, role, original_product_sn_id,
 *                    customer_id, customer_name, invoice_no, current_product_sn_id, current_serial_number, is_current,
 *                    warranty_start, warranty_expiry, extended_expiry, has_extended, warranty_status, events }
 */
function appstv_case_tv_chain( $product_sn_id ){
    $e_id = escape_string( $product_sn_id );
    $tv = mysqli_fetch_assoc( appstv_case_db( "SELECT ps.product_sn_id, ps.serial_number, ps.product_id, p.name AS product_name
                                                FROM product_sn ps LEFT JOIN products p ON p.product_id = ps.product_id
                                                WHERE ps.product_sn_id = '$e_id' LIMIT 1" ) );
    if( $tv === null ){ return null; }

    // Warranty: the TV's own, else the one it was given out under as a replacement (latest)
    $warranty = mysqli_fetch_assoc( appstv_case_db( "SELECT * FROM product_sn_warranty WHERE product_sn_id = '$e_id' ORDER BY id DESC LIMIT 1" ) );
    $role = 'original';
    if( $warranty === null ){
        $warranty = mysqli_fetch_assoc( appstv_case_db( "SELECT w.* FROM product_sn_warranty w
                                                         JOIN product_warranty_replacements r ON r.psnw_id = w.psnw_id
                                                         WHERE r.new_product_sn_id = '$e_id' ORDER BY r.date_of_service DESC, r.id DESC LIMIT 1" ) );
        $role = ( $warranty === null ) ? 'none' : 'replacement';
    }

    $chain = array(
        'product_sn_id' => $tv[ 'product_sn_id' ], 'serial_number' => $tv[ 'serial_number' ],
        'product_id' => $tv[ 'product_id' ], 'product_name' => (string) $tv[ 'product_name' ],
        'psnw_id' => null, 'role' => $role, 'original_product_sn_id' => $tv[ 'product_sn_id' ],
        'customer_id' => '', 'customer_name' => '', 'invoice_no' => '',
        'current_product_sn_id' => $tv[ 'product_sn_id' ], 'current_serial_number' => $tv[ 'serial_number' ], 'is_current' => true,
        'warranty_start' => null, 'warranty_expiry' => null, 'extended_expiry' => null, 'has_extended' => false,
        'warranty_status' => 'No Warranty Found', 'events' => array()
    );

    // Customer: the sale of the original TV (or of this TV when it has no warranty)
    $sale_of = ( $warranty !== null ) ? $warranty[ 'product_sn_id' ] : $tv[ 'product_sn_id' ];
    $sale = mysqli_fetch_assoc( appstv_case_db( "SELECT oi.invoice_no, oi.customer_id, c.company_name, c.first_name, c.last_name
                                                  FROM invoice_product_sn ipsn
                                                  JOIN order_invoices oi ON oi.invoice_id = ipsn.invoice_id
                                                  LEFT JOIN customers c ON c.customer_id = oi.customer_id
                                                  WHERE ipsn.product_sn_id = '" . escape_string( $sale_of ) . "' ORDER BY ipsn.id LIMIT 1" ) );
    if( $sale !== null ){
        $chain[ 'customer_id' ] = (string) $sale[ 'customer_id' ];
        $chain[ 'customer_name' ] = appstv_case_customer_name( $sale );
        $chain[ 'invoice_no' ] = (string) $sale[ 'invoice_no' ];
    }
    if( $warranty === null ){ return $chain; }

    $chain[ 'psnw_id' ] = $warranty[ 'psnw_id' ];
    $chain[ 'original_product_sn_id' ] = $warranty[ 'product_sn_id' ];
    $e_psnw = escape_string( $warranty[ 'psnw_id' ] );

    // Dates and status (same rules as the warranty page)
    if( !empty( $warranty[ 'warranty_start_date' ] ) && !empty( $warranty[ 'warranty_period' ] ) ){
        $expiry = new DateTime( $warranty[ 'warranty_start_date' ] );
        $expiry->modify( '+' . intval( $warranty[ 'warranty_period' ] ) . ' days' );
        $chain[ 'warranty_start' ] = substr( $warranty[ 'warranty_start_date' ], 0, 10 );
        $chain[ 'warranty_expiry' ] = $expiry->format( 'Y-m-d' );
    }
    $ext = mysqli_fetch_assoc( appstv_case_db( "SELECT * FROM product_sn_extended_warranty WHERE psnw_id = '$e_psnw' ORDER BY id LIMIT 1" ) );
    if( $ext !== null ){
        $chain[ 'has_extended' ] = true;
        if( !empty( $ext[ 'extended_warranty_start_date' ] ) && !empty( $ext[ 'extended_warranty_period' ] ) ){
            $ext_expiry = new DateTime( $ext[ 'extended_warranty_start_date' ] );
            $ext_expiry->modify( '+' . intval( $ext[ 'extended_warranty_period' ] ) . ' days' );
            $chain[ 'extended_expiry' ] = $ext_expiry->format( 'Y-m-d' );
        }
    }
    $today = appstv_case_today();
    if( $chain[ 'warranty_expiry' ] !== null ){
        if( $today <= $chain[ 'warranty_expiry' ] ){ $chain[ 'warranty_status' ] = 'Active'; }
        else if( $chain[ 'extended_expiry' ] !== null && $today <= $chain[ 'extended_expiry' ] ){ $chain[ 'warranty_status' ] = 'Active (Extended)'; }
        else { $chain[ 'warranty_status' ] = 'Expired'; }
    }

    // Events, oldest first; the TV in service now is the last replacement (or the original)
    $events = appstv_case_rows( "SELECT r.pwrp_id, r.is_repairable, r.date_of_service, r.new_product_sn_id, r.remarks,
                                        ns.serial_number AS new_serial_number, ni.invoice_no AS ref_no
                                 FROM product_warranty_replacements r
                                 LEFT JOIN product_sn ns ON ns.product_sn_id = r.new_product_sn_id
                                 LEFT JOIN order_invoices ni ON ni.invoice_id = r.new_invoice_id
                                 WHERE r.psnw_id = '$e_psnw' ORDER BY r.date_of_service, r.id" );
    $current_id = $warranty[ 'product_sn_id' ];
    $current_sn = null;
    foreach( $events as $event ){
        if( !empty( $event[ 'new_product_sn_id' ] ) ){
            $current_id = $event[ 'new_product_sn_id' ];
            $current_sn = $event[ 'new_serial_number' ];
        }
        $chain[ 'events' ][] = array(
            'pwrp_id' => $event[ 'pwrp_id' ], 'date' => substr( (string) $event[ 'date_of_service' ], 0, 10 ),
            'type' => empty( $event[ 'new_product_sn_id' ] ) ? 'repair' : 'replacement',
            'new_serial_number' => $event[ 'new_serial_number' ], 'ref_no' => $event[ 'ref_no' ], 'remarks' => $event[ 'remarks' ]
        );
    }
    if( $current_sn === null ){
        $row = mysqli_fetch_assoc( appstv_case_db( "SELECT serial_number FROM product_sn WHERE product_sn_id = '" . escape_string( $current_id ) . "' LIMIT 1" ) );
        $current_sn = $row ? $row[ 'serial_number' ] : '';
    }
    $chain[ 'current_product_sn_id' ] = $current_id;
    $chain[ 'current_serial_number' ] = $current_sn;
    $chain[ 'is_current' ] = ( $current_id === $tv[ 'product_sn_id' ] );
    return $chain;
}

/**
 * @description Adds a line to a case's timeline
 * @param {int} $case_id - The case
 * @param {string} $type - created | note | status | assign | visit | tv | service | close | reopen
 * @param {string} $text - What happened
 * @returns {void}
 */
function appstv_case_log( $case_id, $type, $text ){
    $case_id = (int) $case_id;
    $e_user = escape_string( appstv_case_user_id() );
    $e_type = escape_string( $type );
    $e_text = escape_string( $text );
    $now = create_iso_8601_datetime();
    appstv_case_db( "INSERT INTO service_case_notes ( case_id, user_id, note_type, note, created_on ) VALUES ( $case_id, '$e_user', '$e_type', '$e_text', '$now' )" );
    appstv_case_db( "UPDATE service_cases SET updated_on = '$now' WHERE id = $case_id" );
}

/**
 * @description Name of a user for the timeline
 * @param {string} $user_id - users.user_id
 * @returns {string} "First Last", or '' when unknown
 */
function appstv_case_user_name( $user_id ){
    if( $user_id === '' || $user_id === null ){ return ''; }
    $row = mysqli_fetch_assoc( appstv_case_db( "SELECT fname, lname FROM users WHERE user_id = '" . escape_string( $user_id ) . "' LIMIT 1" ) );
    return $row ? trim( $row[ 'fname' ] . ' ' . $row[ 'lname' ] ) : '';
}

/**
 * @description Checks that a user may be assigned to cases
 * @param {string} $user_id - users.user_id ('' = nobody)
 * @returns {bool}
 */
function appstv_case_is_staff( $user_id ){
    if( $user_id === '' ){ return true; }
    $e = escape_string( $user_id );
    return mysqli_num_rows( appstv_case_db( "SELECT 1 FROM users u JOIN roles r ON r.role_id = u.role_id
                                             WHERE u.user_id = '$e' AND r.role_name IN ( " . APPSTV_CASE_STAFF_ROLES . " ) LIMIT 1" ) ) > 0;
}

/**
 * @description Reads one case row (locked when asked)
 * @param {int} $case_id - The case
 * @param {bool} $lock - True to lock it inside a transaction
 * @returns {array} The row
 * @throws AppstvServiceRuleException when it does not exist
 */
function appstv_case_read( $case_id, $lock = false ){
    $row = mysqli_fetch_assoc( appstv_case_db( "SELECT * FROM service_cases WHERE id = " . (int) $case_id . ( $lock ? " FOR UPDATE" : "" ) ) );
    if( $row === null ){ throw new AppstvServiceRuleException( "This case does not exist." ); }
    return $row;
}

/**
 * @description Runs a function inside a transaction and echoes its result as the JSON answer
 * @param {string} $for - Webservice action name
 * @param {callable} $work - Returns [ message, data ]; throws AppstvServiceRuleException for user errors
 * @returns {void}
 */
function appstv_case_transaction( $for, $work ){
    global $con;
    connect();
    try {
        mysqli_begin_transaction( $con );
        list( $message, $data ) = $work();
        mysqli_commit( $con );
    } catch( AppstvServiceRuleException $e ){
        mysqli_rollback( $con );
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, $for, array( 'message' => $e->getMessage() ) );
        return;
    } catch( Throwable $e ){
        mysqli_rollback( $con );
        error_log( $for . ': ' . $e->getMessage() );
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, $for, array( 'message' => "A server error occurred. Nothing was saved." ) );
        return;
    }
    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, $for, array( 'message' => $message, 'data' => $data ) );
}

/**
 * @description Answers a read-only action, hiding database errors
 * @param {string} $for - Webservice action name
 * @param {callable} $work - Returns [ message, data ]; throws AppstvServiceRuleException for user errors
 * @returns {void}
 */
function appstv_case_answer( $for, $work ){
    connect();
    try {
        list( $message, $data ) = $work();
    } catch( AppstvServiceRuleException $e ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, $for, array( 'message' => $e->getMessage() ) );
        return;
    } catch( Throwable $e ){
        error_log( $for . ': ' . $e->getMessage() );
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, $for, array( 'message' => "A server error occurred. Please try again." ) );
        return;
    }
    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, $for, array( 'message' => $message, 'data' => $data ) );
}

/**
 * @description Finds or creates the service invoice (Ref No) of a customer, dated the day of service
 * @param {string} $ref_no - Invoice number ('' = none)
 * @param {string} $customer_id - Customer of the original sale
 * @param {string} $date - 'YYYY-MM-DD'
 * @returns {string|null} invoice_id, null when there is no Ref No
 */
function appstv_case_service_invoice( $ref_no, $customer_id, $date ){
    if( $ref_no === '' ){ return null; }
    $e_no = escape_string( $ref_no );
    $rows = appstv_case_rows( "SELECT invoice_id, customer_id FROM order_invoices WHERE invoice_no = '$e_no' ORDER BY id FOR UPDATE" );
    foreach( $rows as $row ){
        if( (string) $row[ 'customer_id' ] === (string) $customer_id ){ return $row[ 'invoice_id' ]; }
    }
    if( count( $rows ) > 0 ){
        throw new AppstvServiceRuleException( "Ref No. $ref_no is an invoice of another customer. Check the number." );
    }
    $invoice_id = appstv_case_unique_id( 'order_invoices', 'invoice_id' );
    $e_cid = escape_string( $customer_id );
    appstv_case_db( "INSERT INTO order_invoices ( invoice_id, invoice_no, invoice_date, customer_id )
                     VALUES ( '$invoice_id', '$e_no', '$date 00:00:00', " . ( $customer_id === '' ? "NULL" : "'$e_cid'" ) . " )" );
    return $invoice_id;
}


/**
 * Users that cases can be assigned to (Administrator and APPSTV Admin)
 */
function appstv_case_staff(){
    checkAuthorizationForFunction( __FUNCTION__ );
    appstv_case_answer( __FUNCTION__, function(){
        $rows = appstv_case_rows( "SELECT u.user_id, u.fname, u.lname FROM users u JOIN roles r ON r.role_id = u.role_id
                                   WHERE r.role_name IN ( " . APPSTV_CASE_STAFF_ROLES . " ) ORDER BY u.fname, u.lname" );
        $staff = array();
        foreach( $rows as $row ){
            $staff[] = array( 'user_id' => $row[ 'user_id' ], 'name' => trim( $row[ 'fname' ] . ' ' . $row[ 'lname' ] ) );
        }
        return array( count( $staff ) . " staff.", array( 'staff' => $staff, 'me' => appstv_case_user_id(), 'today' => appstv_case_today() ) );
    } );
}


/**
 * Finds the TVs with a serial number (or one TV by product_sn_id) with their warranty chain and customer
 */
function appstv_case_find_tv(){
    checkAuthorizationForFunction( __FUNCTION__ );
    appstv_case_answer( __FUNCTION__, function(){
        $product_sn_id = trim( (string) request( 'product_sn_id' ) );
        $serial = strtoupper( preg_replace( '/[^A-Za-z0-9]/', '', (string) request( 'serial_number' ) ) );
        if( $product_sn_id !== '' ){
            $ids = array( $product_sn_id );
        } else {
            if( $serial === '' || strlen( $serial ) > 50 ){ throw new AppstvServiceRuleException( "Enter a serial number (letters and digits)." ); }
            $ids = array();
            foreach( appstv_case_rows( "SELECT product_sn_id FROM product_sn WHERE serial_number = '" . escape_string( $serial ) . "' ORDER BY id" ) as $row ){
                $ids[] = $row[ 'product_sn_id' ];
            }
        }
        $tvs = array();
        foreach( $ids as $id ){
            $chain = appstv_case_tv_chain( $id );
            if( $chain !== null ){ $tvs[] = $chain; }
        }
        return array( count( $tvs ) . " TV(s) found.", array( 'serial_number' => $serial, 'tvs' => $tvs ) );
    } );
}


/**
 * Lists cases with filters (status, assigned, search) and the counts for the summary chips
 */
function appstv_case_list(){
    checkAuthorizationForFunction( __FUNCTION__ );
    appstv_case_answer( __FUNCTION__, function(){
        $status   = (string) request( 'status' );             // active (default) | open | scheduled | visited | closed | all | overdue | today
        $assigned = (string) request( 'assigned' );           // '' anyone | me | none | <user_id>
        $q        = trim( (string) request( 'q' ) );
        $today    = appstv_case_today();
        $me       = appstv_case_user_id();
        $where    = array( '1=1' );

        if( $status === '' || $status === 'active' ){ $where[] = "c.status <> 'closed'"; }
        else if( $status === 'overdue' ){ $where[] = "c.status IN ( 'open', 'scheduled' ) AND c.visit_date <> '' AND c.visit_date < '$today'"; }
        else if( $status === 'today' ){ $where[] = "c.status IN ( 'open', 'scheduled' ) AND c.visit_date = '$today'"; }
        else if( in_array( $status, APPSTV_CASE_STATUSES ) ){ $where[] = "c.status = '$status'"; }

        if( $assigned === 'me' ){ $where[] = "c.assigned_user_id = '" . escape_string( $me ) . "'"; }
        else if( $assigned === 'none' ){ $where[] = "( c.assigned_user_id IS NULL OR c.assigned_user_id = '' )"; }
        else if( $assigned !== '' ){ $where[] = "c.assigned_user_id = '" . escape_string( $assigned ) . "'"; }

        if( $q !== '' ){
            $e_q = escape_string( $q );
            $case_no = (int) preg_replace( '/\D/', '', $q );
            $where[] = "( cu.company_name LIKE '%$e_q%' OR CONCAT( cu.first_name, ' ', cu.last_name ) LIKE '%$e_q%' OR c.contact_name LIKE '%$e_q%'
                          OR c.contact_phone LIKE '%$e_q%' OR c.problem LIKE '%$e_q%'
                          OR EXISTS ( SELECT 1 FROM service_case_tvs t WHERE t.case_id = c.id AND t.serial_number LIKE '%$e_q%' )"
                     . ( $case_no > 0 ? " OR c.id = $case_no" : "" ) . " )";
        }

        // Due visits first (overdue, today, upcoming), then cases without a visit date, closed ones last
        $sql = "SELECT c.*, cu.company_name, cu.first_name, cu.last_name, u.fname AS a_fname, u.lname AS a_lname,
                       ( SELECT GROUP_CONCAT( t.serial_number ORDER BY t.id SEPARATOR ', ' ) FROM service_case_tvs t WHERE t.case_id = c.id ) AS serials,
                       ( SELECT COUNT(*) FROM service_case_notes n WHERE n.case_id = c.id AND n.note_type = 'note' ) AS note_count
                FROM service_cases c
                LEFT JOIN customers cu ON cu.customer_id = c.customer_id
                LEFT JOIN users u ON CONVERT( u.user_id USING utf8mb4 ) COLLATE utf8mb4_unicode_ci = c.assigned_user_id   -- users is utf16
                WHERE " . implode( ' AND ', $where ) . "
                ORDER BY ( c.status = 'closed' ),
                         ( CASE WHEN c.status IN ( 'open', 'scheduled' ) AND c.visit_date <> '' THEN 0 ELSE 1 END ),
                         c.visit_date, c.visit_time, c.id DESC
                LIMIT 300";
        $cases = array();
        foreach( appstv_case_rows( $sql ) as $row ){
            $due = '';
            if( in_array( $row[ 'status' ], array( 'open', 'scheduled' ) ) && $row[ 'visit_date' ] !== '' && $row[ 'visit_date' ] !== null ){
                $due = ( $row[ 'visit_date' ] < $today ) ? 'overdue' : ( $row[ 'visit_date' ] === $today ? 'today' : 'upcoming' );
            }
            $cases[] = array(
                'id' => (int) $row[ 'id' ], 'status' => $row[ 'status' ], 'outcome' => $row[ 'outcome' ],
                'customer_name' => appstv_case_customer_name( $row ), 'contact_name' => $row[ 'contact_name' ], 'contact_phone' => $row[ 'contact_phone' ],
                'problem' => $row[ 'problem' ], 'serials' => (string) $row[ 'serials' ],
                'assigned_user_id' => (string) $row[ 'assigned_user_id' ], 'assigned_name' => trim( $row[ 'a_fname' ] . ' ' . $row[ 'a_lname' ] ),
                'visit_date' => (string) $row[ 'visit_date' ], 'visit_time' => (string) $row[ 'visit_time' ], 'due' => $due,
                'created_on' => $row[ 'created_on' ], 'closed_on' => $row[ 'closed_on' ], 'note_count' => (int) $row[ 'note_count' ]
            );
        }

        $e_me = escape_string( $me );
        $counts = mysqli_fetch_assoc( appstv_case_db( "SELECT
                SUM( status IN ( 'open', 'scheduled' ) AND visit_date <> '' AND visit_date < '$today' ) AS overdue,
                SUM( status IN ( 'open', 'scheduled' ) AND visit_date = '$today' ) AS today,
                SUM( status <> 'closed' ) AS active,
                SUM( status <> 'closed' AND assigned_user_id = '$e_me' ) AS mine
            FROM service_cases" ) );
        foreach( $counts as $k => $v ){ $counts[ $k ] = (int) $v; }

        return array( count( $cases ) . " case(s).", array( 'cases' => $cases, 'counts' => $counts, 'today' => $today ) );
    } );
}


/**
 * Reads one case: details, its TVs with their warranty chain, and the timeline
 */
function appstv_case_get(){
    checkAuthorizationForFunction( __FUNCTION__ );
    appstv_case_answer( __FUNCTION__, function(){
        $case = appstv_case_read( request( 'case_id' ) );
        $id = (int) $case[ 'id' ];

        $customer = mysqli_fetch_assoc( appstv_case_db( "SELECT customer_id, company_name, first_name, last_name, primary_email, primary_mobile
                                                         FROM customers WHERE customer_id = '" . escape_string( $case[ 'customer_id' ] ) . "' LIMIT 1" ) );
        $tvs = array();
        foreach( appstv_case_rows( "SELECT * FROM service_case_tvs WHERE case_id = $id ORDER BY id" ) as $row ){
            $tvs[] = array(
                'id' => (int) $row[ 'id' ], 'serial_number' => $row[ 'serial_number' ], 'product_sn_id' => (string) $row[ 'product_sn_id' ],
                'pwrp_id' => (string) $row[ 'pwrp_id' ],
                'chain' => ( $row[ 'product_sn_id' ] !== null && $row[ 'product_sn_id' ] !== '' ) ? appstv_case_tv_chain( $row[ 'product_sn_id' ] ) : null
            );
        }
        $notes = array();
        foreach( appstv_case_rows( "SELECT n.*, u.fname, u.lname FROM service_case_notes n LEFT JOIN users u ON CONVERT( u.user_id USING utf8mb4 ) COLLATE utf8mb4_unicode_ci = n.user_id
                                    WHERE n.case_id = $id ORDER BY n.id DESC" ) as $row ){
            $notes[] = array( 'type' => $row[ 'note_type' ], 'note' => $row[ 'note' ], 'by' => trim( $row[ 'fname' ] . ' ' . $row[ 'lname' ] ), 'on' => $row[ 'created_on' ] );
        }

        $data = array(
            'case' => array(
                'id' => $id, 'status' => $case[ 'status' ], 'outcome' => $case[ 'outcome' ],
                'customer_id' => $case[ 'customer_id' ], 'customer_name' => $customer ? appstv_case_customer_name( $customer ) : '(customer not found)',
                'customer_email' => $customer ? $customer[ 'primary_email' ] : '', 'customer_mobile' => $customer ? $customer[ 'primary_mobile' ] : '',
                'contact_name' => $case[ 'contact_name' ], 'contact_phone' => $case[ 'contact_phone' ], 'problem' => $case[ 'problem' ],
                'assigned_user_id' => (string) $case[ 'assigned_user_id' ], 'visit_date' => (string) $case[ 'visit_date' ], 'visit_time' => (string) $case[ 'visit_time' ],
                'created_by' => appstv_case_user_name( $case[ 'created_by' ] ), 'created_on' => $case[ 'created_on' ],
                'closed_by' => appstv_case_user_name( $case[ 'closed_by' ] ), 'closed_on' => $case[ 'closed_on' ]
            ),
            'tvs' => $tvs,
            'notes' => $notes,
            'today' => appstv_case_today()
        );
        return array( "Case SC-$id.", $data );
    } );
}


/**
 * @description Validates and normalises the visit fields
 * @param {string} $date - 'YYYY-MM-DD' or ''
 * @param {string} $time - 'HH:MM' or ''
 * @returns {array} [ date, time ]
 */
function appstv_case_visit_fields( $date, $time ){
    $date = trim( (string) $date );
    $time = trim( (string) $time );
    if( $date !== '' && !appstv_case_valid_date( $date ) ){ throw new AppstvServiceRuleException( "The visit date is not valid." ); }
    if( $time !== '' && !preg_match( '/^([01]\d|2[0-3]):[0-5]\d$/', $time ) ){ throw new AppstvServiceRuleException( "The visit time is not valid (HH:MM)." ); }
    if( $date === '' ){ $time = ''; }
    return array( $date, $time );
}

/**
 * @description Adds a TV to a case (by product_sn_id, or as a serial number not in the system)
 * @param {int} $case_id - The case
 * @param {string} $product_sn_id - The TV ('' when unknown)
 * @param {string} $serial - Serial number (used when product_sn_id is '')
 * @returns {string} The serial number added
 */
function appstv_case_insert_tv( $case_id, $product_sn_id, $serial ){
    $case_id = (int) $case_id;
    if( $product_sn_id !== '' ){
        $row = mysqli_fetch_assoc( appstv_case_db( "SELECT serial_number FROM product_sn WHERE product_sn_id = '" . escape_string( $product_sn_id ) . "' LIMIT 1" ) );
        if( $row === null ){ throw new AppstvServiceRuleException( "That TV does not exist." ); }
        $serial = $row[ 'serial_number' ];
        if( mysqli_num_rows( appstv_case_db( "SELECT 1 FROM service_case_tvs WHERE case_id = $case_id AND product_sn_id = '" . escape_string( $product_sn_id ) . "'" ) ) > 0 ){
            throw new AppstvServiceRuleException( "TV $serial is already on this case." );
        }
    } else {
        $serial = strtoupper( preg_replace( '/[^A-Za-z0-9]/', '', (string) $serial ) );
        if( $serial === '' || strlen( $serial ) > 50 ){ throw new AppstvServiceRuleException( "Enter a serial number (letters and digits)." ); }
    }
    $e_sn = escape_string( $serial );
    $e_id = ( $product_sn_id === '' ) ? "NULL" : "'" . escape_string( $product_sn_id ) . "'";
    $now = create_iso_8601_datetime();
    appstv_case_db( "INSERT INTO service_case_tvs ( case_id, product_sn_id, serial_number, added_on ) VALUES ( $case_id, $e_id, '$e_sn', '$now' )" );
    return $serial;
}


/**
 * Creates a case: customer, contact, problem, TVs, assigned staff and visit date
 */
function appstv_case_create(){
    checkAuthorizationForFunction( __FUNCTION__ );
    appstv_case_transaction( __FUNCTION__, function(){
        $in = json_decode( (string) request( 'case' ), true );
        if( !is_array( $in ) ){ throw new AppstvServiceRuleException( "The form data was not received." ); }

        $customer_id = trim( (string) ( isset( $in[ 'customer_id' ] ) ? $in[ 'customer_id' ] : '' ) );
        $customer = mysqli_fetch_assoc( appstv_case_db( "SELECT customer_id FROM customers WHERE customer_id = '" . escape_string( $customer_id ) . "' LIMIT 1" ) );
        if( $customer_id === '' || $customer === null ){ throw new AppstvServiceRuleException( "Choose the customer." ); }

        $contact_name  = trim( (string) ( isset( $in[ 'contact_name' ] ) ? $in[ 'contact_name' ] : '' ) );
        $contact_phone = trim( (string) ( isset( $in[ 'contact_phone' ] ) ? $in[ 'contact_phone' ] : '' ) );
        $problem       = trim( (string) ( isset( $in[ 'problem' ] ) ? $in[ 'problem' ] : '' ) );
        $assigned      = trim( (string) ( isset( $in[ 'assigned_user_id' ] ) ? $in[ 'assigned_user_id' ] : '' ) );
        if( $problem === '' ){ throw new AppstvServiceRuleException( "Describe the problem." ); }
        if( strlen( $problem ) > 5000 || strlen( $contact_name ) > 200 || strlen( $contact_phone ) > 50 ){ throw new AppstvServiceRuleException( "A field is too long." ); }
        if( !appstv_case_is_staff( $assigned ) ){ throw new AppstvServiceRuleException( "The chosen staff member cannot be assigned." ); }
        list( $visit_date, $visit_time ) = appstv_case_visit_fields( isset( $in[ 'visit_date' ] ) ? $in[ 'visit_date' ] : '', isset( $in[ 'visit_time' ] ) ? $in[ 'visit_time' ] : '' );

        $status = ( $visit_date !== '' ) ? 'scheduled' : 'open';
        $now = create_iso_8601_datetime();
        $user = appstv_case_user_id();
        appstv_case_db( "INSERT INTO service_cases ( customer_id, contact_name, contact_phone, problem, status, assigned_user_id, visit_date, visit_time,
                                                    outcome, created_by, created_on, updated_on, closed_by, closed_on )
                         VALUES ( '" . escape_string( $customer_id ) . "', '" . escape_string( $contact_name ) . "', '" . escape_string( $contact_phone ) . "',
                                  '" . escape_string( $problem ) . "', '$status', '" . escape_string( $assigned ) . "', '$visit_date', '$visit_time',
                                  '', '" . escape_string( $user ) . "', '$now', '$now', '', '' )" );
        global $con;
        $case_id = (int) mysqli_insert_id( $con );

        $added = array();
        foreach( ( isset( $in[ 'tvs' ] ) && is_array( $in[ 'tvs' ] ) ) ? $in[ 'tvs' ] : array() as $tv ){
            $added[] = appstv_case_insert_tv( $case_id, trim( (string) ( isset( $tv[ 'product_sn_id' ] ) ? $tv[ 'product_sn_id' ] : '' ) ),
                                                        isset( $tv[ 'serial_number' ] ) ? $tv[ 'serial_number' ] : '' );
        }

        $text = 'Case opened' . ( count( $added ) ? ' for TV ' . implode( ', ', $added ) : '' );
        if( $assigned !== '' ){ $text .= '; assigned to ' . appstv_case_user_name( $assigned ); }
        if( $visit_date !== '' ){ $text .= '; visit ' . $visit_date . ( $visit_time !== '' ? ' ' . $visit_time : '' ); }
        appstv_case_log( $case_id, 'created', $text );

        return array( "Case SC-$case_id opened.", array( 'case_id' => $case_id ) );
    } );
}


/**
 * Updates a case: contact, problem, assigned staff, visit date / time, status (visited / reopen)
 */
function appstv_case_update(){
    checkAuthorizationForFunction( __FUNCTION__ );
    appstv_case_transaction( __FUNCTION__, function(){
        $case = appstv_case_read( request( 'case_id' ), true );
        $id = (int) $case[ 'id' ];
        $in = json_decode( (string) request( 'changes' ), true );
        if( !is_array( $in ) ){ throw new AppstvServiceRuleException( "No changes were received." ); }
        $set = array();
        $log = array();

        if( $case[ 'status' ] === 'closed' && !( isset( $in[ 'status' ] ) && $in[ 'status' ] === 'open' ) ){
            throw new AppstvServiceRuleException( "This case is closed. Reopen it first." );
        }

        foreach( array( 'contact_name' => 200, 'contact_phone' => 50, 'problem' => 5000 ) as $field => $max ){
            if( !array_key_exists( $field, $in ) ){ continue; }
            $value = trim( (string) $in[ $field ] );
            if( strlen( $value ) > $max ){ throw new AppstvServiceRuleException( "A field is too long." ); }
            if( $field === 'problem' && $value === '' ){ throw new AppstvServiceRuleException( "The problem cannot be empty." ); }
            if( $value !== (string) $case[ $field ] ){
                $set[] = "$field = '" . escape_string( $value ) . "'";
                $log[] = array( 'note', ( $field === 'problem' ? 'Problem changed to: ' : str_replace( '_', ' ', ucfirst( $field ) ) . ' changed to: ' ) . ( $value === '' ? '(empty)' : $value ) );
            }
        }

        if( array_key_exists( 'assigned_user_id', $in ) ){
            $assigned = trim( (string) $in[ 'assigned_user_id' ] );
            if( !appstv_case_is_staff( $assigned ) ){ throw new AppstvServiceRuleException( "The chosen staff member cannot be assigned." ); }
            if( $assigned !== (string) $case[ 'assigned_user_id' ] ){
                $set[] = "assigned_user_id = '" . escape_string( $assigned ) . "'";
                $log[] = array( 'assign', $assigned === '' ? 'Unassigned' : 'Assigned to ' . appstv_case_user_name( $assigned ) );
            }
        }

        $status = $case[ 'status' ];
        if( array_key_exists( 'visit_date', $in ) ){
            list( $date, $time ) = appstv_case_visit_fields( $in[ 'visit_date' ], isset( $in[ 'visit_time' ] ) ? $in[ 'visit_time' ] : '' );
            if( $date !== (string) $case[ 'visit_date' ] || $time !== (string) $case[ 'visit_time' ] ){
                $set[] = "visit_date = '$date'";
                $set[] = "visit_time = '$time'";
                $log[] = array( 'visit', $date === '' ? 'Visit date removed' : 'Visit set for ' . $date . ( $time !== '' ? ' ' . $time : '' ) );
                if( $status === 'open' && $date !== '' ){ $status = 'scheduled'; }
                if( $status === 'scheduled' && $date === '' ){ $status = 'open'; }
            }
        }

        if( isset( $in[ 'status' ] ) ){
            if( $in[ 'status' ] === 'visited' ){
                if( !in_array( $case[ 'status' ], array( 'open', 'scheduled' ) ) ){ throw new AppstvServiceRuleException( "Only an open or scheduled case can be marked as visited." ); }
                $status = 'visited';
                $log[] = array( 'status', 'Marked as visited' );
            } else if( $in[ 'status' ] === 'open' ){
                if( $case[ 'status' ] !== 'closed' && $case[ 'status' ] !== 'visited' ){ throw new AppstvServiceRuleException( "Only a visited or closed case can be reopened." ); }
                $status = ( (string) $case[ 'visit_date' ] >= appstv_case_today() ) ? 'scheduled' : 'open';   // A future visit stays scheduled
                $set[] = "outcome = ''";
                $set[] = "closed_by = ''";
                $set[] = "closed_on = ''";
                $log[] = array( 'reopen', 'Case reopened' );
            } else {
                throw new AppstvServiceRuleException( "Use Close case to close it." );
            }
        }
        if( $status !== $case[ 'status' ] ){ $set[] = "status = '$status'"; }

        if( count( $set ) > 0 ){
            appstv_case_db( "UPDATE service_cases SET " . implode( ', ', $set ) . " WHERE id = $id" );
            foreach( $log as $entry ){ appstv_case_log( $id, $entry[ 0 ], $entry[ 1 ] ); }
        }
        return array( count( $set ) ? "Case SC-$id updated." : "Nothing changed.", array( 'case_id' => $id, 'status' => $status ) );
    } );
}


/**
 * Adds a note to a case's timeline
 */
function appstv_case_add_note(){
    checkAuthorizationForFunction( __FUNCTION__ );
    appstv_case_transaction( __FUNCTION__, function(){
        $case = appstv_case_read( request( 'case_id' ), true );
        $note = trim( (string) request( 'note' ) );
        if( $note === '' ){ throw new AppstvServiceRuleException( "Write the note first." ); }
        if( strlen( $note ) > 5000 ){ throw new AppstvServiceRuleException( "The note is too long (max 5000)." ); }
        appstv_case_log( $case[ 'id' ], 'note', $note );
        return array( "Note added.", array( 'case_id' => (int) $case[ 'id' ] ) );
    } );
}


/**
 * Adds a TV to a case, or removes one that has no service recorded through the case
 */
function appstv_case_set_tv(){
    checkAuthorizationForFunction( __FUNCTION__ );
    appstv_case_transaction( __FUNCTION__, function(){
        $case = appstv_case_read( request( 'case_id' ), true );
        $id = (int) $case[ 'id' ];
        if( $case[ 'status' ] === 'closed' ){ throw new AppstvServiceRuleException( "This case is closed. Reopen it first." ); }

        if( request( 'remove_id' ) !== null ){
            $row = mysqli_fetch_assoc( appstv_case_db( "SELECT * FROM service_case_tvs WHERE id = " . (int) request( 'remove_id' ) . " AND case_id = $id" ) );
            if( $row === null ){ throw new AppstvServiceRuleException( "That TV is not on this case." ); }
            if( (string) $row[ 'pwrp_id' ] !== '' ){ throw new AppstvServiceRuleException( "A service was recorded for this TV on this case; it cannot be removed." ); }
            appstv_case_db( "DELETE FROM service_case_tvs WHERE id = " . (int) $row[ 'id' ] );
            appstv_case_log( $id, 'tv', 'TV ' . $row[ 'serial_number' ] . ' removed from the case' );
            return array( "TV removed.", array( 'case_id' => $id ) );
        }

        $serial = appstv_case_insert_tv( $id, trim( (string) request( 'product_sn_id' ) ), (string) request( 'serial_number' ) );
        appstv_case_log( $id, 'tv', 'TV ' . $serial . ' added to the case' . ( trim( (string) request( 'product_sn_id' ) ) === '' ? ' (not found in the system)' : '' ) );
        return array( "TV added.", array( 'case_id' => $id ) );
    } );
}


/**
 * Closes a case with its outcome; repaired / replaced need a service recorded on one of its TVs
 */
function appstv_case_close(){
    checkAuthorizationForFunction( __FUNCTION__ );
    appstv_case_transaction( __FUNCTION__, function(){
        $case = appstv_case_read( request( 'case_id' ), true );
        $id = (int) $case[ 'id' ];
        $outcome = (string) request( 'outcome' );
        $note = trim( (string) request( 'note' ) );
        if( $case[ 'status' ] === 'closed' ){ throw new AppstvServiceRuleException( "This case is already closed." ); }
        if( !in_array( $outcome, APPSTV_CASE_OUTCOMES ) ){ throw new AppstvServiceRuleException( "Choose how the case ended." ); }
        if( strlen( $note ) > 5000 ){ throw new AppstvServiceRuleException( "The note is too long (max 5000)." ); }
        if( in_array( $outcome, array( 'repaired', 'replaced' ) ) ){
            $type = ( $outcome === 'replaced' ) ? "r.new_product_sn_id IS NOT NULL AND r.new_product_sn_id <> ''" : "( r.new_product_sn_id IS NULL OR r.new_product_sn_id = '' )";
            $found = mysqli_num_rows( appstv_case_db( "SELECT 1 FROM service_case_tvs t JOIN product_warranty_replacements r ON r.pwrp_id = t.pwrp_id
                                                       WHERE t.case_id = $id AND $type LIMIT 1" ) );
            if( $found === 0 ){
                throw new AppstvServiceRuleException( "Record the " . ( $outcome === 'replaced' ? 'replacement' : 'repair' ) . " on the case's TV first (Record service), then close the case." );
            }
        }
        $now = create_iso_8601_datetime();
        $e_user = escape_string( appstv_case_user_id() );
        appstv_case_db( "UPDATE service_cases SET status = 'closed', outcome = '$outcome', closed_by = '$e_user', closed_on = '$now' WHERE id = $id" );
        $labels = array( 'repaired' => 'Repaired', 'replaced' => 'Replaced', 'no_fault' => 'No fault found', 'not_covered' => 'Not covered by warranty' );
        appstv_case_log( $id, 'close', 'Case closed: ' . $labels[ $outcome ] . ( $note !== '' ? ' - ' . $note : '' ) );
        return array( "Case SC-$id closed.", array( 'case_id' => $id ) );
    } );
}


/**
 * Records a repair or a replacement on a TV (from a case or from the Warranty page)
 */
function appstv_record_service(){
    checkAuthorizationForFunction( __FUNCTION__ );
    appstv_case_transaction( __FUNCTION__, function(){
        global $con;
        $in = json_decode( (string) request( 'service' ), true );
        if( !is_array( $in ) ){ throw new AppstvServiceRuleException( "The form data was not received." ); }

        $product_sn_id = trim( (string) ( isset( $in[ 'product_sn_id' ] ) ? $in[ 'product_sn_id' ] : '' ) );
        $type    = isset( $in[ 'type' ] ) ? $in[ 'type' ] : '';
        $date    = trim( (string) ( isset( $in[ 'date' ] ) ? $in[ 'date' ] : '' ) );
        $ref_no  = trim( (string) ( isset( $in[ 'ref_no' ] ) ? $in[ 'ref_no' ] : '' ) );
        $remarks = trim( (string) ( isset( $in[ 'remarks' ] ) ? $in[ 'remarks' ] : '' ) );
        $case_id = (int) ( isset( $in[ 'case_id' ] ) ? $in[ 'case_id' ] : 0 );
        if( !in_array( $type, array( 'repair', 'replacement' ) ) ){ throw new AppstvServiceRuleException( "Choose Repair or Replacement." ); }
        if( !appstv_case_valid_date( $date ) ){ throw new AppstvServiceRuleException( "Enter the date of service." ); }
        if( $date > appstv_case_today() ){ throw new AppstvServiceRuleException( "The date of service cannot be in the future." ); }
        if( $ref_no !== '' && !preg_match( '/^[A-Za-z0-9][A-Za-z0-9 \/\-_.#]{0,49}$/', $ref_no ) ){ throw new AppstvServiceRuleException( "The Ref No. is not valid (letters, digits, space / - _ . #, up to 50)." ); }
        if( $remarks === '' ){ throw new AppstvServiceRuleException( "Describe what was done (remarks)." ); }
        if( strlen( $remarks ) > 2000 ){ throw new AppstvServiceRuleException( "Remarks are too long (max 2000)." ); }

        $chain = appstv_case_tv_chain( $product_sn_id );
        if( $chain === null ){ throw new AppstvServiceRuleException( "That TV does not exist." ); }
        if( $chain[ 'psnw_id' ] === null ){ throw new AppstvServiceRuleException( "TV {$chain['serial_number']} has no warranty record, so no service can be recorded on it." ); }
        if( !$chain[ 'is_current' ] ){ throw new AppstvServiceRuleException( "TV {$chain['serial_number']} was already replaced by {$chain['current_serial_number']}. Record the service on that TV." ); }

        $case_tv = null;
        if( $case_id > 0 ){
            $case = appstv_case_read( $case_id, true );
            if( $case[ 'status' ] === 'closed' ){ throw new AppstvServiceRuleException( "This case is closed. Reopen it first." ); }
            $case_tv = mysqli_fetch_assoc( appstv_case_db( "SELECT * FROM service_case_tvs WHERE case_id = $case_id AND product_sn_id = '" . escape_string( $product_sn_id ) . "' LIMIT 1" ) );
            if( $case_tv === null ){ throw new AppstvServiceRuleException( "That TV is not on this case." ); }
        }

        // Replacement TV: unsold stock record of that model, else a new record (warning when the serial is used elsewhere)
        $new_product_sn_id = null;
        $new_serial = '';
        if( $type === 'replacement' ){
            $new_serial = strtoupper( preg_replace( '/[^A-Za-z0-9]/', '', (string) ( isset( $in[ 'new_serial_number' ] ) ? $in[ 'new_serial_number' ] : '' ) ) );
            $new_product_id = trim( (string) ( isset( $in[ 'new_product_id' ] ) ? $in[ 'new_product_id' ] : '' ) );
            if( $new_serial === '' || strlen( $new_serial ) > 50 ){ throw new AppstvServiceRuleException( "Enter the serial number of the replacement TV." ); }
            if( $new_product_id === '' ){ $new_product_id = $chain[ 'product_id' ]; }
            $e_pid = escape_string( $new_product_id );
            if( mysqli_num_rows( appstv_case_db( "SELECT 1 FROM products WHERE product_id = '$e_pid'" ) ) === 0 ){ throw new AppstvServiceRuleException( "The replacement TV model does not exist." ); }
            if( $new_serial === strtoupper( $chain[ 'serial_number' ] ) && $new_product_id === $chain[ 'product_id' ] ){
                throw new AppstvServiceRuleException( "The replacement serial number is the same as the faulty TV." );
            }
            $e_sn = escape_string( $new_serial );
            $records = appstv_case_rows( "SELECT ps.product_sn_id, ps.product_id, ps.allotted_to_customer,
                                                 EXISTS( SELECT 1 FROM invoice_product_sn i WHERE i.product_sn_id = ps.product_sn_id ) AS invoiced,
                                                 EXISTS( SELECT 1 FROM order_product_sn o WHERE o.product_sn_id = ps.product_sn_id ) AS in_order,
                                                 EXISTS( SELECT 1 FROM product_warranty_replacements r WHERE r.new_product_sn_id = ps.product_sn_id ) AS replacement
                                          FROM product_sn ps WHERE ps.serial_number = '$e_sn' ORDER BY ps.id FOR UPDATE" );
            $used_elsewhere = false;
            foreach( $records as $record ){
                $free = ( (int) $record[ 'allotted_to_customer' ] === 0 && !$record[ 'invoiced' ] && !$record[ 'in_order' ] && !$record[ 'replacement' ] );
                if( $free && $record[ 'product_id' ] === $new_product_id && $new_product_sn_id === null ){
                    $new_product_sn_id = $record[ 'product_sn_id' ];
                }
                if( !$free ){ $used_elsewhere = true; }
            }
            if( $new_product_sn_id !== null ){
                appstv_case_db( "UPDATE product_sn SET allotted_to_customer = 1 WHERE product_sn_id = '" . escape_string( $new_product_sn_id ) . "' AND allotted_to_customer = 0" );
                if( mysqli_affected_rows( $con ) !== 1 ){ throw new AppstvServiceRuleException( "The replacement TV was taken by someone else just now. Please try again." ); }
            } else {
                if( $used_elsewhere && empty( $in[ 'confirm_duplicate' ] ) ){
                    throw new AppstvServiceRuleException( "DUPLICATE:Serial number $new_serial is already used by another TV. Save it as a separate TV (same serial number from the manufacturer)?" );
                }
                $new_product_sn_id = appstv_case_unique_id( 'product_sn', 'product_sn_id', 'product_sn' );
                appstv_case_db( "INSERT INTO product_sn ( product_sn_id, product_id, serial_number, created_on, allotted_to_customer )
                                 VALUES ( '$new_product_sn_id', '$e_pid', '$e_sn', '" . create_iso_8601_datetime() . "', 1 )" );
            }
        }

        $invoice_id = appstv_case_service_invoice( $ref_no, $chain[ 'customer_id' ], $date );
        $pwrp_id = appstv_case_unique_id( 'product_warranty_replacements', 'pwrp_id' );
        appstv_case_db( "INSERT INTO product_warranty_replacements ( pwrp_id, is_repairable, psnw_id, date_of_service, new_invoice_id, new_product_sn_id, remarks )
                         VALUES ( '$pwrp_id', " . ( $type === 'repair' ? 1 : 0 ) . ", '" . escape_string( $chain[ 'psnw_id' ] ) . "', '$date 00:00:00',
                                  " . ( $invoice_id === null ? "NULL" : "'$invoice_id'" ) . ", " . ( $new_product_sn_id === null ? "NULL" : "'$new_product_sn_id'" ) . ",
                                  '" . escape_string( $remarks ) . "' )" );

        $summary = ( $type === 'repair' ) ? "Repair of TV {$chain['serial_number']} on $date" : "TV {$chain['serial_number']} replaced by $new_serial on $date";
        $summary .= ( $ref_no !== '' ? " (Ref No. $ref_no)" : '' ) . ': ' . $remarks;
        if( $case_tv !== null ){
            appstv_case_db( "UPDATE service_case_tvs SET pwrp_id = '$pwrp_id' WHERE id = " . (int) $case_tv[ 'id' ] );
            $on_case = mysqli_num_rows( appstv_case_db( "SELECT 1 FROM service_case_tvs WHERE case_id = $case_id AND product_sn_id = '$new_product_sn_id'" ) ) > 0;
            if( $type === 'replacement' && !$on_case ){  // The replacement TV joins the case so later services can be recorded on it
                appstv_case_insert_tv( $case_id, $new_product_sn_id, '' );
            }
            appstv_case_log( $case_id, 'service', $summary );
        }
        return array( ( $type === 'repair' ? "Repair" : "Replacement" ) . " recorded.", array( 'pwrp_id' => $pwrp_id, 'summary' => $summary ) );
    } );
}


/**
 * Adds an extended warranty to a TV's warranty (starts when the warranty ends)
 */
function appstv_add_extended_warranty(){
    checkAuthorizationForFunction( __FUNCTION__ );
    appstv_case_transaction( __FUNCTION__, function(){
        $product_sn_id = trim( (string) request( 'product_sn_id' ) );
        $years = (int) request( 'years' );
        $remarks = trim( (string) request( 'remarks' ) );
        $case_id = (int) request( 'case_id' );
        if( $years < 1 || $years > 10 ){ throw new AppstvServiceRuleException( "Choose 1 to 10 years." ); }
        if( strlen( $remarks ) > 1000 ){ throw new AppstvServiceRuleException( "Remarks are too long (max 1000)." ); }

        $chain = appstv_case_tv_chain( $product_sn_id );
        if( $chain === null ){ throw new AppstvServiceRuleException( "That TV does not exist." ); }
        if( $chain[ 'psnw_id' ] === null || $chain[ 'warranty_expiry' ] === null ){ throw new AppstvServiceRuleException( "TV {$chain['serial_number']} has no warranty to extend." ); }
        $e_psnw = escape_string( $chain[ 'psnw_id' ] );
        appstv_case_db( "SELECT id FROM product_sn_warranty WHERE psnw_id = '$e_psnw' FOR UPDATE" );
        if( mysqli_num_rows( appstv_case_db( "SELECT 1 FROM product_sn_extended_warranty WHERE psnw_id = '$e_psnw'" ) ) > 0 ){
            throw new AppstvServiceRuleException( "TV {$chain['serial_number']} already has an extended warranty" . ( $chain[ 'extended_expiry' ] ? " (until {$chain['extended_expiry']})" : '' ) . "." );
        }

        $start = new DateTime( $chain[ 'warranty_expiry' ] );
        $end = clone $start;
        $end->modify( '+' . $years . ' years' );
        $days = (int) $start->diff( $end )->days;
        $id = appstv_case_unique_id( 'product_sn_extended_warranty', 'psnexw_id' );
        $user = appstv_case_user_name( appstv_case_user_id() );
        $text = 'Extended warranty added' . ( $user !== '' ? ' by ' . $user : '' ) . ( $remarks !== '' ? ': ' . $remarks : '' );
        appstv_case_db( "INSERT INTO product_sn_extended_warranty ( psnexw_id, psnw_id, extended_warranty_start_date, extended_warranty_period, remarks )
                         VALUES ( '$id', '$e_psnw', '" . $start->format( 'Y-m-d' ) . " 00:00:00', '$days', '" . escape_string( $text ) . "' )" );
        if( $case_id > 0 ){
            appstv_case_read( $case_id, true );
            appstv_case_log( $case_id, 'service', "Extended warranty +$years year(s) for TV {$chain['serial_number']}, until " . $end->format( 'Y-m-d' ) . ( $remarks !== '' ? ': ' . $remarks : '' ) );
        }
        return array( "Extended warranty added until " . $end->format( 'd/m/Y' ) . ".", array( 'until' => $end->format( 'Y-m-d' ) ) );
    } );
}
