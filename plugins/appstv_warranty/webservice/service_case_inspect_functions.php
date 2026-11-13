<?php

/*
 * Service cases: inspection of each TV on site (v1.0.32)
 *
 * One row of service_case_inspections per TV of a case. The "Inspect" form on the TVs tab sends one step at a time;
 * the server keeps the answers, works out the next step and the result, so the phone never decides the rules:
 *
 *   warranty   only when the serial gives no warranty: staff choose Under / Out
 *   outside    under warranty: no damage | damage (physical, liquid) + note + photos (outside_damage)
 *   paid       not covered (out of warranty, or damage): does the customer want a paid repair? no = the end
 *              (result declined / not_covered); out-of-warranty TVs and paid repairs carry a service charge note
 *   open_here  can the TV be opened on site? no = reason -> it has to be taken to the office (to_office)
 *   inside     under warranty, no damage outside: liquid inside? liquid = note + photos (inside_liquid) -> paid
 *   repair     repaired (what was done + photo of the TV working: the repair is written into the warranty records)
 *              | spare part (waiting_part) | take to the office (to_office) | other (revisit)
 *
 * Answering a step again clears the steps after it. Once the repair is in the warranty records the inspection is
 * locked; waiting_part / revisit can answer the repair step again on the next visit.
 */

define( 'APPSTV_INSPECT_STEPS', array( 'warranty', 'outside', 'open_here', 'inside', 'repair' ) );   // Order ('paid' follows its cause)
define( 'APPSTV_INSPECT_RESULTS', array(
    'not_covered'  => 'Not covered by warranty',
    'declined'     => 'Out of warranty, no repair',
    'repaired'     => 'Repaired on site',
    'waiting_part' => 'Waiting for a spare part',
    'to_office'    => 'To be taken to the office',
    'revisit'      => 'Needs another visit'
) );
define( 'APPSTV_INSPECT_NO_OPEN', array(
    'no_space'    => 'No space to work',
    'mounted'     => 'TV mounted / hard to reach',
    'not_allowed' => 'Customer does not allow it',
    'other'       => 'Other'
) );
define( 'APPSTV_INSPECT_PURPOSES', array( 'outside_damage', 'inside_liquid', 'working' ) );
define( 'APPSTV_INSPECT_NOTE_MAX', 2000 );


/**
 * @description Number of ready photos of one TV for one inspection step
 * @param {int} $case_tv_id - service_case_tvs.id
 * @param {string} $purpose - outside_damage | inside_liquid | working
 * @returns {int}
 */
function appstv_case_inspect_photos( $case_tv_id, $purpose ){
    $row = mysqli_fetch_row( appstv_case_db( "SELECT COUNT(*) FROM service_case_media WHERE tv_id = " . (int) $case_tv_id . "
                                              AND purpose = '" . escape_string( $purpose ) . "' AND deleted_on = '' AND state = 'ready'" ) );
    return (int) $row[ 0 ];
}

/**
 * @description The step whose answer made the repair "not covered" (it decides where 'paid' sits in the order)
 * @param {array} $r - service_case_inspections row
 * @returns {string} warranty | outside | inside | '' (covered)
 */
function appstv_case_inspect_paid_cause( $r ){
    if( $r[ 'warranty' ] === 'out' ){ return 'warranty'; }
    if( $r[ 'outside' ] === 'damage' ){ return 'outside'; }
    if( $r[ 'inside' ] === 'liquid' ){ return 'inside'; }
    return '';
}

/**
 * @description Works out the next step and the result of an inspection from its answers
 * @param {array} $r - service_case_inspections row
 * @returns {array} [ next step ('' when finished), result ('' when not finished) ]
 */
function appstv_case_inspect_flow( $r ){
    if( $r[ 'warranty' ] === '' ){ return array( 'warranty', '' ); }
    if( $r[ 'warranty' ] === 'under' ){
        if( $r[ 'outside' ] === '' ){ return array( 'outside', '' ); }
    }
    $cause = appstv_case_inspect_paid_cause( $r );
    if( $cause === 'warranty' || $cause === 'outside' ){
        if( $r[ 'paid' ] === '' ){ return array( 'paid', '' ); }
        if( $r[ 'paid' ] === 'no' ){ return array( '', $cause === 'warranty' ? 'declined' : 'not_covered' ); }
    }
    if( $r[ 'open_here' ] === '' ){ return array( 'open_here', '' ); }
    if( $r[ 'open_here' ] === 'no' ){ return array( '', 'to_office' ); }
    if( $r[ 'warranty' ] === 'under' && $r[ 'outside' ] === 'none' ){
        if( $r[ 'inside' ] === '' ){ return array( 'inside', '' ); }
        if( $r[ 'inside' ] === 'liquid' ){
            if( $r[ 'paid' ] === '' ){ return array( 'paid', '' ); }
            if( $r[ 'paid' ] === 'no' ){ return array( '', 'not_covered' ); }
        }
    }
    $results = array( 'repaired' => 'repaired', 'part' => 'waiting_part', 'office' => 'to_office', 'other' => 'revisit' );
    if( !array_key_exists( $r[ 'repair' ], $results ) ){ return array( 'repair', '' ); }
    return array( '', $results[ $r[ 'repair' ] ] );
}

/**
 * @description Clears the answers that come after a step (they may not apply any more)
 * @param {array} $r - service_case_inspections row (changed)
 * @param {string} $step - Step answered
 * @returns {array} The row
 */
function appstv_case_inspect_clear_after( $r, $step ){
    $fields = array(
        'outside'   => array( 'outside' => '', 'outside_kinds' => '', 'outside_note' => null ),
        'open_here' => array( 'open_here' => '', 'no_open_reason' => '', 'no_open_note' => null ),
        'inside'    => array( 'inside' => '', 'inside_note' => null ),
        'repair'    => array( 'repair' => '', 'repair_note' => null, 'part_name' => '' )
    );
    $cause = appstv_case_inspect_paid_cause( $r );
    $from = ( $step === 'paid' ) ? $cause : $step;            // 'paid' sits right after its cause
    $pos = array_search( $from, APPSTV_INSPECT_STEPS, true );
    if( $pos === false ){ return $r; }
    foreach( array_slice( APPSTV_INSPECT_STEPS, $pos + 1 ) as $later ){
        foreach( $fields[ $later ] as $field => $empty ){ $r[ $field ] = $empty; }
    }
    // The paid answer belongs to its cause: answering that cause (or an earlier step) again asks it again
    if( $step !== 'paid' ){
        $cause_pos = array_search( $cause, APPSTV_INSPECT_STEPS, true );
        if( $cause === '' || ( $cause_pos !== false && $cause_pos >= $pos ) ){ $r[ 'paid' ] = ''; }
    }
    return $r;
}

/**
 * @description An inspection as sent to the page
 * @param {array} $r - service_case_inspections row
 * @returns {array}
 */
function appstv_case_inspect_item( $r ){
    list( $next, $result ) = appstv_case_inspect_flow( $r );
    $tv = (int) $r[ 'case_tv_id' ];
    return array(
        'id' => (int) $r[ 'id' ], 'case_tv_id' => $tv, 'warranty' => $r[ 'warranty' ], 'paid' => $r[ 'paid' ],
        'outside' => $r[ 'outside' ], 'outside_kinds' => $r[ 'outside_kinds' ] === '' ? array() : explode( ',', $r[ 'outside_kinds' ] ),
        'outside_note' => (string) $r[ 'outside_note' ], 'open_here' => $r[ 'open_here' ], 'no_open_reason' => $r[ 'no_open_reason' ],
        'no_open_note' => (string) $r[ 'no_open_note' ], 'inside' => $r[ 'inside' ], 'inside_note' => (string) $r[ 'inside_note' ],
        'repair' => $r[ 'repair' ], 'repair_note' => (string) $r[ 'repair_note' ], 'part_name' => $r[ 'part_name' ],
        'next' => $next, 'result' => $result, 'result_text' => $result === '' ? '' : APPSTV_INSPECT_RESULTS[ $result ],
        'locked' => (string) $r[ 'pwrp_id' ] !== '',
        'charge' => $r[ 'charge' ], 'charge_by' => appstv_case_user_name( $r[ 'charge_by' ] ), 'charge_on' => $r[ 'charge_on' ],
        'photos' => array( 'outside_damage' => appstv_case_inspect_photos( $tv, 'outside_damage' ),
                           'inside_liquid' => appstv_case_inspect_photos( $tv, 'inside_liquid' ),
                           'working' => appstv_case_inspect_photos( $tv, 'working' ) ),
        'by' => appstv_case_user_name( $r[ 'updated_by' ] ), 'on' => $r[ 'updated_on' ], 'finished_on' => $r[ 'finished_on' ]
    );
}

/**
 * @description The inspections of a case, by service_case_tvs.id
 * @param {int} $case_id - service_cases.id
 * @returns {object} { case_tv_id: item }
 */
function appstv_case_inspect_list( $case_id ){
    $out = array();
    foreach( appstv_case_rows( "SELECT * FROM service_case_inspections WHERE case_id = " . (int) $case_id ) as $r ){
        $out[ (string) $r[ 'case_tv_id' ] ] = appstv_case_inspect_item( $r );
    }
    return (object) $out;
}

/**
 * @description Warranty of a TV for the inspection, from its serial
 * @param {array|null} $chain - appstv_case_tv_chain() of the TV (null = not in the system)
 * @returns {string} under | out | '' (not known: staff choose)
 */
function appstv_case_inspect_warranty( $chain ){
    if( $chain === null ){ return ''; }
    if( in_array( $chain[ 'warranty_status' ], array( 'Active', 'Active (Extended)' ), true ) ){ return 'under'; }
    if( $chain[ 'warranty_status' ] === 'Expired' ){ return 'out'; }
    return '';
}

/**
 * @description Reads the text of an answer, trimmed and limited
 * @param {array} $in - Answer
 * @param {string} $key - Field
 * @param {int} $max - Longest length
 * @param {string} $label - Name in the error message
 * @returns {string}
 */
function appstv_case_inspect_text( $in, $key, $max, $label ){
    $value = trim( (string) ( isset( $in[ $key ] ) ? $in[ $key ] : '' ) );
    if( mb_strlen( $value ) > $max ){ throw new AppstvServiceRuleException( "$label is too long (max $max)." ); }
    return $value;
}

/**
 * @description Applies one answered step to the row (checked) and returns the history sentence
 * @param {array} $r - service_case_inspections row
 * @param {string} $step - Step answered
 * @param {array} $in - Answer
 * @param {string} $serial - Serial number (for the messages)
 * @returns {array} [ row, sentence ]
 */
function appstv_case_inspect_apply( $r, $step, $in, $serial ){
    $value = (string) ( isset( $in[ 'value' ] ) ? $in[ 'value' ] : '' );
    $max = APPSTV_INSPECT_NOTE_MAX;
    if( $step === 'warranty' ){
        if( !in_array( $value, array( 'under', 'out' ), true ) ){ throw new AppstvServiceRuleException( "Choose Under or Out of warranty." ); }
        $r[ 'warranty' ] = $value;
        return array( $r, $value === 'under' ? 'treated as under warranty' : 'treated as out of warranty' );
    }
    if( $step === 'outside' ){
        if( $value === 'none' ){
            $r[ 'outside' ] = 'none'; $r[ 'outside_kinds' ] = ''; $r[ 'outside_note' ] = null;
            return array( $r, 'no damage visible from outside' );
        }
        if( $value !== 'damage' ){ throw new AppstvServiceRuleException( "Choose No damage, or the damage seen." ); }
        $kinds = isset( $in[ 'kinds' ] ) && is_array( $in[ 'kinds' ] ) ? array_values( array_intersect( array( 'physical', 'liquid' ), $in[ 'kinds' ] ) ) : array();
        if( count( $kinds ) === 0 ){ throw new AppstvServiceRuleException( "Tick Physical damage and / or Liquid damage." ); }
        $note = appstv_case_inspect_text( $in, 'note', $max, "The description" );
        if( $note === '' ){ throw new AppstvServiceRuleException( "Write where the damage is and what it looks like." ); }
        if( appstv_case_inspect_photos( $r[ 'case_tv_id' ], 'outside_damage' ) === 0 ){ throw new AppstvServiceRuleException( "Add at least one photo of the damage." ); }
        $r[ 'outside' ] = 'damage'; $r[ 'outside_kinds' ] = implode( ',', $kinds ); $r[ 'outside_note' ] = $note;
        return array( $r, implode( ' and ', $kinds ) . ' damage visible from outside: ' . $note );
    }
    if( $step === 'paid' ){
        if( appstv_case_inspect_paid_cause( $r ) === '' ){ throw new AppstvServiceRuleException( "This TV is covered by the warranty; no paid repair is asked." ); }
        if( !in_array( $value, array( 'yes', 'no' ), true ) ){ throw new AppstvServiceRuleException( "Choose whether the customer wants a paid repair." ); }
        $r[ 'paid' ] = $value;
        return array( $r, $value === 'yes' ? 'the customer wants a paid repair' : 'the customer does not want a paid repair' );
    }
    if( $step === 'open_here' ){
        if( $value === 'yes' ){
            $r[ 'open_here' ] = 'yes'; $r[ 'no_open_reason' ] = ''; $r[ 'no_open_note' ] = null;
            return array( $r, 'can be opened on site' );
        }
        if( $value !== 'no' ){ throw new AppstvServiceRuleException( "Choose whether the TV can be opened here." ); }
        $reason = (string) ( isset( $in[ 'reason' ] ) ? $in[ 'reason' ] : '' );
        if( !array_key_exists( $reason, APPSTV_INSPECT_NO_OPEN ) ){ throw new AppstvServiceRuleException( "Choose why the TV cannot be opened here." ); }
        $note = appstv_case_inspect_text( $in, 'note', $max, "The reason" );
        if( $reason === 'other' && $note === '' ){ throw new AppstvServiceRuleException( "Write why the TV cannot be opened here." ); }
        $r[ 'open_here' ] = 'no'; $r[ 'no_open_reason' ] = $reason; $r[ 'no_open_note' ] = ( $note === '' ? null : $note );
        return array( $r, 'cannot be opened on site (' . strtolower( APPSTV_INSPECT_NO_OPEN[ $reason ] ) . ( $note !== '' ? ': ' . $note : '' ) . ')' );
    }
    if( $step === 'inside' ){
        if( $value === 'none' ){
            $r[ 'inside' ] = 'none'; $r[ 'inside_note' ] = null;
            return array( $r, 'no liquid damage inside' );
        }
        if( $value !== 'liquid' ){ throw new AppstvServiceRuleException( "Choose whether there is liquid damage inside." ); }
        $note = appstv_case_inspect_text( $in, 'note', $max, "The description" );
        if( $note === '' ){ throw new AppstvServiceRuleException( "Write what the liquid damage inside looks like." ); }
        if( appstv_case_inspect_photos( $r[ 'case_tv_id' ], 'inside_liquid' ) === 0 ){ throw new AppstvServiceRuleException( "Add at least one photo of the liquid damage." ); }
        $r[ 'inside' ] = 'liquid'; $r[ 'inside_note' ] = $note;
        return array( $r, 'liquid damage inside: ' . $note );
    }
    if( $step === 'repair' ){
        $note = appstv_case_inspect_text( $in, 'note', $max, "The note" );
        if( $value === 'repaired' ){
            if( $note === '' ){ throw new AppstvServiceRuleException( "Write what was repaired." ); }
            if( appstv_case_inspect_photos( $r[ 'case_tv_id' ], 'working' ) === 0 ){ throw new AppstvServiceRuleException( "Add a photo of the TV working after the repair." ); }
            $r[ 'repair' ] = 'repaired'; $r[ 'repair_note' ] = $note; $r[ 'part_name' ] = '';
            return array( $r, 'repaired on site: ' . $note );
        }
        if( $value === 'part' ){
            $part = appstv_case_inspect_text( $in, 'part_name', 200, "The spare part name" );
            if( $part === '' ){ throw new AppstvServiceRuleException( "Write which spare part is needed." ); }
            $r[ 'repair' ] = 'part'; $r[ 'repair_note' ] = ( $note === '' ? null : $note ); $r[ 'part_name' ] = $part;
            return array( $r, 'needs a spare part: ' . $part . ( $note !== '' ? ' - ' . $note : '' ) );
        }
        if( $value === 'office' || $value === 'other' ){
            if( $note === '' ){ throw new AppstvServiceRuleException( $value === 'office' ? "Write why the TV has to be taken to the office." : "Write why it was not repaired on site." ); }
            $r[ 'repair' ] = $value; $r[ 'repair_note' ] = $note; $r[ 'part_name' ] = '';
            return array( $r, ( $value === 'office' ? 'has to be taken to the office: ' : 'not repaired on site: ' ) . $note );
        }
        throw new AppstvServiceRuleException( "Choose what happened with the repair." );
    }
    throw new AppstvServiceRuleException( "Unknown inspection step." );
}

/**
 * @description Writes the on-site repair into the warranty records (when the TV has one) and marks it on the case
 * @param {array} $case - service_cases row
 * @param {array} $case_tv - service_case_tvs row
 * @param {array} $r - service_case_inspections row
 * @returns {string|null} pwrp_id, null when the TV has no warranty record
 */
function appstv_case_inspect_record_repair( $case, $case_tv, $r ){
    if( (string) $case_tv[ 'product_sn_id' ] === '' ){ return null; }
    $chain = appstv_case_tv_chain( $case_tv[ 'product_sn_id' ] );
    if( $chain === null || $chain[ 'psnw_id' ] === null ){ return null; }
    $remarks = ( $r[ 'paid' ] === 'yes' ? 'Paid repair on site' : 'Repaired on site' ) . ' (' . appstv_case_label( $case ) . '): ' . $r[ 'repair_note' ];
    $pwrp_id = appstv_case_unique_id( 'product_warranty_replacements', 'pwrp_id' );
    appstv_case_db( "INSERT INTO product_warranty_replacements ( pwrp_id, is_repairable, psnw_id, date_of_service, new_invoice_id, new_product_sn_id, remarks )
                     VALUES ( '$pwrp_id', 1, '" . escape_string( $chain[ 'psnw_id' ] ) . "', '" . appstv_case_today() . " 00:00:00', NULL, NULL,
                              '" . escape_string( mb_substr( $remarks, 0, 2000 ) ) . "' )" );
    if( (string) $case_tv[ 'pwrp_id' ] === '' ){
        appstv_case_db( "UPDATE service_case_tvs SET pwrp_id = '$pwrp_id' WHERE id = " . (int) $case_tv[ 'id' ] );
    }
    return $pwrp_id;
}


/**
 * Inspection of one TV of a case: step 'start' makes (or reads) it; any other step saves that answer and answers
 * with the inspection, its next step and its result
 */
function appstv_case_inspect(){
    checkAuthorizationForFunction( __FUNCTION__ );
    appstv_case_transaction( __FUNCTION__, function(){
        $case = appstv_case_read( request( 'case_id' ), true );
        $id = (int) $case[ 'id' ];
        if( $case[ 'status' ] === 'closed' ){ throw new AppstvServiceRuleException( "This case is closed. Reopen it first." ); }
        $case_tv = mysqli_fetch_assoc( appstv_case_db( "SELECT * FROM service_case_tvs WHERE id = " . (int) request( 'case_tv_id' ) . " AND case_id = $id FOR UPDATE" ) );
        if( $case_tv === null ){ throw new AppstvServiceRuleException( "That TV is not on this case." ); }
        $serial = $case_tv[ 'serial_number' ];
        $chain = (string) $case_tv[ 'product_sn_id' ] !== '' ? appstv_case_tv_chain( $case_tv[ 'product_sn_id' ] ) : null;
        if( $chain !== null && !$chain[ 'is_current' ] ){
            throw new AppstvServiceRuleException( "TV $serial was already replaced by {$chain['current_serial_number']}. Add that TV to the case and inspect it." );
        }

        $now = create_iso_8601_datetime();
        $e_user = escape_string( appstv_case_user_id() );
        $r = mysqli_fetch_assoc( appstv_case_db( "SELECT * FROM service_case_inspections WHERE case_tv_id = " . (int) $case_tv[ 'id' ] . " FOR UPDATE" ) );
        if( $r === null ){
            $warranty = appstv_case_inspect_warranty( $chain );
            appstv_case_db( "INSERT INTO service_case_inspections ( case_id, case_tv_id, warranty, charge, started_by, started_on, updated_by, updated_on )
                             VALUES ( $id, " . (int) $case_tv[ 'id' ] . ", '$warranty', '" . ( $warranty === 'out' ? 'to_collect' : '' ) . "', '$e_user', '$now', '$e_user', '$now' )" );
            $r = mysqli_fetch_assoc( appstv_case_db( "SELECT * FROM service_case_inspections WHERE case_tv_id = " . (int) $case_tv[ 'id' ] ) );
            appstv_case_history( $id, null, 'inspected', 'inspection', null, null, "Inspection of TV $serial started"
                                 . ( $warranty === 'under' ? ' (under warranty)' : ( $warranty === 'out' ? ' (out of warranty: service charge to collect)' : '' ) ) );
        }
        $step = (string) request( 'step' );
        if( $step === 'start' ){ return array( "Inspection of TV $serial.", array( 'inspection' => appstv_case_inspect_item( $r ) ) ); }
        if( $step === 'charge' ){ return appstv_case_inspect_charge( $id, $r, $serial ); }

        if( (string) $r[ 'pwrp_id' ] !== '' ){ throw new AppstvServiceRuleException( "The repair of TV $serial is already in the warranty records; the inspection cannot be changed." ); }
        if( $step !== 'paid' && !in_array( $step, APPSTV_INSPECT_STEPS, true ) ){ throw new AppstvServiceRuleException( "Unknown inspection step." ); }
        if( $step === 'warranty' && appstv_case_inspect_warranty( $chain ) !== '' ){ throw new AppstvServiceRuleException( "The warranty of TV $serial comes from its serial number." ); }
        list( $expected, $old_result ) = appstv_case_inspect_flow( $r );
        $answered = function( $s ) use ( $r ){
            if( $s === 'paid' ){ return $r[ 'paid' ] !== ''; }
            return $r[ $s ] !== '';
        };
        if( $step !== $expected && !$answered( $step ) ){ throw new AppstvServiceRuleException( "Answer the steps in order." ); }

        $in = json_decode( (string) request( 'answer' ), true );
        if( !is_array( $in ) ){ $in = array(); }
        // The same answer again (Back, then Next) keeps the later answers; a changed answer clears them
        list( $same, $sentence ) = appstv_case_inspect_apply( $r, $step, $in, $serial );
        if( $same == $r ){ return array( "Nothing changed.", array( 'inspection' => appstv_case_inspect_item( $r ) ) ); }
        $r = appstv_case_inspect_clear_after( $r, $step );
        list( $r, $sentence ) = appstv_case_inspect_apply( $r, $step, $in, $serial );
        list( $next, $result ) = appstv_case_inspect_flow( $r );

        // Service charge: out of warranty, or a paid repair (collected stays collected)
        $charge = $r[ 'charge' ];
        if( $charge !== 'collected' ){ $charge = ( $r[ 'warranty' ] === 'out' || $r[ 'paid' ] === 'yes' ) ? 'to_collect' : ''; }

        $sets = array();
        foreach( array( 'warranty', 'paid', 'outside', 'outside_kinds', 'open_here', 'no_open_reason', 'inside', 'repair', 'part_name' ) as $field ){
            $sets[] = "$field = '" . escape_string( (string) $r[ $field ] ) . "'";
        }
        foreach( array( 'outside_note', 'no_open_note', 'inside_note', 'repair_note' ) as $field ){
            $sets[] = "$field = " . ( $r[ $field ] === null || $r[ $field ] === '' ? "NULL" : "'" . escape_string( $r[ $field ] ) . "'" );
        }
        $sets[] = "result = '$result'";
        $sets[] = "charge = '$charge'";
        $sets[] = "updated_by = '$e_user'";
        $sets[] = "updated_on = '$now'";
        $sets[] = "finished_on = '" . ( $result === '' ? '' : $now ) . "'";
        appstv_case_db( "UPDATE service_case_inspections SET " . implode( ', ', $sets ) . " WHERE id = " . (int) $r[ 'id' ] );

        appstv_case_history( $id, null, 'inspected', 'inspection', null, null, "Inspection of TV $serial: $sentence" );
        if( $result !== '' && $result !== $old_result ){
            // "TV 123: Not covered by warranty - the customer does not want a paid repair" (no repeat when the sentence says it)
            $line = ( stripos( $sentence, APPSTV_INSPECT_RESULTS[ $result ] ) === 0 ) ? "TV $serial: " . ucfirst( $sentence )
                                                                                     : "TV $serial: " . APPSTV_INSPECT_RESULTS[ $result ] . ' - ' . $sentence;
            if( $result === 'repaired' ){
                $pwrp_id = appstv_case_inspect_record_repair( $case, $case_tv, $r );
                if( $pwrp_id !== null ){
                    appstv_case_db( "UPDATE service_case_inspections SET pwrp_id = '$pwrp_id' WHERE id = " . (int) $r[ 'id' ] );
                    $line .= ' (written into the warranty records)';
                }
            }
            appstv_case_log( $id, 'inspect', $line . ( $charge === 'to_collect' ? '. Service charge to collect.' : '' ) );
        } else {
            appstv_case_db( "UPDATE service_cases SET updated_on = '$now' WHERE id = $id" );
        }

        $r = mysqli_fetch_assoc( appstv_case_db( "SELECT * FROM service_case_inspections WHERE id = " . (int) $r[ 'id' ] ) );
        $message = ( $result === '' ) ? "Saved." : "TV $serial: " . APPSTV_INSPECT_RESULTS[ $result ] . ".";
        return array( $message, array( 'inspection' => appstv_case_inspect_item( $r ) ) );
    } );
}


/**
 * @description Step 'charge' of appstv_case_inspect: ticks the service charge of the TV as collected, or back to
 *              "to collect" (request collected = 1 | 0)
 * @param {int} $id - service_cases.id (locked)
 * @param {array} $r - service_case_inspections row (locked)
 * @param {string} $serial - Serial number
 * @returns {array} [ message, data ]
 */
function appstv_case_inspect_charge( $id, $r, $serial ){
    if( $r[ 'charge' ] === '' ){ throw new AppstvServiceRuleException( "No service charge applies to this TV." ); }
    $collected = ( (string) request( 'collected' ) === '1' );
    $state = $collected ? 'collected' : 'to_collect';
    if( $state === $r[ 'charge' ] ){ return array( "Nothing changed.", array( 'inspection' => appstv_case_inspect_item( $r ) ) ); }
    $now = create_iso_8601_datetime();
    $e_user = escape_string( appstv_case_user_id() );
    appstv_case_db( "UPDATE service_case_inspections SET charge = '$state', charge_by = '" . ( $collected ? $e_user : '' ) . "', charge_on = '" . ( $collected ? $now : '' ) . "'
                     WHERE id = " . (int) $r[ 'id' ] );
    $text = "Service charge of TV $serial " . ( $collected ? 'collected' : 'marked as not collected yet' );
    appstv_case_history( $id, null, 'inspected', 'charge', null, null, $text );
    appstv_case_log( $id, 'inspect', $text );
    $r = mysqli_fetch_assoc( appstv_case_db( "SELECT * FROM service_case_inspections WHERE id = " . (int) $r[ 'id' ] ) );
    return array( $text . ".", array( 'inspection' => appstv_case_inspect_item( $r ) ) );
}
?>
