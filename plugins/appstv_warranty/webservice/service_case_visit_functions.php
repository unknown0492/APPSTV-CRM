<?php

/*
 * Service case visits (v1.0.26) - a case can need more than one trip to the site
 *
 * Every trip is a row of service_case_visits that ends with a result:
 *   fixed    the case becomes Visited (then it is closed as before)
 *   revisit  needs another visit: a reason (spare part, tools, customer / room not available, senior technician,
 *            other), the spare part with its Needed -> Ordered -> Arrived state, a note. The case becomes 'revisit';
 *            planning the next visit date makes it Scheduled again ("Visit 2").
 * A case waiting in 'revisit' for more than APPSTV_CASE_REVISIT_DAYS days without a planned date counts as a missed
 * deadline (red, in the Overdue count and the daily digest).
 * Technicians and managers both use these (every role that can update cases).
 */

define( 'APPSTV_CASE_REVISIT_DAYS', 3 );
define( 'APPSTV_CASE_REVISIT_REASONS', array(
    'part'          => 'Spare part needed',
    'tools'         => 'More tools needed',
    'not_available' => 'Customer or room not available',
    'senior'        => 'Needs a senior technician',
    'office'        => 'TV taken to the office',                 // 1.0.33
    'other'         => 'Other'
) );
define( 'APPSTV_CASE_PART_STATES', array( 'needed' => 'Needed', 'ordered' => 'Ordered', 'arrived' => 'Arrived' ) );


/**
 * @description The visits of a case, newest first, as shown on the Visits tab
 * @param {int} $case_id - service_cases.id
 * @returns {array} [ { id, visit_no, visited_on, staff: [ { user_id, name } ], result, reason, reason_text, part_name,
 *                    part_status, part_status_by, part_status_on, note, by, on } ]
 */
function appstv_case_visit_rows( $case_id ){
    $out = array();
    foreach( appstv_case_rows( "SELECT * FROM service_case_visits WHERE case_id = " . (int) $case_id . " ORDER BY visit_no DESC" ) as $row ){
        $staff = array();
        foreach( ( $row[ 'staff' ] === '' ) ? array() : explode( ',', $row[ 'staff' ] ) as $user_id ){
            $staff[] = array( 'user_id' => $user_id, 'name' => appstv_case_user_name( $user_id ) );
        }
        $out[] = array(
            'id' => (int) $row[ 'id' ], 'visit_no' => (int) $row[ 'visit_no' ], 'visited_on' => $row[ 'visited_on' ], 'staff' => $staff,
            'result' => $row[ 'result' ], 'reason' => $row[ 'reason' ],
            'reason_text' => array_key_exists( $row[ 'reason' ], APPSTV_CASE_REVISIT_REASONS ) ? APPSTV_CASE_REVISIT_REASONS[ $row[ 'reason' ] ] : '',
            'part_name' => $row[ 'part_name' ], 'part_status' => $row[ 'part_status' ],
            'part_status_by' => appstv_case_user_name( $row[ 'part_status_by' ] ), 'part_status_on' => $row[ 'part_status_on' ],
            'note' => (string) $row[ 'note' ], 'by' => appstv_case_user_name( $row[ 'created_by' ] ), 'on' => $row[ 'created_on' ]
        );
    }
    return $out;
}

/**
 * @description Days a case has been waiting for its next visit to be planned (0 when it is not waiting)
 * @param {array} $case - service_cases row
 * @returns {int}
 */
function appstv_case_revisit_wait( $case ){
    if( $case[ 'status' ] !== 'revisit' || (string) $case[ 'visit_date' ] !== '' ){ return 0; }
    $row = mysqli_fetch_assoc( appstv_case_db( "SELECT visited_on FROM service_case_visits WHERE case_id = " . (int) $case[ 'id' ] . " ORDER BY visit_no DESC LIMIT 1" ) );
    if( $row === null ){ return 0; }
    $from = DateTime::createFromFormat( '!Y-m-d', $row[ 'visited_on' ] );
    $to = DateTime::createFromFormat( '!Y-m-d', appstv_case_today() );
    return ( $from && $to ) ? max( 0, (int) $from->diff( $to )->format( '%r%a' ) ) : 0;
}

/**
 * @description The sentence of a finished visit for the timeline and the history
 * @param {int} $visit_no - 1, 2 ...
 * @param {string} $result - fixed | revisit
 * @param {string} $reason - Revisit reason key
 * @param {string} $part_name - Spare part ('' = none)
 * @returns {string} e.g. "finished visit 1: needs another visit (spare part needed: T-Con board)"
 */
function appstv_case_visit_sentence( $visit_no, $result, $reason, $part_name ){
    if( $result === 'fixed' ){ return "finished visit $visit_no: fixed"; }
    $why = strtolower( APPSTV_CASE_REVISIT_REASONS[ $reason ] ) . ( $part_name !== '' ? ': ' . $part_name : '' );
    return "finished visit $visit_no: needs another visit ($why)";
}


/**
 * Ends a visit to the site: fixed (the case becomes Visited), or needs another visit (reason, spare part, note; the
 * case becomes 'revisit', or Scheduled when the next visit date is given at once)
 */
function appstv_case_visit_finish(){
    checkAuthorizationForFunction( __FUNCTION__ );
    appstv_case_transaction( __FUNCTION__, function(){
        $case = appstv_case_read( request( 'case_id' ), true );
        $id = (int) $case[ 'id' ];
        $label = appstv_case_label( $case );
        if( $case[ 'status' ] === 'closed' ){ throw new AppstvServiceRuleException( "This case is closed. Reopen it first." ); }
        if( $case[ 'status' ] === 'visited' ){ throw new AppstvServiceRuleException( "The last visit of this case was already finished as fixed. Reopen the case first." ); }

        $today = appstv_case_today();
        $visited_on = trim( (string) request( 'visited_on' ) );
        if( $visited_on === '' ){ $visited_on = $today; }
        if( !appstv_case_valid_date( $visited_on ) ){ throw new AppstvServiceRuleException( "The date of the visit is not valid." ); }
        if( $visited_on > $today ){ throw new AppstvServiceRuleException( "The date of the visit cannot be in the future." ); }
        if( $visited_on < date( 'Y-m-d', strtotime( $today . ' -1 year' ) ) ){ throw new AppstvServiceRuleException( "The date of the visit is more than a year ago." ); }

        $staff = json_decode( (string) request( 'staff' ), true );
        if( !is_array( $staff ) ){ $staff = array(); }
        $staff = array_values( array_unique( array_filter( array_map( 'strval', $staff ), 'strlen' ) ) );
        if( count( $staff ) === 0 ){ throw new AppstvServiceRuleException( "Tick who went to the site." ); }
        foreach( $staff as $user_id ){
            $on_case = mysqli_num_rows( appstv_case_db( "SELECT 1 FROM service_case_staff WHERE case_id = " . (int) request( 'case_id' ) . " AND user_id = '" . escape_string( $user_id ) . "' LIMIT 1" ) ) > 0;
            if( !$on_case && !appstv_case_is_staff( $user_id ) ){ throw new AppstvServiceRuleException( "One of the staff members ticked is not a visit technician." ); }
        }

        $result = (string) request( 'result' );
        if( !in_array( $result, array( 'fixed', 'revisit' ) ) ){ throw new AppstvServiceRuleException( "Choose the result of the visit." ); }
        $reason = '';
        $part_name = '';
        $note = trim( (string) request( 'note' ) );
        if( mb_strlen( $note ) > 2000 ){ throw new AppstvServiceRuleException( "The note is too long (max 2000)." ); }
        if( $result === 'revisit' ){
            $reason = (string) request( 'reason' );
            if( !array_key_exists( $reason, APPSTV_CASE_REVISIT_REASONS ) ){ throw new AppstvServiceRuleException( "Choose why another visit is needed." ); }
            if( $reason === 'part' ){
                $part_name = trim( (string) request( 'part_name' ) );
                if( $part_name === '' ){ throw new AppstvServiceRuleException( "Write which spare part is needed." ); }
                if( mb_strlen( $part_name ) > 200 ){ throw new AppstvServiceRuleException( "The spare part name is too long (max 200)." ); }
            }
            if( $reason === 'other' && $note === '' ){ throw new AppstvServiceRuleException( "Write why another visit is needed." ); }
        }

        // Next visit at once (optional, revisit only)
        list( $next_date, $next_time ) = appstv_case_visit_fields( $result === 'revisit' ? request( 'next_date' ) : '', $result === 'revisit' ? request( 'next_time' ) : '' );
        if( $next_date !== '' && $next_date < $today ){ throw new AppstvServiceRuleException( "The next visit cannot be in the past." ); }

        $row = mysqli_fetch_assoc( appstv_case_db( "SELECT COALESCE( MAX( visit_no ), 0 ) AS n FROM service_case_visits WHERE case_id = $id" ) );
        $visit_no = (int) $row[ 'n' ] + 1;
        $now = create_iso_8601_datetime();
        $e_user = escape_string( appstv_case_user_id() );
        appstv_case_db( "INSERT INTO service_case_visits ( case_id, visit_no, visited_on, staff, result, reason, part_name, part_status, note, created_by, created_on )
                         VALUES ( $id, $visit_no, '$visited_on', '" . escape_string( implode( ',', $staff ) ) . "', '$result', '$reason',
                                  '" . escape_string( $part_name ) . "', '" . ( $reason === 'part' ? 'needed' : '' ) . "',
                                  " . ( $note === '' ? "NULL" : "'" . escape_string( $note ) . "'" ) . ", '$e_user', '$now' )" );

        if( $result === 'fixed' ){
            appstv_case_db( "UPDATE service_cases SET status = 'visited' WHERE id = $id" );
            $status = 'visited';
        } else {
            // The window / date were for this visit; the next one is planned on its own
            $status = ( $next_date !== '' ) ? 'scheduled' : 'revisit';
            appstv_case_db( "UPDATE service_cases SET status = '$status', visit_date = '$next_date', visit_time = '$next_time', visit_from = '', visit_to = '' WHERE id = $id" );
        }

        $sentence = appstv_case_visit_sentence( $visit_no, $result, $reason, $part_name );
        appstv_case_log( $id, 'status', ucfirst( $sentence ) . ( $note !== '' ? ' - ' . $note : '' ) );
        appstv_case_history( $id, null, 'visit_finished', '', null,
                             array( 'visit_no' => $visit_no, 'result' => $result, 'reason' => $reason, 'part_name' => $part_name, 'visited_on' => $visited_on, 'note' => $note ),
                             $sentence );
        if( $next_date !== '' ){
            appstv_case_history( $id, null, 'changed', 'visit_date', null, $next_date . ( $next_time !== '' ? ' ' . $next_time : '' ), 'visit ' . ( $visit_no + 1 ) );
            appstv_case_log( $id, 'visit', 'Visit ' . ( $visit_no + 1 ) . ' set for ' . $next_date . ( $next_time !== '' ? ' ' . $next_time : '' ) );
        }

        $message = ( $result === 'fixed' ) ? "Visit $visit_no of $label finished: fixed."
                 : "Visit $visit_no of $label finished. " . ( $next_date !== '' ? "Visit " . ( $visit_no + 1 ) . " is planned." : "Plan the next visit when ready." );
        return array( $message, array( 'case_id' => $id, 'status' => $status, 'visit_no' => $visit_no ) );
    } );
}


/**
 * Marks the spare part of a visit as needed / ordered / arrived (so the office knows when to plan the next visit)
 */
function appstv_case_visit_part(){
    checkAuthorizationForFunction( __FUNCTION__ );
    appstv_case_transaction( __FUNCTION__, function(){
        $visit = mysqli_fetch_assoc( appstv_case_db( "SELECT * FROM service_case_visits WHERE id = " . (int) request( 'visit_id' ) . " FOR UPDATE" ) );
        if( $visit === null ){ throw new AppstvServiceRuleException( "This visit does not exist." ); }
        $case = appstv_case_read( $visit[ 'case_id' ], true );
        $id = (int) $case[ 'id' ];
        if( $case[ 'status' ] === 'closed' ){ throw new AppstvServiceRuleException( "This case is closed. Reopen it first." ); }
        if( $visit[ 'reason' ] !== 'part' ){ throw new AppstvServiceRuleException( "This visit has no spare part." ); }
        $state = (string) request( 'part_status' );
        if( !array_key_exists( $state, APPSTV_CASE_PART_STATES ) ){ throw new AppstvServiceRuleException( "Choose the state of the spare part." ); }
        if( $state === $visit[ 'part_status' ] ){ return array( "Nothing changed.", array( 'case_id' => $id ) ); }

        $now = create_iso_8601_datetime();
        appstv_case_db( "UPDATE service_case_visits SET part_status = '$state', part_status_by = '" . escape_string( appstv_case_user_id() ) . "', part_status_on = '$now'
                         WHERE id = " . (int) $visit[ 'id' ] );
        appstv_case_db( "UPDATE service_cases SET updated_on = '$now' WHERE id = $id" );
        $old = array_key_exists( $visit[ 'part_status' ], APPSTV_CASE_PART_STATES ) ? APPSTV_CASE_PART_STATES[ $visit[ 'part_status' ] ] : 'Needed';
        appstv_case_history( $id, null, 'part_status', 'part_status', $old, APPSTV_CASE_PART_STATES[ $state ], $visit[ 'part_name' ] );
        appstv_case_log( $id, 'part', 'Spare part ' . $visit[ 'part_name' ] . ': ' . strtolower( APPSTV_CASE_PART_STATES[ $state ] ) );
        $message = "Spare part " . $visit[ 'part_name' ] . " marked as " . strtolower( APPSTV_CASE_PART_STATES[ $state ] ) . "."
                 . ( $state === 'arrived' && $case[ 'status' ] === 'revisit' ? " Plan the next visit now." : "" );
        return array( $message, array( 'case_id' => $id ) );
    } );
}
