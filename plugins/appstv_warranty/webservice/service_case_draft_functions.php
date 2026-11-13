<?php

/*
 * Service case OPENING wizard (v1.0.21) - drafts and opening the case
 *
 * Drafts are shared: everyone who handles cases sees them, continues them or deletes them. Each save carries the
 * revision it was based on; a save based on an older revision (someone else saved in between) is refused and the
 * newer draft is sent back. Every change is written to service_case_history (who, when, field, old -> new); changes
 * of the same field by the same person within APPSTV_CASE_HISTORY_MERGE_MIN minutes are merged into one line, so
 * autosaving while typing does not flood the history.
 *
 * Opening: one case (single), or with "one case per TV" a main case + one TV case per TV (SC-12-01 ...). Serials
 * go to the TV cases in order; a TV case's warranty starts with the main case's answer and is replaced by the real
 * warranty of its serial when the serial is in the system.
 *
 * Helpers used from service_case_functions.php and service_case_wizard_functions.php (loaded with this file).
 */

define( 'APPSTV_CASE_CHANNELS', array( 'call', 'whatsapp', 'email', 'sms' ) );
define( 'APPSTV_CASE_WARRANTY', array( 'under', 'out', 'mixed', 'unknown' ) );
define( 'APPSTV_CASE_PROBLEMS', array( 'no_power', 'no_picture', 'no_sound', 'remote', 'wifi_apps', 'broken_screen',
                                       'physical_damage', 'liquid_damage', 'other' ) );
define( 'APPSTV_CASE_MAX_TVS', 500 );                     // Most TVs on one case
define( 'APPSTV_CASE_DRAFT_MAX_BYTES', 200000 );          // Largest draft JSON
define( 'APPSTV_CASE_HISTORY_MERGE_MIN', 10 );            // Same person + same field within this many minutes = one history line


// ── Draft data ──────────────────────────────────────────────────────────────────────────────────────────────

/**
 * @description Text of a key of an array, trimmed and cut to a length
 * @param {array} $in - Source
 * @param {string} $key - Key
 * @param {int} $max - Longest length kept
 * @returns {string}
 */
function appstv_case_in_text( $in, $key, $max ){
    $value = ( is_array( $in ) && isset( $in[ $key ] ) && is_scalar( $in[ $key ] ) ) ? trim( (string) $in[ $key ] ) : '';
    return mb_substr( $value, 0, $max );
}

/**
 * @description Keeps the items of a list that are allowed, once each
 * @param {mixed} $list - Received list
 * @param {array} $allowed - Allowed values (null = any text up to 50 characters)
 * @returns {array}
 */
function appstv_case_in_list( $list, $allowed ){
    $out = array();
    if( !is_array( $list ) ){ return $out; }
    foreach( $list as $item ){
        if( !is_scalar( $item ) ){ continue; }
        $item = trim( (string) $item );
        if( $item === '' || strlen( $item ) > 50 ){ continue; }
        if( $allowed !== null && !in_array( $item, $allowed, true ) ){ continue; }
        if( !in_array( $item, $out, true ) ){ $out[] = $item; }
    }
    return $out;
}

/**
 * @description Normalises the wizard answers (only known fields, right types and lengths). Nothing is required here:
 *              a draft may be incomplete; appstv_case_open_errors() checks what opening needs.
 * @param {mixed} $in - Decoded JSON from the wizard
 * @returns {array} Clean answers
 */
function appstv_case_clean_answers( $in ){
    if( !is_array( $in ) ){ $in = array(); }
    $customer = ( isset( $in[ 'customer' ] ) && is_array( $in[ 'customer' ] ) ) ? $in[ 'customer' ] : null;
    $complainant = ( isset( $in[ 'complainant' ] ) && is_array( $in[ 'complainant' ] ) ) ? $in[ 'complainant' ] : array();
    $serials = array();
    if( isset( $in[ 'serials' ] ) && is_array( $in[ 'serials' ] ) ){
        foreach( $in[ 'serials' ] as $s ){
            if( !is_array( $s ) ){ continue; }
            $serial = strtoupper( preg_replace( '/[^A-Za-z0-9]/', '', appstv_case_in_text( $s, 'serial_number', 50 ) ) );
            if( $serial === '' ){ continue; }
            $serials[] = array( 'product_sn_id' => appstv_case_in_text( $s, 'product_sn_id', 50 ), 'serial_number' => $serial,
                                'label' => appstv_case_in_text( $s, 'label', 300 ) );
            if( count( $serials ) >= APPSTV_CASE_MAX_TVS ){ break; }
        }
    }
    $tv_count = isset( $in[ 'tv_count' ] ) ? (int) $in[ 'tv_count' ] : 1;
    return array(
        'customer'        => ( $customer !== null && appstv_case_in_text( $customer, 'customer_id', 50 ) !== '' )
                             ? array( 'customer_id' => appstv_case_in_text( $customer, 'customer_id', 50 ),
                                      'name' => appstv_case_in_text( $customer, 'name', 200 ), 'sub' => appstv_case_in_text( $customer, 'sub', 300 ) )
                             : null,
        'complainant'     => array( 'contact_id' => isset( $complainant[ 'contact_id' ] ) ? max( 0, (int) $complainant[ 'contact_id' ] ) : 0,
                                    'name' => appstv_case_in_text( $complainant, 'name', 200 ), 'phone' => appstv_case_in_text( $complainant, 'phone', 50 ),
                                    'email' => appstv_case_in_text( $complainant, 'email', 200 ), 'position' => appstv_case_in_text( $complainant, 'position', 100 ) ),
        'complained_date' => appstv_case_in_text( $in, 'complained_date', 10 ),
        'complained_time' => appstv_case_in_text( $in, 'complained_time', 5 ),
        'channels'        => appstv_case_in_list( isset( $in[ 'channels' ] ) ? $in[ 'channels' ] : null, APPSTV_CASE_CHANNELS ),
        'tv_count'        => max( 1, min( APPSTV_CASE_MAX_TVS, $tv_count ) ),
        'split'           => !empty( $in[ 'split' ] ),
        'models'          => appstv_case_in_list( isset( $in[ 'models' ] ) ? $in[ 'models' ] : null, null ),
        'warranty'        => in_array( appstv_case_in_text( $in, 'warranty', 10 ), APPSTV_CASE_WARRANTY, true ) ? appstv_case_in_text( $in, 'warranty', 10 ) : '',
        'problem_types'   => appstv_case_in_list( isset( $in[ 'problem_types' ] ) ? $in[ 'problem_types' ] : null, APPSTV_CASE_PROBLEMS ),
        'problem'         => appstv_case_in_text( $in, 'problem', 5000 ),
        'serials'         => $serials,
        'staff'           => appstv_case_in_list( isset( $in[ 'staff' ] ) ? $in[ 'staff' ] : null, null ),
        'visit_from'      => appstv_case_in_text( $in, 'visit_from', 10 ),
        'visit_to'        => appstv_case_in_text( $in, 'visit_to', 10 )
    );
}

/**
 * @description What is still missing or wrong before the case can be opened
 * @param {array} $a - Clean answers (appstv_case_clean_answers)
 * @returns {array} [ { step, message } ] - empty when the case can be opened
 */
function appstv_case_open_errors( $a ){
    $errors = array();
    $add = function( $step, $message ) use ( &$errors ){ $errors[] = array( 'step' => $step, 'message' => $message ); };

    // Step 1
    if( $a[ 'customer' ] === null ){
        $add( 1, "Choose the customer (or add a new one)." );
    } else if( mysqli_num_rows( appstv_case_db( "SELECT 1 FROM customers WHERE customer_id = '" . escape_string( $a[ 'customer' ][ 'customer_id' ] ) . "' LIMIT 1" ) ) === 0 ){
        $add( 1, "The chosen customer no longer exists. Choose it again." );
    }
    if( $a[ 'complainant' ][ 'name' ] === '' ){ $add( 1, "Enter the name of the person who complained." ); }
    if( $a[ 'complainant' ][ 'phone' ] !== '' && !preg_match( '/^[0-9+\-() ]{6,20}$/', $a[ 'complainant' ][ 'phone' ] ) ){ $add( 1, "The complainant's phone number is not valid." ); }
    if( $a[ 'complainant' ][ 'email' ] !== '' && !filter_var( $a[ 'complainant' ][ 'email' ], FILTER_VALIDATE_EMAIL ) ){ $add( 1, "The complainant's e-mail address is not valid." ); }
    if( !appstv_case_valid_date( $a[ 'complained_date' ] ) ){ $add( 1, "Enter the date of the complaint." ); }
    else if( $a[ 'complained_date' ] > appstv_case_today() ){ $add( 1, "The date of the complaint cannot be in the future." ); }
    if( !preg_match( '/^([01]\d|2[0-3]):[0-5]\d$/', $a[ 'complained_time' ] ) ){ $add( 1, "Enter the time of the complaint (HH:MM)." ); }
    if( count( $a[ 'channels' ] ) === 0 ){ $add( 1, "Tick how they complained (call, WhatsApp, e-mail, text message)." ); }

    // Step 2
    if( count( $a[ 'models' ] ) === 0 ){ $add( 2, "Tick the TV model(s), or Not sure." ); }
    else {
        $ids = array_diff( $a[ 'models' ], array( 'unsure' ) );
        if( count( $ids ) > 0 ){
            $list = "'" . implode( "','", array_map( 'escape_string', $ids ) ) . "'";
            $known = (int) mysqli_fetch_row( appstv_case_db( "SELECT COUNT(*) FROM products WHERE has_sn = 1 AND product_id IN ( $list )" ) )[ 0 ];
            if( $known !== count( $ids ) ){ $add( 2, "A ticked TV model no longer exists. Tick the models again." ); }
        }
    }
    if( $a[ 'warranty' ] === '' ){ $add( 2, "Choose the warranty answer." ); }
    if( $a[ 'warranty' ] === 'mixed' && $a[ 'tv_count' ] < 2 ){ $add( 2, "\"With or without warranty\" needs more than one TV." ); }

    // Step 3
    if( $a[ 'problem' ] === '' ){ $add( 3, "Describe the problem in their own words." ); }

    // Serials
    if( $a[ 'split' ] && $a[ 'tv_count' ] > 1 && count( $a[ 'serials' ] ) > $a[ 'tv_count' ] ){
        $add( 4, "There are more serial numbers (" . count( $a[ 'serials' ] ) . ") than TVs (" . $a[ 'tv_count' ] . "). Raise the number of TVs or remove serials." );
    }

    // Last step
    if( count( $a[ 'staff' ] ) === 0 ){ $add( 6, "Assign the case to at least one staff member." ); }
    foreach( $a[ 'staff' ] as $user_id ){
        if( !appstv_case_is_staff( $user_id ) ){ $add( 6, "A chosen staff member is not a visit technician (any more). Choose the staff again." ); break; }
    }
    if( !appstv_case_valid_date( $a[ 'visit_from' ] ) || !appstv_case_valid_date( $a[ 'visit_to' ] ) ){ $add( 6, "Enter the visit window (from and to dates)." ); }
    else if( $a[ 'visit_to' ] < $a[ 'visit_from' ] ){ $add( 6, "The visit window ends before it starts." ); }
    return $errors;
}

/**
 * @description Name shown for a draft in the list
 * @param {array} $a - Clean answers
 * @returns {string}
 */
function appstv_case_draft_label( $a ){
    if( $a[ 'customer' ] !== null && $a[ 'customer' ][ 'name' ] !== '' ){ return $a[ 'customer' ][ 'name' ]; }
    return '(customer not chosen yet)';
}


// ── History ─────────────────────────────────────────────────────────────────────────────────────────────────

/**
 * @description Writes the changed fields of a draft to its history. A change of the same field by the same person
 *              within APPSTV_CASE_HISTORY_MERGE_MIN minutes, with nobody else's change in between, updates that line
 *              (the first "old" value is kept) instead of adding a new one.
 * @param {int} $draft_id - service_case_drafts.id
 * @param {array} $before - Answers before
 * @param {array} $after - Answers after
 * @returns {int} Number of fields that changed
 */
function appstv_case_draft_history( $draft_id, $before, $after ){
    $draft_id = (int) $draft_id;
    $user = appstv_case_user_id();
    $e_user = escape_string( $user );
    $limit = ( new DateTime( 'now' ) )->modify( '-' . APPSTV_CASE_HISTORY_MERGE_MIN . ' minutes' )->format( 'c' );
    $last = mysqli_fetch_assoc( appstv_case_db( "SELECT id, user_id FROM service_case_history WHERE draft_id = $draft_id ORDER BY id DESC LIMIT 1" ) );
    $changed = 0;
    foreach( $after as $field => $new_value ){
        $old_value = array_key_exists( $field, $before ) ? $before[ $field ] : null;
        $old_cmp = is_array( $old_value ) ? json_encode( appstv_case_sorted( $old_value ) ) : (string) $old_value;
        $new_cmp = is_array( $new_value ) ? json_encode( appstv_case_sorted( $new_value ) ) : (string) $new_value;
        if( $old_cmp === $new_cmp ){ continue; }
        $changed++;
        $e_field = escape_string( $field );
        // The latest line of this field, when it is mine, recent, and nobody else changed the draft since
        $row = mysqli_fetch_assoc( appstv_case_db( "SELECT h.id, h.user_id, h.created_on FROM service_case_history h
                                                    WHERE h.draft_id = $draft_id AND h.action = 'changed' AND h.field = '$e_field'
                                                    ORDER BY h.id DESC LIMIT 1" ) );
        $mergeable = ( $row !== null && $row[ 'user_id' ] === $user && strcmp( $row[ 'created_on' ], $limit ) >= 0
                       && $last !== null && $last[ 'user_id' ] === $user
                       && mysqli_num_rows( appstv_case_db( "SELECT 1 FROM service_case_history WHERE draft_id = $draft_id AND id > " . (int) $row[ 'id' ] . " AND user_id <> '$e_user' LIMIT 1" ) ) === 0 );
        if( $mergeable ){
            $value = is_array( $new_value ) ? json_encode( $new_value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) : (string) $new_value;
            appstv_case_db( "UPDATE service_case_history SET new_value = '" . escape_string( $value ) . "', created_on = '" . create_iso_8601_datetime() . "'
                             WHERE id = " . (int) $row[ 'id' ] );
        } else {
            appstv_case_history( null, $draft_id, 'changed', $field, $old_value, $new_value );
        }
    }
    return $changed;
}

/**
 * @description History lines with the names of who made them, newest first
 * @param {string} $where - SQL condition on service_case_history (alias h)
 * @param {int} $limit - Most lines returned
 * @returns {array} [ { action, field, old, new, detail, by, on, case_id } ]
 */
function appstv_case_history_list( $where, $limit = 500 ){
    $out = array();
    foreach( appstv_case_rows( "SELECT h.*, u.fname, u.lname FROM service_case_history h
                                LEFT JOIN users u ON CONVERT( u.user_id USING utf8mb4 ) COLLATE utf8mb4_unicode_ci = h.user_id
                                WHERE $where ORDER BY h.id DESC LIMIT " . (int) $limit ) as $row ){
        $out[] = array( 'action' => $row[ 'action' ], 'field' => $row[ 'field' ], 'old' => $row[ 'old_value' ], 'new' => $row[ 'new_value' ],
                        'detail' => $row[ 'detail' ], 'case_id' => $row[ 'case_id' ] === null ? null : (int) $row[ 'case_id' ],
                        'by' => trim( $row[ 'fname' ] . ' ' . $row[ 'lname' ] ) ?: ( $row[ 'user_id' ] === '' ? 'System' : $row[ 'user_id' ] ),
                        'on' => $row[ 'created_on' ] );
    }
    return $out;
}


// ── Draft actions ───────────────────────────────────────────────────────────────────────────────────────────

/**
 * @description Reads a draft row (locked when asked) that is still a draft
 * @param {int} $draft_id - service_case_drafts.id
 * @param {bool} $lock - Lock it inside a transaction
 * @returns {array} The row
 * @throws AppstvServiceRuleException when it does not exist, was deleted or was opened
 */
function appstv_case_draft_read( $draft_id, $lock = false ){
    $row = mysqli_fetch_assoc( appstv_case_db( "SELECT * FROM service_case_drafts WHERE id = " . (int) $draft_id . ( $lock ? " FOR UPDATE" : "" ) ) );
    if( $row === null || $row[ 'status' ] === 'deleted' ){ throw new AppstvServiceRuleException( "This draft was deleted." ); }
    if( $row[ 'status' ] === 'opened' ){ throw new AppstvServiceRuleException( "This draft was already opened as case SC-" . (int) $row[ 'case_id' ] . "." ); }
    return $row;
}

/**
 * @description A draft as sent to the wizard
 * @param {array} $row - service_case_drafts row
 * @returns {array} draft_id, revision, step, answers, created / updated by and on
 */
function appstv_case_draft_item( $row ){
    return array( 'draft_id' => (int) $row[ 'id' ], 'revision' => (int) $row[ 'revision' ], 'step' => (int) $row[ 'step' ],
                  'answers' => appstv_case_clean_answers( json_decode( $row[ 'data' ], true ) ),
                  'created_by' => appstv_case_user_name( $row[ 'created_by' ] ), 'created_on' => $row[ 'created_on' ],
                  'updated_by' => appstv_case_user_name( $row[ 'updated_by' ] ), 'updated_on' => $row[ 'updated_on' ] );
}


/**
 * Starts (draft_id 0) or saves a draft. revision = the revision the wizard was based on; when someone else saved in
 * between, nothing is saved and the newer draft is returned (conflict = true).
 */
function appstv_case_draft_save(){
    checkAuthorizationForFunction( __FUNCTION__ );
    appstv_case_transaction( __FUNCTION__, function(){
        $draft_id = (int) request( 'draft_id' );
        $revision = (int) request( 'revision' );
        $step = max( 1, min( 6, (int) request( 'step' ) ) );
        $raw = (string) request( 'answers' );
        if( strlen( $raw ) > APPSTV_CASE_DRAFT_MAX_BYTES ){ throw new AppstvServiceRuleException( "The draft is too large to save." ); }
        $answers = appstv_case_clean_answers( json_decode( $raw, true ) );
        $json = json_encode( $answers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
        $e_json = escape_string( $json );
        $e_label = escape_string( mb_substr( appstv_case_draft_label( $answers ), 0, 200 ) );
        $e_user = escape_string( appstv_case_user_id() );
        $now = create_iso_8601_datetime();

        if( $draft_id === 0 ){
            appstv_case_db( "INSERT INTO service_case_drafts ( data, customer_label, step, status, revision, created_by, created_on, updated_by, updated_on )
                             VALUES ( '$e_json', '$e_label', $step, 'draft', 1, '$e_user', '$now', '$e_user', '$now' )" );
            global $con;
            $draft_id = (int) mysqli_insert_id( $con );
            appstv_case_history( null, $draft_id, 'draft_created', '', null, null, "Draft started" );
            return array( "Draft saved.", array( 'conflict' => false, 'draft_id' => $draft_id, 'revision' => 1, 'saved_on' => $now ) );
        }

        $row = appstv_case_draft_read( $draft_id, true );
        if( (int) $row[ 'revision' ] !== $revision ){
            return array( "Someone else changed this draft.", array( 'conflict' => true, 'draft' => appstv_case_draft_item( $row ) ) );
        }
        $before = appstv_case_clean_answers( json_decode( $row[ 'data' ], true ) );
        $changed = appstv_case_draft_history( $draft_id, $before, $answers );
        if( $changed === 0 && (int) $row[ 'step' ] === $step ){
            return array( "No change.", array( 'conflict' => false, 'draft_id' => $draft_id, 'revision' => $revision, 'saved_on' => $row[ 'updated_on' ] ) );
        }
        $next = $revision + 1;
        appstv_case_db( "UPDATE service_case_drafts SET data = '$e_json', customer_label = '$e_label', step = $step, revision = $next,
                                updated_by = '$e_user', updated_on = '$now' WHERE id = $draft_id" );
        return array( "Draft saved.", array( 'conflict' => false, 'draft_id' => $draft_id, 'revision' => $next, 'saved_on' => $now ) );
    } );
}


/**
 * Drafts not opened yet, last changed first
 */
function appstv_case_draft_list(){
    checkAuthorizationForFunction( __FUNCTION__ );
    appstv_case_answer( __FUNCTION__, function(){
        $drafts = array();
        foreach( appstv_case_rows( "SELECT d.id, d.customer_label, d.step, d.revision, d.created_by, d.created_on, d.updated_by, d.updated_on, d.data,
                                           ( SELECT COUNT(*) FROM service_case_media m WHERE m.draft_id = d.id AND m.case_id IS NULL AND m.deleted_on = '' ) AS media
                                    FROM service_case_drafts d WHERE d.status = 'draft' ORDER BY d.updated_on DESC LIMIT 200" ) as $row ){
            $a = appstv_case_clean_answers( json_decode( $row[ 'data' ], true ) );
            $drafts[] = array( 'draft_id' => (int) $row[ 'id' ], 'label' => $row[ 'customer_label' ], 'step' => (int) $row[ 'step' ],
                               'problem' => mb_substr( $a[ 'problem' ], 0, 160 ), 'tv_count' => $a[ 'tv_count' ], 'media' => (int) $row[ 'media' ],
                               'created_by' => appstv_case_user_name( $row[ 'created_by' ] ), 'created_on' => $row[ 'created_on' ],
                               'updated_by' => appstv_case_user_name( $row[ 'updated_by' ] ), 'updated_on' => $row[ 'updated_on' ] );
        }
        return array( count( $drafts ) . " draft(s).", array( 'drafts' => $drafts ) );
    } );
}


/**
 * One draft with its history
 */
function appstv_case_draft_get(){
    checkAuthorizationForFunction( __FUNCTION__ );
    appstv_case_answer( __FUNCTION__, function(){
        $row = appstv_case_draft_read( request( 'draft_id' ) );
        $item = appstv_case_draft_item( $row );
        $item[ 'history' ] = appstv_case_history_list( "h.draft_id = " . (int) $row[ 'id' ] . " AND h.case_id IS NULL" );
        $item[ 'media' ] = appstv_media_list( "m.draft_id = " . (int) $row[ 'id' ] . " AND m.case_id IS NULL" );
        return array( "Draft.", array( 'draft' => $item ) );
    } );
}


/**
 * Deletes a draft (kept in the database as deleted, with who and when)
 */
function appstv_case_draft_delete(){
    checkAuthorizationForFunction( __FUNCTION__ );
    appstv_case_transaction( __FUNCTION__, function(){
        $row = appstv_case_draft_read( request( 'draft_id' ), true );
        $id = (int) $row[ 'id' ];
        $e_user = escape_string( appstv_case_user_id() );
        $now = create_iso_8601_datetime();
        appstv_case_db( "UPDATE service_case_drafts SET status = 'deleted', deleted_by = '$e_user', deleted_on = '$now', revision = revision + 1 WHERE id = $id" );
        appstv_case_history( null, $id, 'draft_deleted', '', null, null, "Draft deleted" );
        // Its photos and videos go with it (files deleted, rows kept as removed)
        foreach( appstv_case_rows( "SELECT * FROM service_case_media WHERE draft_id = $id AND case_id IS NULL AND deleted_on = ''" ) as $media ){
            appstv_case_db( "UPDATE service_case_media SET deleted_by = '$e_user', deleted_on = '$now' WHERE id = " . (int) $media[ 'id' ] );
            appstv_media_unlink( $media );
        }
        return array( "Draft deleted.", array( 'draft_id' => $id ) );
    } );
}


// ── Opening ─────────────────────────────────────────────────────────────────────────────────────────────────

/**
 * @description Warranty answer of a TV from its real warranty (when the serial is in the system)
 * @param {string} $product_sn_id - product_sn.product_sn_id ('' = not in the system)
 * @returns {string|null} 'under' / 'out', null when it cannot be told
 */
function appstv_case_serial_warranty( $product_sn_id ){
    if( $product_sn_id === '' ){ return null; }
    $chain = appstv_case_tv_chain( $product_sn_id );
    if( $chain === null ){ return null; }
    if( $chain[ 'warranty_status' ] === 'Active' || $chain[ 'warranty_status' ] === 'Active (Extended)' ){ return 'under'; }
    if( $chain[ 'warranty_status' ] === 'Expired' ){ return 'out'; }
    return null;
}

/**
 * @description Inserts one service_cases row of the wizard (single, main or TV case) with its staff, channels and models
 * @param {array} $a - Clean, checked answers
 * @param {array} $extra - kind, parent_case_id, tv_index, tv_count, warranty, complainant_id, draft_id
 * @returns {int} The new case id
 */
function appstv_case_insert_case( $a, $extra ){
    global $con;
    $now = create_iso_8601_datetime();
    $user = escape_string( appstv_case_user_id() );
    $c = $a[ 'complainant' ];
    $complained_on = $a[ 'complained_date' ] . 'T' . $a[ 'complained_time' ] . ':00+08:00';
    $v = array(
        'parent_case_id'   => $extra[ 'parent_case_id' ] === null ? "NULL" : (int) $extra[ 'parent_case_id' ],
        'case_kind'        => "'" . escape_string( $extra[ 'kind' ] ) . "'",
        'tv_index'         => (int) $extra[ 'tv_index' ],
        'customer_id'      => "'" . escape_string( $a[ 'customer' ][ 'customer_id' ] ) . "'",
        'complainant_id'   => $extra[ 'complainant_id' ] ? (int) $extra[ 'complainant_id' ] : "NULL",
        'contact_name'     => "'" . escape_string( $c[ 'name' ] ) . "'",
        'contact_phone'    => "'" . escape_string( $c[ 'phone' ] ) . "'",
        'contact_email'    => "'" . escape_string( $c[ 'email' ] ) . "'",
        'contact_position' => "'" . escape_string( $c[ 'position' ] ) . "'",
        'complained_on'    => "'" . escape_string( $complained_on ) . "'",
        'tv_count'         => (int) $extra[ 'tv_count' ],
        'warranty_status'  => "'" . escape_string( $extra[ 'warranty' ] ) . "'",
        'problem_types'    => "'" . escape_string( implode( ',', $a[ 'problem_types' ] ) ) . "'",
        'problem'          => "'" . escape_string( $a[ 'problem' ] ) . "'",
        'visit_from'       => "'" . escape_string( $a[ 'visit_from' ] ) . "'",
        'visit_to'         => "'" . escape_string( $a[ 'visit_to' ] ) . "'",
        'draft_id'         => $extra[ 'draft_id' ] ? (int) $extra[ 'draft_id' ] : "NULL",
        'status'           => "'open'",
        'assigned_user_id' => "'" . escape_string( $a[ 'staff' ][ 0 ] ) . "'",
        'visit_date'       => "''", 'visit_time' => "''", 'outcome' => "''",
        'created_by'       => "'$user'", 'created_on' => "'$now'", 'updated_on' => "'$now'", 'closed_by' => "''", 'closed_on' => "''"
    );
    appstv_case_db( "INSERT INTO service_cases ( " . implode( ', ', array_keys( $v ) ) . " ) VALUES ( " . implode( ', ', $v ) . " )" );
    $case_id = (int) mysqli_insert_id( $con );

    foreach( $a[ 'staff' ] as $user_id ){
        appstv_case_db( "INSERT INTO service_case_staff ( case_id, user_id, added_by, added_on ) VALUES ( $case_id, '" . escape_string( $user_id ) . "', '$user', '$now' )" );
    }
    foreach( $a[ 'channels' ] as $channel ){
        appstv_case_db( "INSERT INTO service_case_channels ( case_id, channel ) VALUES ( $case_id, '" . escape_string( $channel ) . "' )" );
    }
    foreach( $a[ 'models' ] as $product_id ){
        appstv_case_db( "INSERT INTO service_case_models ( case_id, product_id ) VALUES ( $case_id, '" . escape_string( $product_id ) . "' )" );
    }
    return $case_id;
}

/**
 * @description Opens the case(s) from checked answers. Called inside a transaction.
 * @param {array} $a - Clean answers without errors
 * @param {int} $draft_id - Draft it comes from (0 = none)
 * @returns {array} case_id (main or single), tv_case_ids
 */
function appstv_case_open_answers( $a, $draft_id ){
    $split = ( $a[ 'split' ] && $a[ 'tv_count' ] > 1 );
    $c = $a[ 'complainant' ];
    $contact_id = appstv_case_save_contact( $a[ 'customer' ][ 'customer_id' ], $c[ 'name' ], $c[ 'phone' ], $c[ 'email' ], $c[ 'position' ] );

    $case_id = appstv_case_insert_case( $a, array( 'kind' => $split ? 'main' : 'single', 'parent_case_id' => null, 'tv_index' => 0,
                                                   'tv_count' => $a[ 'tv_count' ], 'warranty' => $a[ 'warranty' ],
                                                   'complainant_id' => $contact_id, 'draft_id' => $draft_id ) );
    $staff_names = implode( ', ', array_map( 'appstv_case_user_name', $a[ 'staff' ] ) );
    $summary = "Case opened: " . $a[ 'tv_count' ] . " TV(s)" . ( $split ? ", one case per TV" : "" ) . "; assigned to $staff_names; visit "
             . $a[ 'visit_from' ] . ( $a[ 'visit_to' ] !== $a[ 'visit_from' ] ? " to " . $a[ 'visit_to' ] : "" );
    appstv_case_history( $case_id, $draft_id ?: null, 'opened', '', null, null, $summary );

    $tv_case_ids = array();
    if( !$split ){
        $serials = array();
        foreach( $a[ 'serials' ] as $s ){ $serials[] = appstv_case_insert_tv( $case_id, $s[ 'product_sn_id' ], $s[ 'serial_number' ] ); }
        appstv_case_log( $case_id, 'created', $summary . ( count( $serials ) ? "; TV " . implode( ', ', $serials ) : "" ) );
    } else {
        // Each TV case starts with the main answer ("mixed" cannot apply to one TV: not known until checked)
        $start = ( $a[ 'warranty' ] === 'mixed' ) ? 'unknown' : $a[ 'warranty' ];
        for( $i = 1; $i <= $a[ 'tv_count' ]; $i++ ){
            $serial = isset( $a[ 'serials' ][ $i - 1 ] ) ? $a[ 'serials' ][ $i - 1 ] : null;
            $tv_id = appstv_case_insert_case( $a, array( 'kind' => 'tv', 'parent_case_id' => $case_id, 'tv_index' => $i, 'tv_count' => 1,
                                                         'warranty' => $start, 'complainant_id' => $contact_id, 'draft_id' => $draft_id ) );
            $tv_case_ids[] = $tv_id;
            $label = sprintf( 'SC-%d-%02d', $case_id, $i );
            appstv_case_history( $tv_id, null, 'opened', '', null, null, "TV case $label opened with main case SC-$case_id" );
            $text = "TV case $label opened";
            if( $serial !== null ){
                $sn = appstv_case_insert_tv( $tv_id, $serial[ 'product_sn_id' ], $serial[ 'serial_number' ] );
                $text .= " for TV $sn";
                $real = appstv_case_serial_warranty( $serial[ 'product_sn_id' ] );
                if( $real !== null && $real !== $start ){
                    appstv_case_db( "UPDATE service_cases SET warranty_status = '$real' WHERE id = $tv_id" );
                    appstv_case_history( $tv_id, null, 'changed', 'warranty_status', $start, $real, "From the warranty of serial $sn" );
                }
            }
            appstv_case_log( $tv_id, 'created', $text );
        }
        appstv_case_log( $case_id, 'created', $summary . "; TV cases " . sprintf( 'SC-%d-01', $case_id ) . ( $a[ 'tv_count' ] > 1 ? " to " . sprintf( 'SC-%d-%02d', $case_id, $a[ 'tv_count' ] ) : "" ) );
    }

    // Photos and videos of the draft move to the main case
    if( $draft_id ){
        appstv_case_db( "UPDATE service_case_media SET case_id = $case_id WHERE draft_id = " . (int) $draft_id . " AND case_id IS NULL AND deleted_on = ''" );
    }

    // E-mail to each assigned staff member, sent by the every-minute job (1.0.28)
    appstv_case_mail_queue( $case_id, $a[ 'staff' ] );
    return array( 'case_id' => $case_id, 'tv_case_ids' => $tv_case_ids );
}


/**
 * Opens the case from the wizard: answers (JSON), draft_id + revision when it comes from a draft. When something is
 * missing nothing is saved and the problems are returned per step (errors).
 */
function appstv_case_create(){
    checkAuthorizationForFunction( __FUNCTION__ );
    appstv_case_transaction( __FUNCTION__, function(){
        $raw = (string) request( 'answers' );
        if( strlen( $raw ) > APPSTV_CASE_DRAFT_MAX_BYTES ){ throw new AppstvServiceRuleException( "The case is too large to save." ); }
        $answers = appstv_case_clean_answers( json_decode( $raw, true ) );
        $draft_id = (int) request( 'draft_id' );
        $draft = null;
        if( $draft_id > 0 ){
            $draft = appstv_case_draft_read( $draft_id, true );
            if( (int) $draft[ 'revision' ] !== (int) request( 'revision' ) ){
                return array( "Someone else changed this draft.", array( 'opened' => false, 'conflict' => true, 'draft' => appstv_case_draft_item( $draft ) ) );
            }
        }
        $errors = appstv_case_open_errors( $answers );
        if( count( $errors ) > 0 ){
            return array( "Some answers are missing.", array( 'opened' => false, 'conflict' => false, 'errors' => $errors ) );
        }
        if( $draft !== null ){
            appstv_case_draft_history( $draft_id, appstv_case_clean_answers( json_decode( $draft[ 'data' ], true ) ), $answers );
        }
        $result = appstv_case_open_answers( $answers, $draft_id );
        if( $draft !== null ){
            $e_json = escape_string( json_encode( $answers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
            $e_user = escape_string( appstv_case_user_id() );
            $now = create_iso_8601_datetime();
            appstv_case_db( "UPDATE service_case_drafts SET status = 'opened', case_id = {$result['case_id']}, data = '$e_json', revision = revision + 1,
                                    updated_by = '$e_user', updated_on = '$now' WHERE id = $draft_id" );
        }
        $n = count( $result[ 'tv_case_ids' ] );
        return array( "Case SC-{$result['case_id']} opened" . ( $n ? " with $n TV cases" : "" ) . ".",
                      array( 'opened' => true, 'case_id' => $result[ 'case_id' ], 'tv_case_ids' => $result[ 'tv_case_ids' ] ) );
    } );
}


/**
 * Who changed what and when on a case (and, for a main case, on its TV cases and the draft it came from)
 */
function appstv_case_history_get(){
    checkAuthorizationForFunction( __FUNCTION__ );
    appstv_case_answer( __FUNCTION__, function(){
        $case = appstv_case_read( request( 'case_id' ) );
        $id = (int) $case[ 'id' ];
        $where = "h.case_id = $id";
        if( $case[ 'case_kind' ] === 'main' ){
            $where .= " OR h.case_id IN ( SELECT k.id FROM service_cases k WHERE k.parent_case_id = $id )";
        }
        if( $case[ 'draft_id' ] && $case[ 'case_kind' ] !== 'tv' ){
            $where .= " OR ( h.draft_id = " . (int) $case[ 'draft_id' ] . " AND h.case_id IS NULL )";
        }
        return array( "History of " . appstv_case_label( $case ) . ".", array( 'history' => appstv_case_history_list( $where, 2000 ) ) );
    } );
}
