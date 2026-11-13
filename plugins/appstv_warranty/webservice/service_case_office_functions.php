<?php

/*
 * Service cases: TVs at the office, delivery, returned TVs, closing by itself (v1.0.34)
 *
 *   at_office  (consent signed)  -- office: repaired there | beyond repair, replaced -->  ready (to deliver)
 *   ready                        -- delivered and installed, with photos             -->  delivered
 * Both office results are written into the warranty records (appstv_case_service_core); a replacement TV joins
 * the case. The TV that was replaced comes back to the office as a returned TV: marked repaired it is "ready to
 * give out" and can be given to another customer as a replacement (the same TV record is used again).
 * A case closes by itself when every TV is done (not covered / no repair / repaired / delivered), a main case when
 * all its TV cases are closed.
 */

define( 'APPSTV_CASE_OFFICE', 'appstv_case_office' );                 // Permission: office repair / replacement
define( 'APPSTV_STOCK_STATES', array(
    'returned'       => 'Returned, faulty',
    'repaired_stock' => 'Repaired, ready to give out',
    'given'          => 'Given out again'
) );
define( 'APPSTV_CASE_DONE_RESULTS', array( 'not_covered', 'declined', 'repaired', 'delivered' ) );


/**
 * @description Sets the office stock state of a TV and writes it in its stock log
 * @param {string} $product_sn_id - The TV
 * @param {string} $state - returned | repaired_stock | given
 * @param {int|null} $case_id - Case it came back from / went out with (null = none)
 * @param {string} $note - What happened
 * @returns {void}
 */
function appstv_case_stock_set( $product_sn_id, $state, $case_id, $note ){
    $e_id = escape_string( $product_sn_id );
    $e_user = escape_string( appstv_case_user_id() );
    $now = create_iso_8601_datetime();
    $sql_case = ( $case_id === null ) ? "NULL" : (int) $case_id;
    $e_state = escape_string( $state );
    appstv_case_db( "INSERT INTO product_sn_stock ( product_sn_id, state, case_id, updated_by, updated_on ) VALUES ( '$e_id', '$e_state', $sql_case, '$e_user', '$now' )
                     ON DUPLICATE KEY UPDATE state = VALUES( state ), case_id = VALUES( case_id ), updated_by = VALUES( updated_by ), updated_on = VALUES( updated_on )" );
    appstv_case_db( "INSERT INTO product_sn_stock_log ( product_sn_id, state, note, case_id, user_id, created_on )
                     VALUES ( '$e_id', '$e_state', " . ( $note === '' ? "NULL" : "'" . escape_string( mb_substr( $note, 0, 2000 ) ) . "'" ) . ", $sql_case, '$e_user', '$now' )" );
}

/**
 * @description Office stock state and log of a TV (Warranty page)
 * @param {string} $product_sn_id - The TV
 * @returns {array|null} { state, state_text, log: [ { state, state_text, note, case_label, by, on } ] }, null = never returned
 */
function appstv_case_stock_info( $product_sn_id ){
    $e_id = escape_string( $product_sn_id );
    $row = mysqli_fetch_assoc( appstv_case_db( "SELECT state FROM product_sn_stock WHERE product_sn_id = '$e_id'" ) );
    if( $row === null ){ return null; }
    $log = array();
    foreach( appstv_case_rows( "SELECT l.*, c.id AS cid, c.case_kind, c.parent_case_id, c.tv_index FROM product_sn_stock_log l
                                LEFT JOIN service_cases c ON c.id = l.case_id WHERE l.product_sn_id = '$e_id' ORDER BY l.id" ) as $l ){
        $log[] = array( 'state' => $l[ 'state' ], 'state_text' => APPSTV_STOCK_STATES[ $l[ 'state' ] ] ?? $l[ 'state' ], 'note' => (string) $l[ 'note' ],
                        'case_label' => $l[ 'cid' ] === null ? '' : appstv_case_label( array( 'id' => $l[ 'cid' ], 'case_kind' => $l[ 'case_kind' ], 'parent_case_id' => $l[ 'parent_case_id' ], 'tv_index' => $l[ 'tv_index' ] ) ),
                        'by' => appstv_case_user_name( $l[ 'user_id' ] ), 'on' => $l[ 'created_on' ] );
    }
    return array( 'state' => $row[ 'state' ], 'state_text' => APPSTV_STOCK_STATES[ $row[ 'state' ] ] ?? $row[ 'state' ], 'log' => $log );
}


/**
 * @description Step 'office' of appstv_case_inspect: the office repaired the TV, or it is beyond repair and replaced.
 *              Answer: { value: repaired | replaced, note, new_serial, new_product_id, confirm_duplicate }
 * @param {array} $case - service_cases row (locked)
 * @param {array} $case_tv - service_case_tvs row (locked)
 * @param {array} $r - service_case_inspections row (locked)
 * @param {array|null} $chain - Warranty chain of the TV (null = not in the system)
 * @param {array} $in - Answer
 * @returns {array} [ message, data ]
 */
function appstv_case_inspect_office( $case, $case_tv, $r, $chain, $in ){
    if( !hasAuthorization( APPSTV_CASE_OFFICE ) ){ throw new AppstvServiceRuleException( "Only the office can mark a TV at the office as repaired or replaced." ); }
    $id = (int) $case[ 'id' ];
    $serial = $case_tv[ 'serial_number' ];
    if( (string) $r[ 'consent_id' ] === '' ){ throw new AppstvServiceRuleException( "TV $serial is not at the office." ); }
    if( $r[ 'office_result' ] !== '' ){ throw new AppstvServiceRuleException( "The office already marked TV $serial as " . ( $r[ 'office_result' ] === 'replaced' ? 'replaced' : 'repaired' ) . "." ); }
    $value = (string) ( $in[ 'value' ] ?? '' );
    $note = appstv_case_inspect_text( $in, 'note', APPSTV_INSPECT_NOTE_MAX, "The note" );
    if( !in_array( $value, array( 'repaired', 'replaced' ), true ) ){ throw new AppstvServiceRuleException( "Choose Repaired at the office, or Beyond repair: replace." ); }
    if( $note === '' ){ throw new AppstvServiceRuleException( $value === 'repaired' ? "Write what the office repaired." : "Write why the TV cannot be repaired." ); }

    $label = appstv_case_label( $case );
    $paid = ( $r[ 'paid' ] === 'yes' ) ? 'Paid repair. ' : '';
    $pwrp_id = null;
    $new_case_tv_id = null;
    $new_serial = '';
    $has_record = ( $chain !== null && $chain[ 'psnw_id' ] !== null );
    if( $value === 'replaced' ){
        if( !$has_record ){ throw new AppstvServiceRuleException( "TV $serial has no warranty record, so no replacement can be recorded on it." ); }
        $done = appstv_case_service_core( array(
            'product_sn_id' => $case_tv[ 'product_sn_id' ], 'type' => 'replacement', 'date' => appstv_case_today(), 'ref_no' => '',
            'remarks' => $paid . "Beyond repair, replaced at the office ($label): $note", 'case_id' => $id,
            'new_serial_number' => (string) ( $in[ 'new_serial' ] ?? '' ), 'new_product_id' => (string) ( $in[ 'new_product_id' ] ?? '' ),
            'confirm_duplicate' => !empty( $in[ 'confirm_duplicate' ] )
        ) );
        $pwrp_id = $done[ 'pwrp_id' ];
        $new_serial = $done[ 'new_serial' ];
        $row = mysqli_fetch_assoc( appstv_case_db( "SELECT id FROM service_case_tvs WHERE case_id = $id AND product_sn_id = '" . escape_string( $done[ 'new_product_sn_id' ] ) . "' ORDER BY id DESC LIMIT 1" ) );
        $new_case_tv_id = $row ? (int) $row[ 'id' ] : null;
    } else if( $has_record ){
        $done = appstv_case_service_core( array(
            'product_sn_id' => $case_tv[ 'product_sn_id' ], 'type' => 'repair', 'date' => appstv_case_today(), 'ref_no' => '',
            'remarks' => $paid . "Repaired at the office ($label): $note", 'case_id' => $id
        ) );
        $pwrp_id = $done[ 'pwrp_id' ];
    }

    $now = create_iso_8601_datetime();
    $e_user = escape_string( appstv_case_user_id() );
    appstv_case_db( "UPDATE service_case_inspections SET office_result = '$value', office_note = '" . escape_string( $note ) . "',
                            office_pwrp_id = " . ( $pwrp_id === null ? "NULL" : "'" . escape_string( $pwrp_id ) . "'" ) . ",
                            new_case_tv_id = " . ( $new_case_tv_id === null ? "NULL" : $new_case_tv_id ) . ", office_by = '$e_user', office_on = '$now',
                            result = 'ready', updated_by = '$e_user', updated_on = '$now' WHERE id = " . (int) $r[ 'id' ] );
    $what = ( $value === 'replaced' ) ? "beyond repair, replaced by $new_serial" : "repaired at the office";
    appstv_case_history( $id, null, 'inspected', 'office', null, null, "Inspection of TV $serial: $what - $note" );
    appstv_case_log( $id, 'inspect', "TV $serial: " . ucfirst( $what ) . " - $note" . ( $pwrp_id !== null ? ' (written into the warranty records)' : '' ) . ". Ready to deliver." );
    $r = mysqli_fetch_assoc( appstv_case_db( "SELECT * FROM service_case_inspections WHERE id = " . (int) $r[ 'id' ] ) );
    return array( "TV $serial: " . ( $value === 'replaced' ? "replacement $new_serial recorded" : "repair recorded" ) . ". Ready to deliver.",
                  array( 'inspection' => appstv_case_inspect_item( $r ) ) );
}

/**
 * @description Step 'deliver' of appstv_case_inspect: the repaired / replacement TV was delivered and installed
 *              (at least one photo, purpose 'delivery'). Answer: { note }
 * @param {array} $case - service_cases row (locked)
 * @param {array} $case_tv - service_case_tvs row (locked)
 * @param {array} $r - service_case_inspections row (locked)
 * @param {array} $in - Answer
 * @returns {array} [ message, data ]
 */
function appstv_case_inspect_deliver( $case, $case_tv, $r, $in ){
    $id = (int) $case[ 'id' ];
    $serial = $case_tv[ 'serial_number' ];
    if( $r[ 'office_result' ] === '' ){ throw new AppstvServiceRuleException( "TV $serial is not ready to deliver." ); }
    if( $r[ 'delivered_on' ] !== '' ){ throw new AppstvServiceRuleException( "TV $serial was already delivered." ); }
    $note = appstv_case_inspect_text( $in, 'note', APPSTV_INSPECT_NOTE_MAX, "The note" );
    if( appstv_case_inspect_photos( $case_tv[ 'id' ], 'delivery' ) === 0 ){ throw new AppstvServiceRuleException( "Add at least one photo of the TV installed and working." ); }
    $now = create_iso_8601_datetime();
    $e_user = escape_string( appstv_case_user_id() );
    appstv_case_db( "UPDATE service_case_inspections SET delivery_note = " . ( $note === '' ? "NULL" : "'" . escape_string( $note ) . "'" ) . ", delivered_by = '$e_user',
                            delivered_on = '$now', result = 'delivered', updated_by = '$e_user', updated_on = '$now', finished_on = '$now' WHERE id = " . (int) $r[ 'id' ] );
    $what = ( $r[ 'office_result' ] === 'replaced' ) ? 'replacement TV delivered and installed' : 'repaired TV delivered and installed';
    if( $r[ 'office_result' ] === 'replaced' && (string) $r[ 'new_case_tv_id' ] !== '' ){
        $new = mysqli_fetch_assoc( appstv_case_db( "SELECT serial_number FROM service_case_tvs WHERE id = " . (int) $r[ 'new_case_tv_id' ] ) );
        if( $new ){ $what = 'replacement TV ' . $new[ 'serial_number' ] . ' delivered and installed'; }
    }
    appstv_case_history( $id, null, 'inspected', 'delivery', null, null, "Inspection of TV $serial: $what" . ( $note !== '' ? " - $note" : '' ) );
    appstv_case_log( $id, 'inspect', "TV $serial: Delivered - " . $what . ( $note !== '' ? " - $note" : '' ) );
    $closed = appstv_case_auto_close( $id );
    $r = mysqli_fetch_assoc( appstv_case_db( "SELECT * FROM service_case_inspections WHERE id = " . (int) $r[ 'id' ] ) );
    return array( "TV $serial delivered." . ( $closed !== '' ? ' ' . $closed : '' ), array( 'inspection' => appstv_case_inspect_item( $r ), 'closed' => $closed !== '' ) );
}


/**
 * @description Closes a case by itself when every TV on it is done (and the number of TVs told is reached); a TV
 *              case also closes its main case when it was the last one open. Runs inside the caller's transaction.
 * @param {int} $case_id - service_cases.id
 * @returns {string} What was closed ('' = nothing)
 */
function appstv_case_auto_close( $case_id ){
    $case = appstv_case_read( $case_id, true );
    if( $case[ 'status' ] === 'closed' || $case[ 'case_kind' ] === 'main' ){ return ''; }
    $id = (int) $case[ 'id' ];
    // Replacement TVs given on this case are not TVs to inspect
    $tvs = appstv_case_rows( "SELECT t.id, i.result, i.office_result FROM service_case_tvs t
                              LEFT JOIN service_case_inspections i ON i.case_tv_id = t.id
                              WHERE t.case_id = $id
                                AND NOT EXISTS ( SELECT 1 FROM service_case_inspections x WHERE x.case_id = $id AND x.new_case_tv_id = t.id )" );
    if( count( $tvs ) === 0 || count( $tvs ) < max( 1, (int) $case[ 'tv_count' ] ) ){ return ''; }
    $kinds = array();
    foreach( $tvs as $t ){
        if( !in_array( (string) $t[ 'result' ], APPSTV_CASE_DONE_RESULTS, true ) ){ return ''; }
        if( $t[ 'result' ] === 'delivered' ){ $kinds[] = ( $t[ 'office_result' ] === 'replaced' ) ? 'replaced' : 'repaired'; }
        else if( $t[ 'result' ] === 'repaired' ){ $kinds[] = 'repaired'; }
        else { $kinds[] = 'not_covered'; }
    }
    $outcome = in_array( 'replaced', $kinds, true ) ? 'replaced' : ( in_array( 'repaired', $kinds, true ) ? 'repaired' : 'not_covered' );
    appstv_case_close_auto( $case, $outcome, 'every TV is done' );
    $message = "Case " . appstv_case_label( $case ) . " closed: every TV is done.";

    if( $case[ 'case_kind' ] === 'tv' ){
        $main = appstv_case_read( $case[ 'parent_case_id' ], true );
        $open = (int) mysqli_fetch_row( appstv_case_db( "SELECT COUNT(*) FROM service_cases WHERE parent_case_id = " . (int) $main[ 'id' ] . " AND case_kind = 'tv' AND status <> 'closed'" ) )[ 0 ];
        if( $main[ 'status' ] !== 'closed' && $open === 0 ){
            $outcomes = array_column( appstv_case_rows( "SELECT outcome FROM service_cases WHERE parent_case_id = " . (int) $main[ 'id' ] . " AND case_kind = 'tv'" ), 'outcome' );
            $main_outcome = in_array( 'replaced', $outcomes, true ) ? 'replaced' : ( in_array( 'repaired', $outcomes, true ) ? 'repaired' : 'not_covered' );
            appstv_case_close_auto( $main, $main_outcome, 'all its TV cases are closed' );
            $message .= " Main case SC-" . (int) $main[ 'id' ] . " closed too.";
        }
    }
    return $message;
}

/**
 * @description Closes one case by itself (timeline + history)
 * @param {array} $case - service_cases row (locked)
 * @param {string} $outcome - repaired | replaced | not_covered
 * @param {string} $why - Reason for the timeline
 * @returns {void}
 */
function appstv_case_close_auto( $case, $outcome, $why ){
    $id = (int) $case[ 'id' ];
    $now = create_iso_8601_datetime();
    $e_user = escape_string( appstv_case_user_id() );
    appstv_case_db( "UPDATE service_cases SET status = 'closed', outcome = '" . escape_string( $outcome ) . "', closed_by = '$e_user', closed_on = '$now' WHERE id = $id" );
    $labels = array( 'repaired' => 'Repaired', 'replaced' => 'Replaced', 'no_fault' => 'No fault found', 'not_covered' => 'Not covered by warranty' );
    appstv_case_log( $id, 'close', 'Case closed automatically: ' . $why . ' (' . $labels[ $outcome ] . ')' );
    appstv_case_history( $id, null, 'closed', 'status', $case[ 'status' ], 'closed', 'closed the case automatically: ' . $why . ' (' . strtolower( $labels[ $outcome ] ) . ')' );
}


/**
 * Returned TVs: TVs that were replaced and came back to the office (faulty, or repaired and ready to give out)
 */
function appstv_returned_tv_list(){
    checkAuthorizationForFunction( __FUNCTION__ );
    appstv_case_answer( __FUNCTION__, function(){
        $out = array();
        foreach( appstv_case_rows( "SELECT st.*, ps.serial_number, p.name AS product_name, c.id AS cid, c.case_kind, c.parent_case_id, c.tv_index,
                                           ( SELECT l.note FROM product_sn_stock_log l WHERE l.product_sn_id = st.product_sn_id ORDER BY l.id DESC LIMIT 1 ) AS note
                                    FROM product_sn_stock st
                                    JOIN product_sn ps ON ps.product_sn_id = st.product_sn_id
                                    LEFT JOIN products p ON p.product_id = ps.product_id
                                    LEFT JOIN service_cases c ON c.id = st.case_id
                                    WHERE st.state IN ( 'returned', 'repaired_stock' )
                                    ORDER BY ( st.state = 'repaired_stock' ), st.updated_on DESC
                                    LIMIT 500" ) as $row ){
            $out[] = array( 'product_sn_id' => $row[ 'product_sn_id' ], 'serial_number' => $row[ 'serial_number' ], 'product_name' => (string) $row[ 'product_name' ],
                            'state' => $row[ 'state' ], 'state_text' => APPSTV_STOCK_STATES[ $row[ 'state' ] ],
                            'case_label' => $row[ 'cid' ] === null ? '' : appstv_case_label( array( 'id' => $row[ 'cid' ], 'case_kind' => $row[ 'case_kind' ], 'parent_case_id' => $row[ 'parent_case_id' ], 'tv_index' => $row[ 'tv_index' ] ) ),
                            'case_id' => $row[ 'cid' ] === null ? null : (int) $row[ 'cid' ],
                            'note' => (string) $row[ 'note' ], 'by' => appstv_case_user_name( $row[ 'updated_by' ] ), 'on' => $row[ 'updated_on' ] );
        }
        return array( count( $out ) . " returned TV(s).", array( 'tvs' => $out, 'can_set' => hasAuthorization( 'appstv_returned_tv_set' ) ) );
    } );
}

/**
 * Marks a returned TV as repaired (ready to give out, a note is needed) or as faulty again
 */
function appstv_returned_tv_set(){
    checkAuthorizationForFunction( __FUNCTION__ );
    appstv_case_transaction( __FUNCTION__, function(){
        $product_sn_id = trim( (string) request( 'product_sn_id' ) );
        $state = (string) request( 'state' );
        $note = trim( (string) request( 'note' ) );
        $row = mysqli_fetch_assoc( appstv_case_db( "SELECT st.*, ps.serial_number FROM product_sn_stock st JOIN product_sn ps ON ps.product_sn_id = st.product_sn_id
                                                    WHERE st.product_sn_id = '" . escape_string( $product_sn_id ) . "' FOR UPDATE" ) );
        if( $row === null || !in_array( $row[ 'state' ], array( 'returned', 'repaired_stock' ), true ) ){ throw new AppstvServiceRuleException( "That TV is not a returned TV." ); }
        if( !in_array( $state, array( 'returned', 'repaired_stock' ), true ) ){ throw new AppstvServiceRuleException( "Choose Repaired or Faulty." ); }
        if( $state === $row[ 'state' ] ){ return array( "Nothing changed.", array( 'product_sn_id' => $product_sn_id ) ); }
        if( mb_strlen( $note ) > 2000 ){ throw new AppstvServiceRuleException( "The note is too long (max 2000)." ); }
        if( $state === 'repaired_stock' && $note === '' ){ throw new AppstvServiceRuleException( "Write what was repaired." ); }
        appstv_case_stock_set( $product_sn_id, $state, $row[ 'case_id' ] === null ? null : (int) $row[ 'case_id' ], $note );
        $text = "TV {$row['serial_number']} " . ( $state === 'repaired_stock' ? "marked as repaired, ready to give out" : "marked as faulty again" );
        return array( $text . ".", array( 'product_sn_id' => $product_sn_id ) );
    } );
}
?>
