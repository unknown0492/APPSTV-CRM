<?php

/**
 * Searches customers by company name, first name, or last name
 * Used for the autocomplete search in the Link Serial Number modal
 */
function appstv_search_customers() {
    checkAuthorizationForFunction( __FUNCTION__ );

    $query = request( 'q' );

    validateEmptyString( $query, __FUNCTION__, "Search query is required." );

    $e_query = escape_string( $query );

    $sql = "SELECT customer_id, company_name, first_name, middle_name, last_name,
                   primary_email, primary_mobile
            FROM customers
            WHERE company_name  LIKE '%$e_query%'
               OR first_name    LIKE '%$e_query%'
               OR last_name     LIKE '%$e_query%'
               OR primary_email LIKE '%$e_query%'
            ORDER BY company_name ASC
            LIMIT 20";

    $result_set = selectQuery( $sql );

    $customers = array();
    if ( $result_set && mysqli_num_rows( $result_set ) > 0 ) {
        while ( $row = mysqli_fetch_assoc( $result_set ) ) {
            $customers[] = array(
                'customer_id'    => $row[ 'customer_id' ],
                'company_name'   => $row[ 'company_name' ],
                'first_name'     => $row[ 'first_name' ],
                'middle_name'    => $row[ 'middle_name' ],
                'last_name'      => $row[ 'last_name' ],
                'primary_email'  => $row[ 'primary_email' ],
                'primary_mobile' => $row[ 'primary_mobile' ]
            );
        }
    }

    $responseData = array(
        'message'   => count( $customers ) . " customer(s) found.",
        'data'      => $customers
    );

    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $responseData );
}


/**
 * Verifies a serial number — checks if it exists and if it is allotted or not
 * Returns status: 'not_found' | 'unallotted' | 'allotted'
 */
function appstv_verify_serial_number() {
    checkAuthorizationForFunction( __FUNCTION__ );

    $serial_number = request( 'serial_number' );

    validateEmptyString( $serial_number, __FUNCTION__, "Serial number is required." );
    validate( $serial_number, __FUNCTION__, "/^\d{1,50}$/", "Serial number is invalid." );

    $e_serial_number = escape_string( $serial_number );

    // ── Look up serial number ──────────────────────────────────────
    $sql        = "SELECT ps.product_sn_id, ps.allotted_to_customer,
                          c.customer_id, c.company_name, c.first_name, c.last_name
                   FROM product_sn ps
                   LEFT JOIN invoice_product_sn ipsn ON ipsn.product_sn_id = ps.product_sn_id
                   LEFT JOIN order_invoices oi       ON oi.invoice_id = ipsn.invoice_id
                   LEFT JOIN customers c             ON c.customer_id = oi.customer_id
                   WHERE ps.serial_number = '$e_serial_number'
                   LIMIT 1";
    $result_set = selectQuery( $sql );

    if ( $result_set === NULL || mysqli_num_rows( $result_set ) === 0 ) {
        $responseData = array(
            'message' => "Serial number not found.",
            'data'    => array( 'status' => 'not_found' )
        );
        echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $responseData );
        return;
    }

    $row = mysqli_fetch_assoc( $result_set );

    if ( intval( $row[ 'allotted_to_customer' ] ) === 1 ) {
        $responseData = array(
            'message' => "Serial number is already allotted.",
            'data'    => array(
                'status'         => 'allotted',
                'product_sn_id'  => $row[ 'product_sn_id' ],
                'customer_id'    => $row[ 'customer_id' ],
                'company_name'   => $row[ 'company_name' ],
                'customer_name'  => trim( $row[ 'first_name' ] . ' ' . $row[ 'last_name' ] )
            )
        );
    } else {
        $responseData = array(
            'message' => "Serial number exists and is available.",
            'data'    => array(
                'status'        => 'unallotted',
                'product_sn_id' => $row[ 'product_sn_id' ]
            )
        );
    }

    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $responseData );
}


/**
 * Reads serial numbers from an uploaded Excel or CSV file
 * Returns an array of serial numbers
 */
function appstv_read_serial_number_from_file() {
    checkAuthorizationForFunction( __FUNCTION__ );

    if ( !isset( $_FILES[ 'sn_file' ] ) || $_FILES[ 'sn_file' ][ 'error' ] !== UPLOAD_ERR_OK ) {
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "No file was uploaded or an upload error occurred." );
        return;
    }

    $file      = $_FILES[ 'sn_file' ];
    $file_name = $file[ 'name' ];
    $file_ext  = strtolower( pathinfo( $file_name, PATHINFO_EXTENSION ) );

    if ( !in_array( $file_ext, [ 'xlsx', 'csv' ] ) ) {
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Only .xlsx and .csv files are supported." );
        return;
    }

    $serial_numbers = array();

    if ( $file_ext === 'csv' ) {
        // ── Read CSV ───────────────────────────────────────────────
        $handle = fopen( $file[ 'tmp_name' ], 'r' );
        if ( $handle ) {
            while ( ( $line = fgetcsv( $handle ) ) !== false ) {
                foreach ( $line as $cell ) {
                    $sn = trim( preg_replace( '/\D/', '', (string) $cell ) );
                    if ( strlen( $sn ) > 0 && strlen( $sn ) <= 50 ) {
                        $serial_numbers[] = $sn;
                    }
                }
            }
            fclose( $handle );
        }
    } else {
        // ── Read XLSX ──────────────────────────────────────────────
        require_once BASE_PATH . FILE_SEPARATOR . 'library' . FILE_SEPARATOR . 'Classes' . FILE_SEPARATOR . 'SimpleXLSX.php';

        $xlsx = \Shuchkin\SimpleXLSX::parse( $file[ 'tmp_name' ] );
        if ( !$xlsx ) {
            echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Failed to parse the Excel file." );
            return;
        }

        foreach ( $xlsx->rows() as $row ) {
            foreach ( $row as $cell ) {
                $sn = trim( preg_replace( '/\D/', '', (string) $cell ) );
                if ( strlen( $sn ) > 0 && strlen( $sn ) <= 50 ) {
                    $serial_numbers[] = $sn;
                }
            }
        }
    }

    // Remove duplicates
    $serial_numbers = array_values( array_unique( $serial_numbers ) );

    if ( empty( $serial_numbers ) ) {
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "No valid serial numbers found in the file." );
        return;
    }

    // ── Verify each serial number ──────────────────────────────────
    $verified_serials = array();

    foreach ( $serial_numbers as $sn ) {
        $e_sn       = escape_string( $sn );
        $sql        = "SELECT ps.product_sn_id, ps.allotted_to_customer,
                              c.customer_id, c.company_name, c.first_name, c.last_name
                       FROM product_sn ps
                       LEFT JOIN invoice_product_sn ipsn ON ipsn.product_sn_id = ps.product_sn_id
                       LEFT JOIN order_invoices oi       ON oi.invoice_id = ipsn.invoice_id
                       LEFT JOIN customers c             ON c.customer_id = oi.customer_id
                       WHERE ps.serial_number = '$e_sn'
                       LIMIT 1";
        $result     = selectQuery( $sql );

        if ( $result === NULL || mysqli_num_rows( $result ) === 0 ) {
            $verified_serials[] = array(
                'serial_number'  => $sn,
                'status'         => 'not_found',
                'product_sn_id'  => null,
                'customer_id'    => null,
                'company_name'   => null,
                'customer_name'  => null
            );
            continue;
        }

        $row = mysqli_fetch_assoc( $result );

        if ( intval( $row[ 'allotted_to_customer' ] ) === 1 ) {
            $verified_serials[] = array(
                'serial_number'  => $sn,
                'status'         => 'allotted',
                'product_sn_id'  => $row[ 'product_sn_id' ],
                'customer_id'    => $row[ 'customer_id' ],
                'company_name'   => $row[ 'company_name' ],
                'customer_name'  => trim( $row[ 'first_name' ] . ' ' . $row[ 'last_name' ] )
            );
        } else {
            $verified_serials[] = array(
                'serial_number'  => $sn,
                'status'         => 'unallotted',
                'product_sn_id'  => $row[ 'product_sn_id' ],
                'customer_id'    => null,
                'company_name'   => null,
                'customer_name'  => null
            );
        }
    }

    $responseData = array(
        'message'        => count( $verified_serials ) . " serial number(s) found and verified.",
        'data'           => array( 'serial_numbers' => $verified_serials )
    );

    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $responseData );
}


/**
 * Links serial numbers to a customer with invoice and warranty details
 * Handles new/existing customer, new/existing invoice, warranty creation,
 * and optional replacement record
 */
function appstv_link_serial_number_to_customer() {
    checkAuthorizationForFunction( __FUNCTION__ );

    // ── 1. Read and validate inputs ────────────────────────────────
    $customer_mode         = request( 'customer_mode' );        // 'new' or 'existing'
    $invoice_no            = request( 'invoice_no' );
    $invoice_date          = request( 'invoice_date' );
    $product_id            = request( 'product_id' );
    $serial_numbers_json   = request( 'serial_numbers' );       // JSON array
    $warranty_start_date   = request( 'warranty_start_date' );
    $warranty_period_months = request( 'warranty_period_months' );
    $replacement_enabled   = request( 'replacement_enabled' );  // '0' or '1'

    validateEmptyString( $customer_mode,          __FUNCTION__, "Customer mode is required." );
    validateEmptyString( $invoice_no,             __FUNCTION__, "Invoice number is required." );
    validateEmptyString( $invoice_date,           __FUNCTION__, "Invoice date is required." );
    validateEmptyString( $product_id,             __FUNCTION__, "Product is required." );
    validateEmptyString( $serial_numbers_json,    __FUNCTION__, "Serial numbers are required." );
    validateEmptyString( $warranty_start_date,    __FUNCTION__, "Warranty start date is required." );
    validateEmptyString( $warranty_period_months, __FUNCTION__, "Warranty period is required." );

    // ── 2. Parse serial numbers ────────────────────────────────────
    $serial_numbers = json_decode( $serial_numbers_json, true );
    if ( !is_array( $serial_numbers ) || count( $serial_numbers ) === 0 ) {
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Invalid serial numbers data." );
        return;
    }
    
    // ── Remove duplicate serial numbers ───────────────────────────
    $unique_sns = array();
    $seen_sns   = array();

    foreach ( $serial_numbers as $sn_data ) {
        $sn = isset( $sn_data[ 'serial_number' ] ) ? trim( $sn_data[ 'serial_number' ] ) : '';
        if ( $sn === '' || in_array( $sn, $seen_sns ) ) {
            continue;
        }
        $seen_sns[]    = $sn;
        $unique_sns[]  = $sn_data;
    }

    $serial_numbers = $unique_sns;

    if ( count( $serial_numbers ) === 0 ) {
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "No valid serial numbers to process after removing duplicates." );
        return;
    }

    // ── 3. Resolve or create customer ─────────────────────────────
    $customer_id = null;

    if ( $customer_mode === 'existing' ) {
        $customer_id = request( 'customer_id' );
        validateEmptyString( $customer_id, __FUNCTION__, "Customer is required." );

        $e_cust_id  = escape_string( $customer_id );
        $sql_check  = "SELECT customer_id FROM customers WHERE customer_id = '$e_cust_id' LIMIT 1";
        $res_check  = selectQuery( $sql_check );
        if ( !$res_check || mysqli_num_rows( $res_check ) === 0 ) {
            echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Selected customer does not exist." );
            return;
        }

    } else {
        // New customer
        $company_name   = request( 'company_name' );
        $first_name     = request( 'first_name' );
        $middle_name    = request( 'middle_name' );
        $last_name      = request( 'last_name' );
        $primary_email  = request( 'primary_email' );
        $primary_mobile = request( 'primary_mobile' );

        if ( empty( $company_name ) && empty( $first_name ) ) {
            echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Either Company Name or First Name is required." );
            return;
        }

        $customer = new Customer();
        $customer->setCustomerID( Customer::generateCustomerID() );
        $customer->setCompanyName( $company_name );
        $customer->setFirstName( $first_name );
        $customer->setMiddleName( $middle_name );
        $customer->setLastName( $last_name );
        $customer->setPrimaryEmail( $primary_email );
        $customer->setPrimaryMobile( $primary_mobile );
        $customer->setCustomerSource( '20365841' );
        $customer->setCreatedAt( create_iso_8601_datetime() );

        $created_customer = $customer->createCustomer();
        if ( $created_customer === null ) {
            echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Failed to create customer." );
            return;
        }
        $customer_id = $created_customer->getCustomerID();
    }

    // ── 4. Resolve or create invoice ──────────────────────────────
    $e_invoice_no   = escape_string( $invoice_no );
    $sql_inv        = "SELECT invoice_id FROM order_invoices WHERE invoice_no = '$e_invoice_no' LIMIT 1";
    $res_inv        = selectQuery( $sql_inv );
    $invoice_id     = null;

    if ( $res_inv && mysqli_num_rows( $res_inv ) > 0 ) {
        $inv_row    = mysqli_fetch_assoc( $res_inv );
        $invoice_id = $inv_row[ 'invoice_id' ];

        // Update customer_id on existing invoice if not set
        $e_inv_id  = escape_string( $invoice_id );
        $e_cust_id = escape_string( $customer_id );
        updateQuery( "UPDATE order_invoices SET customer_id = '$e_cust_id' WHERE invoice_id = '$e_inv_id' AND (customer_id IS NULL OR customer_id = '')" );

    } else {
        $invoice = new OrderInvoice();
        $invoice->setInvoiceNo( $invoice_no );
        $invoice->setInvoiceDate( $invoice_date );

        $created_invoice = $invoice->createInvoice();
        if ( $created_invoice === null ) {
            echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Failed to create invoice." );
            return;
        }
        $invoice_id    = $created_invoice->getInvoiceID();
        $e_inv_id      = escape_string( $invoice_id );
        $e_cust_id_new = escape_string( $customer_id );
        updateQuery( "UPDATE order_invoices SET customer_id = '$e_cust_id_new' WHERE invoice_id = '$e_inv_id'" );
    }

    // ── 5. Calculate warranty period in days ──────────────────────
    $warranty_period_months_int = intval( $warranty_period_months );
    $start_dt                   = new DateTime( $warranty_start_date );
    $end_dt                     = clone $start_dt;
    $end_dt->modify( '+' . $warranty_period_months_int . ' months' );
    $warranty_period_days       = (int) $start_dt->diff( $end_dt )->days;

    // ── 6. Process each serial number ─────────────────────────────
    $processed_sns = array();
    $e_product_id  = escape_string( $product_id );

    foreach ( $serial_numbers as $sn_data ) {
        $sn             = isset( $sn_data[ 'serial_number' ] ) ? trim( $sn_data[ 'serial_number' ] ) : '';
        $verified       = isset( $sn_data[ 'verified' ] )      ? $sn_data[ 'verified' ]              : '';
        $product_sn_id  = isset( $sn_data[ 'product_sn_id' ] ) ? $sn_data[ 'product_sn_id' ]         : null;

        if ( empty( $sn ) ) { continue; }

        // ── 6a. Get or create product_sn ──────────────────────────
        if ( empty( $product_sn_id ) ) {
            $e_sn          = escape_string( $sn );
            $sql_sn        = "SELECT product_sn_id FROM product_sn WHERE serial_number = '$e_sn' LIMIT 1";
            $res_sn        = selectQuery( $sql_sn );
            if ( $res_sn && mysqli_num_rows( $res_sn ) > 0 ) {
                $sn_row        = mysqli_fetch_assoc( $res_sn );
                $product_sn_id = $sn_row[ 'product_sn_id' ];
            } else {
                // Create new product_sn
                $new_psn_id    = ProductSN::generateProductSnID();
                $created_on    = create_iso_8601_datetime();
                $e_sn          = escape_string( $sn );
                $sql_insert    = "INSERT INTO product_sn ( product_sn_id, product_id, serial_number, created_on, allotted_to_customer )
                                  VALUES ( '$new_psn_id', '$e_product_id', '$e_sn', '$created_on', 0 )";
                $rows_inserted = insertQuery( $sql_insert );
                if ( $rows_inserted === 0 ) { continue; }
                $product_sn_id = $new_psn_id;
            }
        }

        $e_psn_id = escape_string( $product_sn_id );

        // ── 6b. Guard — skip if already allotted ──────────────────────
        $sql_allotted = "SELECT allotted_to_customer FROM product_sn WHERE product_sn_id = '$e_psn_id' LIMIT 1";
        $res_allotted = selectQuery( $sql_allotted );
        if ( $res_allotted && mysqli_num_rows( $res_allotted ) > 0 ) {
            $row_allotted = mysqli_fetch_assoc( $res_allotted );
            if ( intval( $row_allotted[ 'allotted_to_customer' ] ) === 1 ) {
                continue;
            }
        }

        // ── 6b. Mark as allotted ──────────────────────────────────────
        updateQuery( "UPDATE product_sn SET allotted_to_customer = 1 WHERE product_sn_id = '$e_psn_id'" );

        // ── 6c. Link invoice to product_sn ─────────────────────────
        $e_inv_id  = escape_string( $invoice_id );
        $sql_ipsn  = "SELECT id FROM invoice_product_sn WHERE invoice_id = '$e_inv_id' AND product_sn_id = '$e_psn_id' LIMIT 1";
        $res_ipsn  = selectQuery( $sql_ipsn );
        if ( !$res_ipsn || mysqli_num_rows( $res_ipsn ) === 0 ) {
            $ipsn = new InvoiceProductSN();
            $ipsn->setInvoiceID( $invoice_id );
            $ipsn->setProductSnID( $product_sn_id );
            $ipsn->createInvoiceProductSN();
        }

        // ── 6d. Create warranty ────────────────────────────────────
        $psnw_id = null;
        $sql_existing_warranty = "SELECT psnw_id FROM product_sn_warranty WHERE product_sn_id = '$e_psn_id' LIMIT 1";
        $res_existing_warranty = selectQuery( $sql_existing_warranty );

        if ( $res_existing_warranty && mysqli_num_rows( $res_existing_warranty ) > 0 ) {
            $w_row   = mysqli_fetch_assoc( $res_existing_warranty );
            $psnw_id = $w_row[ 'psnw_id' ];
        } else {
            $warranty = new ProductSNWarranty();
            $warranty->setProductSnID( $product_sn_id );
            $warranty->setWarrantyStartDate( $warranty_start_date );
            $warranty->setWarrantyPeriod( $warranty_period_days );
            $warranty->setRemarks( '' );
            $created_warranty = $warranty->createWarranty();
            if ( $created_warranty !== null ) {
                $psnw_id = $created_warranty->getPsnwID();
            }
        }

        $processed_sns[] = array(
            'serial_number'  => $sn,
            'product_sn_id'  => $product_sn_id,
            'psnw_id'        => $psnw_id
        );
    }

    // ── 7. Handle replacement if enabled ──────────────────────────
    $replacement_result = null;
    if ( $replacement_enabled === '1' ) {
        $date_of_service    = request( 'date_of_service' );
        $new_invoice_no     = request( 'new_invoice_no' );
        $replacement_sn     = request( 'replacement_sn' );
        $replacement_remark = request( 'replacement_remark' );

        // Only process if we have exactly one allotted serial number (replacement case)
        if ( count( $processed_sns ) === 1 && !empty( $psnw_id ) ) {

            // Resolve new invoice
            $new_invoice_id = null;
            if ( !empty( $new_invoice_no ) ) {
                $e_new_inv_no  = escape_string( $new_invoice_no );
                $sql_new_inv   = "SELECT invoice_id FROM order_invoices WHERE invoice_no = '$e_new_inv_no' LIMIT 1";
                $res_new_inv   = selectQuery( $sql_new_inv );
                if ( $res_new_inv && mysqli_num_rows( $res_new_inv ) > 0 ) {
                    $new_inv_row    = mysqli_fetch_assoc( $res_new_inv );
                    $new_invoice_id = $new_inv_row[ 'invoice_id' ];
                }
            }

            // Resolve new product_sn for replacement serial
            $new_product_sn_id = null;
            if ( !empty( $replacement_sn ) ) {
                $e_rep_sn      = escape_string( $replacement_sn );
                $sql_rep_sn    = "SELECT product_sn_id FROM product_sn WHERE serial_number = '$e_rep_sn' LIMIT 1";
                $res_rep_sn    = selectQuery( $sql_rep_sn );
                if ( $res_rep_sn && mysqli_num_rows( $res_rep_sn ) > 0 ) {
                    $rep_sn_row        = mysqli_fetch_assoc( $res_rep_sn );
                    $new_product_sn_id = $rep_sn_row[ 'product_sn_id' ];
                }
            }

            // Create replacement record
            $replacement = new ProductWarrantyReplacement();
            $replacement->setPsnwID( $psnw_id );
            $replacement->setIsRepairable( 0 );
            $replacement->setDateOfService( $date_of_service );
            $replacement->setNewInvoiceID( $new_invoice_id );
            $replacement->setNewProductSnID( $new_product_sn_id );
            $replacement->setRemarks( $replacement_remark );
            $created_replacement = $replacement->createReplacement();

            if ( $created_replacement !== null ) {
                $replacement_result = array(
                    'pwrp_id'          => $created_replacement->getPwrpID(),
                    'date_of_service'  => $date_of_service,
                    'new_product_sn_id'=> $new_product_sn_id
                );
            }
        }
    }

    // ── 8. Return success ──────────────────────────────────────────
    $responseData = array(
        'message'     => "Serial number(s) linked to customer successfully.",
        'customer_id' => $customer_id,
        'invoice_id'  => $invoice_id,
        'serial_numbers' => $processed_sns,
        'replacement' => $replacement_result
    );

    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $responseData );
}

?>