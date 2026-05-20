<?php

/**
 * Decodes a QR code or Barcode from an uploaded image using ZBar
 * Accepts an image file via $_FILES and returns the decoded serial number
 */
function appstv_decode_qr_barcode() {
    checkAuthorizationForFunction( __FUNCTION__ );

    // ── 1. Validate uploaded file ──────────────────────────────────
    if ( !isset( $_FILES[ 'scan_image' ] ) || $_FILES[ 'scan_image' ][ 'error' ] !== UPLOAD_ERR_OK ) {
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "No image was uploaded or an upload error occurred." );
        return;
    }

    $allowed_mime_types = [ 'image/jpeg', 'image/png' ];
    $uploaded_mime      = mime_content_type( $_FILES[ 'scan_image' ][ 'tmp_name' ] );

    if ( !in_array( $uploaded_mime, $allowed_mime_types ) ) {
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Invalid file type. Only JPG and PNG images are allowed." );
        return;
    }

    // ── 2. Prepare tmp directory ───────────────────────────────────
    $tmp_dir = PLU_PATH . FILE_SEPARATOR . "appstv_warranty" . FILE_SEPARATOR . "data" . FILE_SEPARATOR . "tmp";

    if ( !is_dir( $tmp_dir ) ) {
        if ( !mkdir( $tmp_dir, 0755, true ) ) {
            echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Failed to create temporary directory." );
            return;
        }
    }

    // ── 3. Save uploaded file to tmp directory ─────────────────────
    $ext      = ( $uploaded_mime === 'image/png' ) ? 'png' : 'jpg';
    $tmp_file = $tmp_dir . FILE_SEPARATOR . "scan_" . time() . "_" . mt_rand( 1000, 9999 ) . "." . $ext;

    if ( !move_uploaded_file( $_FILES[ 'scan_image' ][ 'tmp_name' ], $tmp_file ) ) {
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Failed to save the uploaded image." );
        return;
    }

    // ── 4. Fetch customer via order_invoices.customer_id ──────────
    $customer = null;
    if ( $invoice !== null && !empty( $invoice[ 'customer_id' ] ) ) {
        $e_customer_id  = escape_string( $invoice[ 'customer_id' ] );
        $sql_customer   = "SELECT customer_id, company_name, first_name, last_name,
                                  primary_email, primary_mobile
                           FROM customers
                           WHERE customer_id = '$e_customer_id'
                           LIMIT 1";
        $result_cust    = selectQuery( $sql_customer );
        $customer       = ( $result_cust && mysqli_num_rows( $result_cust ) > 0 )
                           ? mysqli_fetch_assoc( $result_cust )
                           : null;
    }

    // Use 'python' on Windows, 'python3' on Ubuntu
    $python_bin = ( strtoupper( substr( PHP_OS, 0, 3 ) ) === 'WIN' ) ? 'python' : 'python3';

    // ── 5. Run Python decode script ────────────────────────────────
    $escaped_python = escapeshellarg( $python_bin );
    $escaped_script = escapeshellarg( $decode_script );
    $escaped_file   = escapeshellarg( $tmp_file );
    $command        = "$escaped_python $escaped_script $escaped_file 2>&1";

    $output      = [];
    $return_code = 0;
    exec( $command, $output, $return_code );

    // ── 6. Clean up tmp file ───────────────────────────────────────
    @unlink( $tmp_file );

    // ── 7. Parse output ────────────────────────────────────────────
    $decoded_raw = isset( $output[0] ) ? trim( $output[0] ) : '';

    if ( $return_code !== 0 || $decoded_raw === '' || strpos( $decoded_raw, 'ERROR:' ) === 0 ) {
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "No QR code or barcode was detected in the image." );
        return;
    }

    // ── 8. Validate serial number format ──────────────────────────
    $cleaned = preg_replace( '/\D/', '', $decoded_raw );

    if ( strlen( $cleaned ) === 0 || strlen( $cleaned ) > 50 ) {
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "This is not a valid serial number." );
        return;
    }

    // ── 9. Return success ──────────────────────────────────────────
    $data = [
        "serial_number" => $cleaned
    ];

    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $data );
}

function appstv_read_sn_info() {
    checkAuthorizationForFunction( __FUNCTION__ );

    $serial_number = request( 'serial_number' );

    // ── Validate ───────────────────────────────────────────────────
    validateEmptyString( $serial_number, __FUNCTION__, "Serial number is required." );
    validate( $serial_number, __FUNCTION__, "/^\d{1,50}$/", "Serial number is invalid." );

    $e_serial_number = escape_string( $serial_number );

    // ── 1. Look up ALL matching serial numbers in product_sn ───────
    $sql        = "SELECT ps.product_sn_id, ps.product_id, ps.serial_number, ps.created_on, ps.allotted_to_customer,
                          p.title AS product_title, p.name AS product_name
                   FROM product_sn ps
                   LEFT JOIN products p ON ps.product_id = p.product_id
                   WHERE ps.serial_number = '$e_serial_number'";
    $result_set = selectQuery( $sql );

    if ( $result_set === NULL || mysqli_num_rows( $result_set ) === 0 ) {
        $responseData = array(
            'message'         => "No product found with this serial number.",
            'modal_direction' => 'do_not_close_modal'
        );
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, $responseData );
        return;
    }

    // ── 2. Build records array — one entry per product_sn row ──────
    $records = array();

    while ( $product_sn = mysqli_fetch_assoc( $result_set ) ) {
        $product_sn_id = escape_string( $product_sn[ 'product_sn_id' ] );

        // ── 3. Fetch invoice via invoice_product_sn ────────────────
        $sql_invoice = "SELECT oi.invoice_id, oi.invoice_no, oi.invoice_date, oi.customer_id
                FROM invoice_product_sn ipsn
                INNER JOIN order_invoices oi ON ipsn.invoice_id = oi.invoice_id
                WHERE ipsn.product_sn_id = '$product_sn_id'
                LIMIT 1";
        $result_inv  = selectQuery( $sql_invoice );
        $invoice     = ( $result_inv && mysqli_num_rows( $result_inv ) > 0 )
                        ? mysqli_fetch_assoc( $result_inv )
                        : null;

        // ── 4. Fetch customer via invoice_no in customers ──────────
        // ── 4. Fetch customer via order_invoices.customer_id ──────────
        $customer = null;
        //$debug_customer_id = null;
        if ( $invoice !== null ) {
            //$debug_customer_id = isset( $invoice[ 'customer_id' ] ) ? $invoice[ 'customer_id' ] : 'KEY_NOT_FOUND';
            if ( !empty( $invoice[ 'customer_id' ] ) ) {
                $e_customer_id  = escape_string( $invoice[ 'customer_id' ] );
                $sql_customer   = "SELECT customer_id, company_name, first_name, last_name,
                                          primary_email, primary_mobile
                                   FROM customers
                                   WHERE customer_id = '$e_customer_id'
                                   LIMIT 1";
                $result_cust    = selectQuery( $sql_customer );
                $customer       = ( $result_cust && mysqli_num_rows( $result_cust ) > 0 )
                                   ? mysqli_fetch_assoc( $result_cust )
                                   : null;
            }
        }

        // ── 5. Fetch warranty ──────────────────────────────────────
        $sql_warranty    = "SELECT * FROM product_sn_warranty WHERE product_sn_id = '$product_sn_id' LIMIT 1";
        $result_warranty = selectQuery( $sql_warranty );
        $warranty        = ( $result_warranty && mysqli_num_rows( $result_warranty ) > 0 )
                            ? mysqli_fetch_assoc( $result_warranty )
                            : null;

        // ── 6. Fetch extended warranty ─────────────────────────────
        $extended_warranty = null;
        if ( $warranty !== null ) {
            $e_psnw_id       = escape_string( $warranty[ 'psnw_id' ] );
            $sql_ext         = "SELECT * FROM product_sn_extended_warranty WHERE psnw_id = '$e_psnw_id' LIMIT 1";
            $result_ext      = selectQuery( $sql_ext );
            $extended_warranty = ( $result_ext && mysqli_num_rows( $result_ext ) > 0 )
                                  ? mysqli_fetch_assoc( $result_ext )
                                  : null;
        }

        // ── 7. Fetch replacement ───────────────────────────────────
        $replacement = null;
        if ( $warranty !== null ) {
            $e_psnw_id    = escape_string( $warranty[ 'psnw_id' ] );
            $sql_rep      = "SELECT * FROM product_warranty_replacements WHERE psnw_id = '$e_psnw_id' LIMIT 1";
            $result_rep   = selectQuery( $sql_rep );
            $replacement  = ( $result_rep && mysqli_num_rows( $result_rep ) > 0 )
                             ? mysqli_fetch_assoc( $result_rep )
                             : null;
        }

        // ── 8. Calculate warranty expiry and status ────────────────
        $warranty_status = 'No Warranty Found';
        $warranty_expiry = null;

        if ( $warranty !== null && !empty( $warranty[ 'warranty_start_date' ] ) && !empty( $warranty[ 'warranty_period' ] ) ) {
            $start_date      = new DateTime( $warranty[ 'warranty_start_date' ] );
            $expiry_date     = clone $start_date;
            $expiry_date->modify( '+' . intval( $warranty[ 'warranty_period' ] ) . ' days' );
            $today           = new DateTime( 'now', new DateTimeZone( '+0800' ) );
            $warranty_expiry = $expiry_date->format( 'Y-m-d' );
            $warranty_status = ( $today <= $expiry_date ) ? 'Active' : 'Expired';
        }

        // ── 9. Append record ───────────────────────────────────────
        $replacement_data = null;
        if ( $replacement !== null ) {

            // Fetch new serial number from product_sn
            $new_serial_number = null;
            if ( !empty( $replacement[ 'new_product_sn_id' ] ) ) {
                $e_new_psn_id  = escape_string( $replacement[ 'new_product_sn_id' ] );
                $sql_new_sn    = "SELECT serial_number FROM product_sn WHERE product_sn_id = '$e_new_psn_id' LIMIT 1";
                $result_new_sn = selectQuery( $sql_new_sn );
                if ( $result_new_sn && mysqli_num_rows( $result_new_sn ) > 0 ) {
                    $new_sn_row    = mysqli_fetch_assoc( $result_new_sn );
                    $new_serial_number = $new_sn_row[ 'serial_number' ];
                }
            }

            // Fetch new invoice no from order_invoices
            $new_invoice_no = null;
            if ( !empty( $replacement[ 'new_invoice_id' ] ) ) {
                $e_new_inv_id  = escape_string( $replacement[ 'new_invoice_id' ] );
                $sql_new_inv   = "SELECT invoice_no FROM order_invoices WHERE invoice_id = '$e_new_inv_id' LIMIT 1";
                $result_new_inv = selectQuery( $sql_new_inv );
                if ( $result_new_inv && mysqli_num_rows( $result_new_inv ) > 0 ) {
                    $new_inv_row   = mysqli_fetch_assoc( $result_new_inv );
                    $new_invoice_no = $new_inv_row[ 'invoice_no' ];
                }
            }

            $replacement_data = array(
                'date_of_service'   => $replacement[ 'date_of_service' ],
                'new_serial_number' => $new_serial_number,
                'new_invoice_no'    => $new_invoice_no,
                'is_repairable'     => $replacement[ 'is_repairable' ],
                'remarks'           => $replacement[ 'remarks' ]
            );
        }

        $records[] = array(
            'product_title'       => $product_sn[ 'product_title' ],
            'product_name'        => $product_sn[ 'product_name' ],
            'serial_number'       => $product_sn[ 'serial_number' ],
            'invoice_no'          => $invoice  ? $invoice[ 'invoice_no' ]            : null,
            'invoice_date'        => $invoice  ? $invoice[ 'invoice_date' ]           : null,
            'company_name'        => $customer ? $customer[ 'company_name' ]          : null,
            'first_name'          => $customer ? $customer[ 'first_name' ]            : null,
            'last_name'           => $customer ? $customer[ 'last_name' ]             : null,
            'warranty_start_date' => $warranty ? $warranty[ 'warranty_start_date' ]   : null,
            'warranty_expiry'     => $warranty_expiry,
            'warranty_status'     => $warranty_status,
            'remarks'             => $warranty ? $warranty[ 'remarks' ]               : null,
            'replacement'         => $replacement_data
        );
    }

    // ── 10. Return all records ─────────────────────────────────────
    $responseData = array(
        'message'       => "Warranty information retrieved successfully.",
        'total_records' => count( $records ),
        'data'          => $records
    );

    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $responseData );
}
?>