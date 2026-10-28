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

    // ── 4. Decode script (pyzbar) ──────────────────────────────────
    $decode_script = LIB_PATH . FILE_SEPARATOR . 'zbar' . FILE_SEPARATOR . 'decode.py';

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
    $cleaned = strtoupper( preg_replace( '/[^A-Za-z0-9]/', '', $decoded_raw ) );    // Letters kept: some serials carry them (v1.0.4)

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

/**
 * Returns the warranty information of every TV that carries the given serial number (v1.0.4)
 *
 * Two TVs can carry the same serial number (a manufacturer batch was delivered with duplicate serials), so one
 * record is returned per product_sn row. Each record carries the TV's own sale and every warranty history the TV
 * is part of: as the original TV (its own warranty) and as a replacement given out under another TV's warranty
 * (a replaced TV can be repaired and given to another customer). Each history lists the original TV, the warranty
 * and extended warranty, and ALL service events in date order. A replacement TV carries the rest of the original
 * TV's warranty, so the status always comes from the original warranty.
 *
 * @returns void  Echoes a JSON message
 */
function appstv_read_sn_info() {
    checkAuthorizationForFunction( __FUNCTION__ );

    $serial_number = trim( (string) request( 'serial_number' ) );

    // ── Validate ───────────────────────────────────────────────────
    // Letters are allowed: some models carry them in the serial number (e.g. 10250S26040001)
    validateEmptyString( $serial_number, __FUNCTION__, "Serial number is required." );
    validate( $serial_number, __FUNCTION__, "/^[A-Za-z0-9]{1,50}$/", "Serial number is invalid." );

    $e_serial_number = escape_string( $serial_number );

    // ── 1. Look up ALL TVs with this serial number ─────────────────
    $sql        = "SELECT ps.product_sn_id, ps.product_id, ps.serial_number, ps.created_on, ps.allotted_to_customer,
                          p.title AS product_title, p.name AS product_name
                   FROM product_sn ps
                   LEFT JOIN products p ON ps.product_id = p.product_id
                   WHERE ps.serial_number = '$e_serial_number'
                   ORDER BY ps.id";
    $result_set = selectQuery( $sql );

    if ( $result_set === NULL || mysqli_num_rows( $result_set ) === 0 ) {
        $responseData = array(
            'message'         => "No product found with this serial number.",
            'modal_direction' => 'do_not_close_modal'
        );
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, $responseData );
        return;
    }

    // ── 2. One record per TV ───────────────────────────────────────
    $records = array();
    while ( $product_sn = mysqli_fetch_assoc( $result_set ) ) {

        $product_sn_id   = $product_sn[ 'product_sn_id' ];      // This TV
        $e_product_sn_id = escape_string( $product_sn_id );

        // Warranties this TV is part of: its own, and those it was given out under as a replacement
        $sql_w = "SELECT w.* FROM product_sn_warranty w WHERE w.product_sn_id = '$e_product_sn_id'
                  UNION
                  SELECT w.* FROM product_sn_warranty w
                  INNER JOIN product_warranty_replacements r ON r.psnw_id = w.psnw_id
                  WHERE r.new_product_sn_id = '$e_product_sn_id'";
        $result_w  = selectQuery( $sql_w );
        $histories = array();                                   // Warranty histories of this TV
        while ( $result_w && ( $warranty = mysqli_fetch_assoc( $result_w ) ) ) {
            $histories[] = appstv_build_warranty_history( $warranty, $product_sn_id );
        }

        // Oldest warranty first, so the history reads in time order
        usort( $histories, function ( $a, $b ) {
            return strcmp( (string) $a[ 'warranty_start_date' ], (string) $b[ 'warranty_start_date' ] );
        } );

        $records[] = array(
            'product_title' => $product_sn[ 'product_title' ],
            'product_name'  => $product_sn[ 'product_name' ],
            'serial_number' => $product_sn[ 'serial_number' ],
            'sale'          => appstv_read_sale_of_product_sn( $product_sn_id ),
            'histories'     => $histories
        );
    }

    // ── 3. Return all records ──────────────────────────────────────
    $responseData = array(
        'message'       => "Warranty information retrieved successfully.",
        'total_records' => count( $records ),
        'data'          => $records
    );

    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $responseData );
}

/**
 * Reads the sale of one TV: its invoice and the customer on that invoice
 *
 * @param string $product_sn_id  The TV
 * @return array|null  invoice_no, invoice_date, company_name, first_name, last_name; null when the TV was never invoiced
 */
function appstv_read_sale_of_product_sn( $product_sn_id ) {
    $e_product_sn_id = escape_string( $product_sn_id );

    $sql    = "SELECT oi.invoice_no, oi.invoice_date, c.company_name, c.first_name, c.last_name
               FROM invoice_product_sn ipsn
               INNER JOIN order_invoices oi ON ipsn.invoice_id = oi.invoice_id
               LEFT JOIN customers c ON c.customer_id = oi.customer_id
               WHERE ipsn.product_sn_id = '$e_product_sn_id'
               ORDER BY ipsn.id
               LIMIT 1";
    $result = selectQuery( $sql );

    return ( $result && mysqli_num_rows( $result ) > 0 ) ? mysqli_fetch_assoc( $result ) : null;
}

/**
 * Builds one warranty history: the original TV and its sale, the warranty and extended warranty with the status,
 * and every service event (repairs and replacements) in date order
 *
 * @param array  $warranty              Row of product_sn_warranty
 * @param string $scanned_product_sn_id The TV that was looked up, to mark where it appears in the history
 * @return array
 */
function appstv_build_warranty_history( $warranty, $scanned_product_sn_id ) {
    $e_psnw_id = escape_string( $warranty[ 'psnw_id' ] );

    // ── Original TV ────────────────────────────────────────────────
    $e_original_id   = escape_string( $warranty[ 'product_sn_id' ] );
    $sql_original    = "SELECT ps.serial_number, p.title AS product_title
                        FROM product_sn ps
                        LEFT JOIN products p ON ps.product_id = p.product_id
                        WHERE ps.product_sn_id = '$e_original_id'
                        LIMIT 1";
    $result_original = selectQuery( $sql_original );
    $original        = ( $result_original && mysqli_num_rows( $result_original ) > 0 ) ? mysqli_fetch_assoc( $result_original ) : array();

    // ── Warranty and extended warranty ─────────────────────────────
    $today           = new DateTime( 'now', new DateTimeZone( '+0800' ) );
    $warranty_expiry = null;                                    // DateTime the standard warranty ends
    if ( !empty( $warranty[ 'warranty_start_date' ] ) && !empty( $warranty[ 'warranty_period' ] ) ) {
        $warranty_expiry = new DateTime( $warranty[ 'warranty_start_date' ] );
        $warranty_expiry->modify( '+' . intval( $warranty[ 'warranty_period' ] ) . ' days' );
    }

    $extended        = null;                                    // Extended warranty, if one was bought
    $extended_expiry = null;                                    // DateTime the extended warranty ends
    $result_ext      = selectQuery( "SELECT * FROM product_sn_extended_warranty WHERE psnw_id = '$e_psnw_id' ORDER BY id LIMIT 1" );
    if ( $result_ext && mysqli_num_rows( $result_ext ) > 0 ) {
        $ext = mysqli_fetch_assoc( $result_ext );
        if ( !empty( $ext[ 'extended_warranty_start_date' ] ) && !empty( $ext[ 'extended_warranty_period' ] ) ) {
            $extended_expiry = new DateTime( $ext[ 'extended_warranty_start_date' ] );
            $extended_expiry->modify( '+' . intval( $ext[ 'extended_warranty_period' ] ) . ' days' );
        }
        $extended = array(
            'start_date'  => $ext[ 'extended_warranty_start_date' ],
            'expiry_date' => $extended_expiry ? $extended_expiry->format( 'Y-m-d' ) : null,
            'remarks'     => $ext[ 'remarks' ]
        );
    }

    $status = 'No Warranty Found';
    if ( $warranty_expiry !== null ) {
        if ( $today <= $warranty_expiry ) {
            $status = 'Active';
        }
        else if ( $extended_expiry !== null && $today <= $extended_expiry ) {
            $status = 'Active (Extended)';
        }
        else {
            $status = 'Expired';
        }
    }

    // ── Service events, oldest first ───────────────────────────────
    $sql_events    = "SELECT r.*, ns.serial_number AS new_serial_number, np.title AS new_product_title, ni.invoice_no AS new_invoice_no
                      FROM product_warranty_replacements r
                      LEFT JOIN product_sn ns ON ns.product_sn_id = r.new_product_sn_id
                      LEFT JOIN products np ON np.product_id = ns.product_id
                      LEFT JOIN order_invoices ni ON ni.invoice_id = r.new_invoice_id
                      WHERE r.psnw_id = '$e_psnw_id'
                      ORDER BY r.date_of_service, r.id";
    $result_events = selectQuery( $sql_events );
    $events        = array();
    $current_sn    = isset( $original[ 'serial_number' ] ) ? $original[ 'serial_number' ] : null;   // TV in service now
    while ( $result_events && ( $event = mysqli_fetch_assoc( $result_events ) ) ) {
        $is_replacement = !empty( $event[ 'new_product_sn_id' ] );
        if ( $is_replacement ) {
            $current_sn = $event[ 'new_serial_number' ];
        }
        $events[] = array(
            'date_of_service'   => $event[ 'date_of_service' ],
            'type'              => $is_replacement ? 'replacement' : 'repair',
            'new_serial_number' => $event[ 'new_serial_number' ],
            'new_product_title' => $event[ 'new_product_title' ],
            'new_invoice_no'    => $event[ 'new_invoice_no' ],
            'is_repairable'     => $event[ 'is_repairable' ],
            'remarks'           => $event[ 'remarks' ],
            'is_scanned_tv'     => ( $is_replacement && $event[ 'new_product_sn_id' ] === $scanned_product_sn_id )
        );
    }

    return array(
        'role'                   => ( $warranty[ 'product_sn_id' ] === $scanned_product_sn_id ) ? 'original' : 'replacement',
        'original_serial_number' => isset( $original[ 'serial_number' ] ) ? $original[ 'serial_number' ] : null,
        'original_product_title' => isset( $original[ 'product_title' ] ) ? $original[ 'product_title' ] : null,
        'original_sale'          => appstv_read_sale_of_product_sn( $warranty[ 'product_sn_id' ] ),
        'warranty_start_date'    => $warranty[ 'warranty_start_date' ],
        'warranty_expiry'        => $warranty_expiry ? $warranty_expiry->format( 'Y-m-d' ) : null,
        'extended_warranty'      => $extended,
        'warranty_status'        => $status,
        'remarks'                => $warranty[ 'remarks' ],
        'events'                 => $events,
        'current_serial_number'  => $current_sn
    );
}
?>
