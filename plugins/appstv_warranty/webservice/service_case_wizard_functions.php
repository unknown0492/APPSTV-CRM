<?php

/*
 * Service case OPENING wizard (v1.0.19) - webservice actions of the "Open case" wizard
 *
 * Step 1 of the wizard: customer (search while typing, or add a new one on the spot) and the person who
 * complained (complainant: suggested from the people who complained before for that customer).
 * Step 2: the TV models to pick from.
 *
 * Shared helpers (appstv_case_db, appstv_case_rows, appstv_case_answer, appstv_case_transaction, appstv_case_user_id,
 * appstv_case_customer_name ...) are in service_case_functions.php.
 */

define( 'APPSTV_CASE_SOURCE_DIRECT', '20365841' );         // sources.source_id of "Direct" (same as Record Sale)
define( 'APPSTV_CASE_SEARCH_LIMIT', 15 );                  // Customers shown while typing
define( 'APPSTV_CASE_CONTACT_LIMIT', 20 );                 // Complainants suggested per customer


/**
 * @description Reads a text field from the request, trimmed, and checks its length
 * @param {string} $name - Request parameter
 * @param {int} $max - Longest allowed length (characters)
 * @param {string} $label - Name of the field in the error message
 * @returns {string} The trimmed value
 * @throws AppstvServiceRuleException when it is too long
 */
function appstv_case_text( $name, $max, $label ){
    $value = trim( (string) request( $name ) );
    if( mb_strlen( $value ) > $max ){
        throw new AppstvServiceRuleException( "$label is too long (at most $max characters)." );
    }
    return $value;
}

/**
 * @description Checks a phone number (digits, spaces, + - ( ), 6 to 20 characters); empty is allowed
 * @param {string} $phone - Phone text
 * @param {string} $label - Name of the field in the error message
 * @returns {void}
 * @throws AppstvServiceRuleException when it is not valid
 */
function appstv_case_check_phone( $phone, $label ){
    if( $phone !== '' && !preg_match( '/^[0-9+\-() ]{6,20}$/', $phone ) ){
        throw new AppstvServiceRuleException( "$label is not a valid phone number." );
    }
}

/**
 * @description Checks an e-mail address; empty is allowed
 * @param {string} $email - E-mail text
 * @param {string} $label - Name of the field in the error message
 * @returns {void}
 * @throws AppstvServiceRuleException when it is not valid
 */
function appstv_case_check_email( $email, $label ){
    if( $email !== '' && !filter_var( $email, FILTER_VALIDATE_EMAIL ) ){
        throw new AppstvServiceRuleException( "$label is not a valid e-mail address." );
    }
}

/**
 * @description Digits of a phone number, for comparing numbers written differently ("+65 9123-4567" = "6591234567")
 * @param {string} $phone - Phone text
 * @returns {string} Digits only
 */
function appstv_case_phone_digits( $phone ){
    return preg_replace( '/\D/', '', (string) $phone );
}

/**
 * @description Shapes a customers row for the wizard
 * @param {array} $row - customers row (customer_id, company_name, first_name, last_name, primary_email, primary_mobile)
 * @returns {array} customer_id, name (company, else the person), person (contact when there is a company), email, mobile
 */
function appstv_case_customer_item( $row ){
    $person = trim( $row[ 'first_name' ] . ' ' . $row[ 'last_name' ] );
    $has_company = ( trim( (string) $row[ 'company_name' ] ) !== '' );
    return array(
        'customer_id' => (string) $row[ 'customer_id' ],
        'name'        => appstv_case_customer_name( $row ),
        'person'      => $has_company ? $person : '',
        'email'       => (string) $row[ 'primary_email' ],
        'mobile'      => (string) $row[ 'primary_mobile' ]
    );
}


/**
 * Customers matching what is typed (company, contact name, e-mail, mobile or customer ID); names that start with
 * the text come first
 */
function appstv_case_customer_search(){
    checkAuthorizationForFunction( __FUNCTION__ );
    appstv_case_answer( __FUNCTION__, function(){
        $query = appstv_case_text( 'q', 100, "The search text" );
        if( mb_strlen( $query ) < 2 ){
            return array( "Type at least 2 letters.", array( 'customers' => array() ) );
        }
        $e_like = escape_string( addcslashes( $query, '%_\\' ) );
        $e_query = escape_string( $query );
        $digits = appstv_case_phone_digits( $query );
        // A number of 4+ digits is also matched against the mobile without its spaces / dashes
        $phone_match = ( strlen( $digits ) >= 4 )
            ? " OR REGEXP_REPLACE( primary_mobile, '[^0-9]', '' ) LIKE '%" . escape_string( $digits ) . "%'"
            : "";
        $rows = appstv_case_rows( "SELECT customer_id, company_name, first_name, last_name, primary_email, primary_mobile,
                                          ( company_name LIKE '$e_like%' OR first_name LIKE '$e_like%' ) AS starts_with
                                   FROM customers
                                   WHERE company_name LIKE '%$e_like%' OR first_name LIKE '%$e_like%' OR last_name LIKE '%$e_like%'
                                      OR CONCAT( first_name, ' ', last_name ) LIKE '%$e_like%'
                                      OR primary_email LIKE '%$e_like%' OR primary_mobile LIKE '%$e_like%'
                                      OR customer_id = '$e_query' $phone_match
                                   ORDER BY starts_with DESC, COALESCE( NULLIF( company_name, '' ), first_name ), last_name
                                   LIMIT " . APPSTV_CASE_SEARCH_LIMIT );
        $customers = array();
        foreach( $rows as $row ){ $customers[] = appstv_case_customer_item( $row ); }
        return array( count( $customers ) . " customer(s) found.", array( 'customers' => $customers ) );
    } );
}


/**
 * Adds a new customer from the wizard (company and / or contact person, mobile, e-mail).
 * A customer with the same company name or mobile number is reported first; confirm = 1 adds it anyway.
 */
function appstv_case_customer_add(){
    checkAuthorizationForFunction( __FUNCTION__ );
    appstv_case_transaction( __FUNCTION__, function(){
        $company = appstv_case_text( 'company_name', 200, "The company name" );
        $first   = appstv_case_text( 'first_name', 100, "The first name" );
        $last    = appstv_case_text( 'last_name', 100, "The last name" );
        $mobile  = appstv_case_text( 'mobile', 20, "The mobile number" );
        $email   = appstv_case_text( 'email', 200, "The e-mail address" );
        $confirm = ( (string) request( 'confirm' ) === '1' );   // Add even when a similar customer exists

        if( $company === '' && $first === '' ){
            throw new AppstvServiceRuleException( "Enter the company name or the contact person's first name." );
        }
        appstv_case_check_phone( $mobile, "The mobile number" );
        appstv_case_check_email( $email, "The e-mail address" );

        // Same company name, or same mobile number: probably the same customer
        if( !$confirm ){
            $checks = array();
            if( $company !== '' ){ $checks[] = "LOWER( TRIM( company_name ) ) = LOWER( '" . escape_string( $company ) . "' )"; }
            $digits = appstv_case_phone_digits( $mobile );
            if( strlen( $digits ) >= 6 ){ $checks[] = "REGEXP_REPLACE( primary_mobile, '[^0-9]', '' ) = '" . escape_string( $digits ) . "'"; }
            if( count( $checks ) > 0 ){
                $rows = appstv_case_rows( "SELECT customer_id, company_name, first_name, last_name, primary_email, primary_mobile
                                           FROM customers WHERE " . implode( " OR ", $checks ) . " LIMIT 5" );
                if( count( $rows ) > 0 ){
                    $similar = array();
                    foreach( $rows as $row ){ $similar[] = appstv_case_customer_item( $row ); }
                    return array( "A customer with the same company name or mobile number already exists.",
                                  array( 'added' => false, 'similar' => $similar ) );
                }
            }
        }

        $customer_id = appstv_case_unique_id( 'customers', 'customer_id' );
        $now = create_iso_8601_datetime();
        $e = array_map( 'escape_string', array( 'company' => $company, 'first' => $first, 'last' => $last, 'mobile' => $mobile, 'email' => $email ) );
        appstv_case_db( "INSERT INTO customers ( customer_id, customer_source, user_id, title, company_name, first_name, middle_name, last_name,
                                                 primary_email, alternate_email, created_at, updated_at, state, primary_mobile, alternate_mobile, currency )
                         VALUES ( '$customer_id', '" . APPSTV_CASE_SOURCE_DIRECT . "', '', '', '{$e['company']}', '{$e['first']}', '', '{$e['last']}',
                                  '{$e['email']}', '', '$now', '', '', '{$e['mobile']}', '', '' )" );
        $row = mysqli_fetch_assoc( appstv_case_db( "SELECT customer_id, company_name, first_name, last_name, primary_email, primary_mobile
                                                     FROM customers WHERE customer_id = '$customer_id' LIMIT 1" ) );
        return array( "Customer added.", array( 'added' => true, 'customer' => appstv_case_customer_item( $row ) ) );
    } );
}


/**
 * People who complained before for a customer (newest first), plus the customer's own contact person.
 * q (optional) narrows the list to names / phones containing the text.
 */
function appstv_case_contacts(){
    checkAuthorizationForFunction( __FUNCTION__ );
    appstv_case_answer( __FUNCTION__, function(){
        $customer_id = appstv_case_text( 'customer_id', 50, "The customer" );
        $query = appstv_case_text( 'q', 100, "The search text" );
        if( $customer_id === '' ){ throw new AppstvServiceRuleException( "Choose the customer first." ); }
        $e_cid = escape_string( $customer_id );
        $customer = mysqli_fetch_assoc( appstv_case_db( "SELECT customer_id, company_name, first_name, last_name, primary_email, primary_mobile
                                                          FROM customers WHERE customer_id = '$e_cid' LIMIT 1" ) );
        if( $customer === null ){ throw new AppstvServiceRuleException( "This customer does not exist." ); }

        $filter = "";
        if( $query !== '' ){
            $e_like = escape_string( addcslashes( $query, '%_\\' ) );
            $filter = " AND ( name LIKE '%$e_like%' OR phone LIKE '%$e_like%' OR email LIKE '%$e_like%' )";
        }
        $contacts = array();
        $seen = array();                                    // name|phone digits already listed
        foreach( appstv_case_rows( "SELECT id, name, phone, email, position, last_used_on FROM customer_contacts
                                    WHERE customer_id = '$e_cid' $filter
                                    ORDER BY last_used_on DESC, id DESC LIMIT " . APPSTV_CASE_CONTACT_LIMIT ) as $row ){
            $contacts[] = array( 'contact_id' => (int) $row[ 'id' ], 'name' => $row[ 'name' ], 'phone' => $row[ 'phone' ],
                                 'email' => $row[ 'email' ], 'position' => $row[ 'position' ], 'source' => 'complained_before' );
            $seen[ mb_strtolower( $row[ 'name' ] ) . '|' . appstv_case_phone_digits( $row[ 'phone' ] ) ] = true;
        }

        // The customer's own contact person, when not listed already
        $person = trim( $customer[ 'first_name' ] . ' ' . $customer[ 'last_name' ] );
        $key = mb_strtolower( $person ) . '|' . appstv_case_phone_digits( $customer[ 'primary_mobile' ] );
        $matches = ( $query === '' ) || ( mb_stripos( $person . ' ' . $customer[ 'primary_mobile' ] . ' ' . $customer[ 'primary_email' ], $query ) !== false );
        if( $person !== '' && !isset( $seen[ $key ] ) && $matches ){
            $contacts[] = array( 'contact_id' => 0, 'name' => $person, 'phone' => $customer[ 'primary_mobile' ],
                                 'email' => $customer[ 'primary_email' ], 'position' => '', 'source' => 'customer' );
        }
        return array( count( $contacts ) . " contact(s).", array( 'contacts' => $contacts ) );
    } );
}


/**
 * @description Finds or adds the complainant of a customer (customer_contacts) and marks it as used now.
 *              Same name (any case) and same phone digits = the same person; a new e-mail / position updates it.
 * @param {string} $customer_id - customers.customer_id
 * @param {string} $name - Complainant name (required)
 * @param {string} $phone - Phone
 * @param {string} $email - E-mail
 * @param {string} $position - Position, e.g. Front Office
 * @returns {int} customer_contacts.id
 */
function appstv_case_save_contact( $customer_id, $name, $phone, $email, $position ){
    $e_cid = escape_string( $customer_id );
    $digits = appstv_case_phone_digits( $phone );
    $now = create_iso_8601_datetime();
    $found = null;
    foreach( appstv_case_rows( "SELECT id, name, phone, email, position FROM customer_contacts
                                WHERE customer_id = '$e_cid' AND LOWER( name ) = LOWER( '" . escape_string( $name ) . "' ) FOR UPDATE" ) as $row ){
        if( appstv_case_phone_digits( $row[ 'phone' ] ) === $digits ){ $found = $row; break; }
    }
    if( $found !== null ){
        $sets = array( "last_used_on = '$now'" );
        if( $email !== '' && $email !== $found[ 'email' ] ){ $sets[] = "email = '" . escape_string( $email ) . "'"; }
        if( $position !== '' && $position !== $found[ 'position' ] ){ $sets[] = "position = '" . escape_string( $position ) . "'"; }
        appstv_case_db( "UPDATE customer_contacts SET " . implode( ", ", $sets ) . " WHERE id = " . (int) $found[ 'id' ] );
        return (int) $found[ 'id' ];
    }
    $e = array_map( 'escape_string', array( 'name' => $name, 'phone' => $phone, 'email' => $email, 'position' => $position ) );
    $e_user = escape_string( appstv_case_user_id() );
    appstv_case_db( "INSERT INTO customer_contacts ( customer_id, name, phone, email, position, created_by, created_on, last_used_on )
                     VALUES ( '$e_cid', '{$e['name']}', '{$e['phone']}', '{$e['email']}', '{$e['position']}', '$e_user', '$now', '$now' )" );
    global $con;
    return (int) mysqli_insert_id( $con );
}


/**
 * @description Series and size of a TV model from its name / title ("APPSTV QLED VQ Series 4K UHD 43\"" = VQ, 43)
 * @param {array} $row - products row (title, name, sku)
 * @returns {array} series (e.g. 'VQ', 'Other'), size (inches, 0 when unknown)
 */
function appstv_case_model_series( $row ){
    $text = $row[ 'name' ] . ' ' . $row[ 'title' ];
    $series = 'Other';
    if( preg_match( '/\b(esTV)\b/i', $text ) ){
        $series = 'esTV';
    } else if( preg_match( '/\b(V[A-Z])(?:\s+Series|\d{2}\b)/', $text, $m ) ){
        $series = $m[ 1 ];
    }
    $size = 0;
    if( preg_match( '/(\d{2})\s*(?:"|”|inch|\'\')/u', $text, $m ) ){ $size = (int) $m[ 1 ]; }
    return array( 'series' => $series, 'size' => $size );
}


/**
 * TV models to pick from (products with serial numbers), grouped by series and sorted by size
 */
function appstv_case_models(){
    checkAuthorizationForFunction( __FUNCTION__ );
    appstv_case_answer( __FUNCTION__, function(){
        $models = array();
        foreach( appstv_case_rows( "SELECT product_id, sku, title, name FROM products WHERE has_sn = 1" ) as $row ){
            $info = appstv_case_model_series( $row );
            $models[] = array( 'product_id' => (string) $row[ 'product_id' ], 'sku' => (string) $row[ 'sku' ],
                               'title' => (string) $row[ 'title' ], 'name' => (string) $row[ 'name' ],
                               'series' => $info[ 'series' ], 'size' => $info[ 'size' ] );
        }
        usort( $models, function( $a, $b ){
            $order = strcmp( $a[ 'series' ] === 'Other' ? 'zz' : $a[ 'series' ], $b[ 'series' ] === 'Other' ? 'zz' : $b[ 'series' ] );
            if( $order !== 0 ){ return $order; }
            if( $a[ 'size' ] !== $b[ 'size' ] ){ return $a[ 'size' ] - $b[ 'size' ]; }
            return strcmp( $a[ 'title' ], $b[ 'title' ] );
        } );
        return array( count( $models ) . " TV model(s).", array( 'models' => $models ) );
    } );
}
