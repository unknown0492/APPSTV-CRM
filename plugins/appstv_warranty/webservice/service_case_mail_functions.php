<?php
/*
 * Service case e-mails to staff (appstv_crm 1.0.28). Helpers only: no functionality of its own.
 *
 *   New case:  appstv_case_mail_queue() is called while the case is opened (same transaction); the every-minute
 *              job (cron/appstv_service_cases.php mail) sends one e-mail per assigned staff member.
 *   Added:     the same e-mail ("You were added to case ...") when someone is added to a case later (1.0.29).
 *   9:00 list: the daily job (cron/appstv_service_cases.php digest) sends each staff member one e-mail with all
 *              their cases that are not closed, the ones needing action first:
 *                1. Visit deadline missed (red, every day until the case is closed)
 *                2. Next visit not planned for more than APPSTV_CASE_REVISIT_DAYS days (red)
 *                3. Needs another visit (orange)
 *                4. Visit today / in the visit window
 *                5. Visited, waiting to be closed
 *                6. Coming up
 * Every e-mail is a row of service_case_mails; its dedupe_key stops it being sent twice.
 */

define( 'APPSTV_CASE_MAIL_ATTEMPTS', 3 );                     // Tries before an e-mail is "failed"
define( 'APPSTV_CASE_MAIL_BATCH', 25 );                       // E-mails sent per run of the every-minute job
define( 'APPSTV_CASE_MAIL_PROBLEMS', array( 'no_power' => 'No power', 'no_picture' => 'No picture', 'no_sound' => 'No sound', 'remote' => 'Remote',
    'wifi_apps' => 'Wi-Fi / apps', 'broken_screen' => 'Broken screen', 'physical_damage' => 'Physical damage', 'liquid_damage' => 'Liquid damage', 'other' => 'Other' ) );
define( 'APPSTV_CASE_MAIL_WARRANTY', array( 'under' => 'Under warranty', 'out' => 'Out of warranty', 'mixed' => 'With or without warranty', 'unknown' => 'Not known' ) );


// ── Queue ───────────────────────────────────────────────────────────────────────────────────────────────────

/**
 * @description Queues the "new case" e-mail for each staff member of a case (called inside the opening transaction,
 *              so nothing is queued when the opening fails)
 * @param {int} $case_id - The case (single or main case)
 * @param {array} $user_ids - users.user_id of the staff
 * @param {string} $kind - opened (case opened) | added (added to the case later, 1.0.29)
 * @returns {void}
 */
function appstv_case_mail_queue( $case_id, $user_ids, $kind = 'opened' ){
    $case_id = (int) $case_id;
    $now = create_iso_8601_datetime();
    $e_kind = ( $kind === 'added' ) ? 'added' : 'opened';
    foreach( array_unique( $user_ids ) as $user_id ){
        $user_id = (string) $user_id;
        if( $user_id === '' ){ continue; }
        $e_user = escape_string( $user_id );
        // "opened" once per case and person; "added" each time (removed and added again = a new e-mail)
        $key = ( $e_kind === 'opened' ) ? "'" . escape_string( 'opened:' . $case_id . ':' . $user_id ) . "'" : "NULL";
        appstv_case_db( "INSERT IGNORE INTO service_case_mails ( kind, case_id, user_id, dedupe_key, state, created_on )
                         VALUES ( '$e_kind', $case_id, '$e_user', $key, 'queued', '$now' )" );
    }
}


// ── Building blocks ─────────────────────────────────────────────────────────────────────────────────────────

/**
 * @description Address of the site (from site_config), without a trailing slash
 * @returns {string} e.g. https://myappstv.com
 */
function appstv_case_mail_site_url(){
    $site = getSiteConfig();
    return ( $site->protocol === 'https' ? 'https' : 'http' ) . '://' . rtrim( (string) $site->domain_name, '/' );
}

/**
 * @description Link that opens a case on the Service Cases page
 * @param {int} $case_id - The case
 * @returns {string}
 */
function appstv_case_mail_link( $case_id ){
    return appstv_case_mail_site_url() . '/adminpanel.php?what_do_you_want=service_cases&case=' . (int) $case_id;
}

/**
 * @description Escapes text for the e-mail HTML
 * @param {string} $text - Plain text
 * @returns {string}
 */
function appstv_case_mail_esc( $text ){
    return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
}

/**
 * @description A date as "Fri 14 Nov 2026"
 * @param {string} $ymd - 'YYYY-MM-DD' ('' = '')
 * @returns {string}
 */
function appstv_case_mail_date( $ymd ){
    if( !is_string( $ymd ) || !preg_match( '/^\d{4}-\d{2}-\d{2}$/', $ymd ) ){ return ''; }
    return date( 'D j M Y', strtotime( $ymd . ' 12:00:00' ) );
}

/**
 * @description Visit window or planned visit as text ("Fri 14 Nov 2026 10:00", "Fri 14 – Mon 17 Nov 2026")
 * @param {array} $row - service_cases row
 * @returns {string}
 */
function appstv_case_mail_when( $row ){
    if( (string) $row[ 'visit_date' ] !== '' ){
        return appstv_case_mail_date( $row[ 'visit_date' ] ) . ( (string) $row[ 'visit_time' ] !== '' ? ' ' . $row[ 'visit_time' ] : '' ) . ' (planned)';
    }
    if( (string) $row[ 'visit_from' ] === '' ){ return 'Not planned yet'; }
    if( $row[ 'visit_from' ] === $row[ 'visit_to' ] ){ return appstv_case_mail_date( $row[ 'visit_from' ] ); }
    return appstv_case_mail_date( $row[ 'visit_from' ] ) . ' – ' . appstv_case_mail_date( $row[ 'visit_to' ] );
}

/**
 * @description Whole days from one date to another
 * @param {string} $from - 'YYYY-MM-DD'
 * @param {string} $to - 'YYYY-MM-DD'
 * @returns {int}
 */
function appstv_case_mail_days( $from, $to ){
    return (int) round( ( strtotime( $to . ' 12:00:00' ) - strtotime( $from . ' 12:00:00' ) ) / 86400 );
}

/**
 * @description Fills the e-mail template (templates/email/service_case_notice.php)
 * @param {string} $heading - Title (plain text)
 * @param {string} $intro - Text under the title (plain text)
 * @param {string} $body - Main part (HTML)
 * @param {array|null} $button - [ 'text' => ..., 'url' => ... ] or null
 * @param {string} $note - Small text at the bottom (plain text)
 * @returns {string} HTML
 */
function appstv_case_mail_page( $heading, $intro, $body, $button, $note ){
    $site_name = (string) getSiteConfig()->site_name;
    $action = '';
    if( is_array( $button ) ){
        $url = appstv_case_mail_esc( $button[ 'url' ] );
        $action = '<table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center"><tr><td align="center" bgcolor="#0b5cad" style="border-radius:10px;">'
                . '<a href="' . $url . '" target="_blank" style="display:inline-block; padding:14px 30px; font-size:16px; font-weight:600; color:#ffffff; text-decoration:none; border-radius:10px;">'
                . appstv_case_mail_esc( $button[ 'text' ] ) . '</a></td></tr></table>';
    }
    $html = (string) @file_get_contents( BASE_PATH . '/templates/email/service_case_notice.php' );
    return str_replace(
        array( '{{site_name}}', '{{heading}}', '{{intro}}', '{{body}}', '{{action}}', '{{note}}' ),
        array( appstv_case_mail_esc( $site_name ), appstv_case_mail_esc( $heading ), nl2br( appstv_case_mail_esc( $intro ) ), $body, $action, nl2br( appstv_case_mail_esc( $note ) ) ),
        $html );
}

/**
 * @description A staff member's name and e-mail address
 * @param {string} $user_id - users.user_id
 * @returns {array|null} { name, email } (email '' when missing or not valid), null when the user does not exist
 */
function appstv_case_mail_user( $user_id ){
    $row = mysqli_fetch_assoc( appstv_case_db( "SELECT CONVERT( fname USING utf8mb4 ) AS fname, CONVERT( lname USING utf8mb4 ) AS lname, CONVERT( email USING utf8mb4 ) AS email
                                                FROM users WHERE user_id = '" . escape_string( $user_id ) . "' LIMIT 1" ) );
    if( $row === null ){ return null; }
    $email = trim( (string) $row[ 'email' ] );
    return array( 'name' => trim( $row[ 'fname' ] . ' ' . $row[ 'lname' ] ), 'email' => filter_var( $email, FILTER_VALIDATE_EMAIL ) ? $email : '' );
}

/**
 * @description Sends one e-mail (or, with $dry_dir, writes it to a file there instead: tests on this PC only)
 * @param {string} $to - Address
 * @param {string} $subject - Subject (the site name is added)
 * @param {string} $html - Body
 * @param {string} $dry_dir - '' to send, else a folder for the file
 * @returns {string} '' when sent, else the error
 */
function appstv_case_mail_send( $to, $subject, $html, $dry_dir ){
    $site_name = (string) getSiteConfig()->site_name;
    if( $dry_dir !== '' ){
        $name = rtrim( $dry_dir, '/\\' ) . '/' . date( 'His' ) . '_' . substr( md5( $to . $subject . microtime() ), 0, 8 ) . '.html';
        return file_put_contents( $name, '<!-- To: ' . appstv_case_mail_esc( $to ) . ' | Subject: ' . appstv_case_mail_esc( $subject . ' | ' . $site_name ) . " -->\n" . $html ) ? '' : 'Could not write the file';
    }
    try {
        $mail = sendMailObject();
        $mail->isHTML( true );
        $mail->CharSet = 'UTF-8';
        $mail->setFrom( EMAIL_NOREPLY, $site_name );
        $mail->addAddress( $to );
        $mail->Subject = $subject . ' | ' . $site_name;
        $mail->Body = $html;
        $mail->AltBody = trim( html_entity_decode( strip_tags( preg_replace( '/<(br|\/tr|\/div|\/p)[^>]*>/i', "\n", $html ) ), ENT_QUOTES, 'UTF-8' ) );
        return $mail->send() ? '' : substr( (string) $mail->ErrorInfo, 0, 250 );
    } catch( Throwable $e ){
        return substr( 'Not sent: ' . $e->getMessage(), 0, 250 );
    }
}


// ── New case ────────────────────────────────────────────────────────────────────────────────────────────────

/**
 * @description The "new case" e-mail for one staff member
 * @param {int} $case_id - The case
 * @param {string} $name - The staff member's name
 * @param {string} $kind - opened | added (added to the case later)
 * @returns {array|null} [ subject, html ], null when the case no longer exists
 */
function appstv_case_mail_opened( $case_id, $name, $kind = 'opened' ){
    $case_id = (int) $case_id;
    $c = mysqli_fetch_assoc( appstv_case_db( "SELECT c.*, cu.company_name, cu.first_name, cu.last_name FROM service_cases c
                                              LEFT JOIN customers cu ON cu.customer_id = c.customer_id WHERE c.id = $case_id" ) );
    if( $c === null ){ return null; }
    $e = 'appstv_case_mail_esc';
    $label = appstv_case_label( $c );
    $customer = appstv_case_customer_name( $c );

    $problems = array();
    foreach( (string) $c[ 'problem_types' ] === '' ? array() : explode( ',', $c[ 'problem_types' ] ) as $p ){
        $problems[] = array_key_exists( $p, APPSTV_CASE_MAIL_PROBLEMS ) ? APPSTV_CASE_MAIL_PROBLEMS[ $p ] : $p;
    }
    $models = array();
    foreach( appstv_case_rows( "SELECT m.product_id, p.name FROM service_case_models m LEFT JOIN products p ON p.product_id = m.product_id WHERE m.case_id = $case_id ORDER BY p.name" ) as $row ){
        $models[] = $row[ 'product_id' ] === 'unsure' ? 'Not sure' : (string) $row[ 'name' ];
    }
    $serials = array();
    foreach( appstv_case_rows( "SELECT t.serial_number FROM service_case_tvs t JOIN service_cases k ON k.id = t.case_id
                                WHERE k.id = $case_id OR k.parent_case_id = $case_id ORDER BY k.tv_index, t.id" ) as $row ){
        $serials[] = $row[ 'serial_number' ];
    }
    $staff = array();
    foreach( appstv_case_rows( "SELECT user_id FROM service_case_staff WHERE case_id = $case_id ORDER BY added_on, user_id" ) as $row ){
        $staff[] = appstv_case_user_name( $row[ 'user_id' ] );
    }
    $tvs = (int) $c[ 'tv_count' ] . ' TV' . ( (int) $c[ 'tv_count' ] > 1 ? 's' : '' )
         . ( $c[ 'case_kind' ] === 'main' ? ' · one case per TV (' . $label . '-01 to ' . sprintf( '%s-%02d', $label, $c[ 'tv_count' ] ) . ')' : '' );
    $phone = (string) $c[ 'contact_phone' ];
    $contact = $e( $c[ 'contact_name' ] ) . ( (string) $c[ 'contact_position' ] !== '' ? ', ' . $e( $c[ 'contact_position' ] ) : '' )
             . ( $phone !== '' ? ' · <a href="tel:' . $e( preg_replace( '/[^0-9+]/', '', $phone ) ) . '" style="color:#0b5cad; text-decoration:none; font-weight:600;">' . $e( $phone ) . '</a>' : '' );

    $rows = array(
        array( 'Customer', '<b>' . $e( $customer ) . '</b>' ),
        array( 'Who complained', $contact ),
        array( 'Visit', '<b style="color:#854d0e;">' . $e( appstv_case_mail_when( $c ) ) . '</b>' ),
        array( 'TVs', $e( $tvs ) ),
        array( 'Models', $e( implode( ', ', $models ) ?: 'Not given' ) ),
        array( 'Warranty', $e( array_key_exists( (string) $c[ 'warranty_status' ], APPSTV_CASE_MAIL_WARRANTY ) ? APPSTV_CASE_MAIL_WARRANTY[ $c[ 'warranty_status' ] ] : '' ) ),
        array( 'Serial numbers', $e( count( $serials ) ? implode( ', ', $serials ) : 'Not added yet' ) ),
        array( 'Problem', ( count( $problems ) ? '<b>' . $e( implode( ', ', $problems ) ) . '</b><br>' : '' ) . nl2br( $e( $c[ 'problem' ] ) ) ),
        array( 'Assigned to', $e( implode( ', ', array_filter( $staff ) ) ) ),
        array( 'Opened by', $e( trim( appstv_case_user_name( $c[ 'created_by' ] ) ) ?: 'System' ) )
    );
    $body = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #e4e6ef; border-radius:10px; border-collapse:separate;">';
    foreach( $rows as $i => $r ){
        $line = $i ? 'border-top:1px solid #eef0f5;' : '';
        $body .= '<tr><td valign="top" style="' . $line . ' padding:10px 12px; width:130px; font-size:13px; color:#5e6278;">' . $e( $r[ 0 ] ) . '</td>'
               . '<td valign="top" style="' . $line . ' padding:10px 12px; font-size:14px; line-height:1.5; color:#181c32;">' . $r[ 1 ] . '</td></tr>';
    }
    $body .= '</table>';

    $added = ( $kind === 'added' );
    $subject = ( $added ? "You were added to service case" : "New service case" ) . " $label · $customer";
    $html = appstv_case_mail_page( $added ? "You were added to case $label" : "New case $label for you",
                                   $added ? "Hello $name, you were added to this case." : "Hello $name, this case was assigned to you.", $body,
                                   array( 'text' => "Open $label", 'url' => appstv_case_mail_link( $case_id ) ),
                                   "You will find it in your 9:00 list every morning until it is closed." );
    return array( $subject, $html );
}


// ── Daily list ──────────────────────────────────────────────────────────────────────────────────────────────

/**
 * @description Where a case stands today, for the daily list
 * @param {array} $c - service_cases row + lv_on (date of the last visit) + revisit_tvs (main case)
 * @param {string} $today - 'YYYY-MM-DD' (Singapore)
 * @returns {array} [ group, text ] group: late | revisit_late | revisit | now | close | later
 */
function appstv_case_mail_standing( $c, $today ){
    $status = $c[ 'status' ];
    if( $status === 'revisit' && (string) $c[ 'visit_date' ] === '' ){
        $wait = (string) $c[ 'lv_on' ] === '' ? 0 : max( 0, appstv_case_mail_days( $c[ 'lv_on' ], $today ) );
        $why = (string) $c[ 'lv_reason_text' ] !== '' ? ' · ' . $c[ 'lv_reason_text' ] . ( (string) $c[ 'lv_part' ] !== '' ? ': ' . $c[ 'lv_part' ] : '' ) : '';
        if( $wait > APPSTV_CASE_REVISIT_DAYS ){ return array( 'revisit_late', "Next visit not planned for $wait days$why. Please plan it." ); }
        return array( 'revisit', 'Needs another visit' . ( $wait ? " · waiting $wait day" . ( $wait > 1 ? 's' : '' ) : ' · since today' ) . "$why. Please plan it." );
    }
    $end = (string) $c[ 'visit_date' ] !== '' ? $c[ 'visit_date' ] : (string) $c[ 'visit_to' ];
    $start = (string) $c[ 'visit_date' ] !== '' ? $c[ 'visit_date' ] : (string) $c[ 'visit_from' ];
    if( $end !== '' && $end < $today ){
        $late = appstv_case_mail_days( $end, $today );
        return array( 'late', ( $status === 'visited' ? 'Visited, but not closed' : 'Visit deadline missed' ) . " $late day" . ( $late > 1 ? 's' : '' ) . ' ago. Please close this case soon.' );
    }
    if( $status === 'visited' ){ return array( 'close', 'Visited. Close the case when the work is done.' ); }
    if( $start !== '' && $start > $today ){
        $in = appstv_case_mail_days( $today, $start );
        return array( 'later', 'Starts in ' . $in . ' day' . ( $in > 1 ? 's' : '' ) . '.' );
    }
    if( $end === '' ){ return array( 'later', 'Visit not planned yet.' ); }
    $left = appstv_case_mail_days( $today, $end );
    return array( 'now', $left === 0 ? 'Visit due today (last day).' : 'In the visit window · ' . $left . ' day' . ( $left > 1 ? 's' : '' ) . ' left.' );
}

/**
 * @description The daily list of one staff member's cases that are not closed
 * @param {string} $user_id - users.user_id
 * @param {string} $name - The staff member's name
 * @param {string} $today - 'YYYY-MM-DD' (Singapore)
 * @returns {array|null} [ subject, html, counts ], null when they have no case
 */
function appstv_case_mail_digest( $user_id, $name, $today ){
    $e_user = escape_string( $user_id );
    $cases = appstv_case_rows( "SELECT c.*, cu.company_name, cu.first_name, cu.last_name,
                                       lv.visited_on AS lv_on, lv.reason AS lv_reason, lv.part_name AS lv_part,
                                       ( SELECT COUNT(*) FROM service_cases k WHERE k.parent_case_id = c.id AND k.case_kind = 'tv' AND k.status = 'revisit' ) AS revisit_tvs,
                                       ( SELECT COUNT(*) FROM service_cases k WHERE k.parent_case_id = c.id AND k.case_kind = 'tv' AND k.status <> 'closed' ) AS open_tvs
                                FROM service_cases c
                                LEFT JOIN service_case_visits lv ON lv.case_id = c.id AND lv.visit_no = ( SELECT MAX( x.visit_no ) FROM service_case_visits x WHERE x.case_id = c.id )
                                LEFT JOIN customers cu ON cu.customer_id = c.customer_id
                                WHERE c.case_kind <> 'tv' AND c.status <> 'closed'
                                  AND ( c.assigned_user_id = '$e_user' OR EXISTS ( SELECT 1 FROM service_case_staff s WHERE s.case_id = c.id AND s.user_id = '$e_user' ) )
                                ORDER BY COALESCE( NULLIF( c.visit_date, '' ), c.visit_to ), c.id" );
    if( count( $cases ) === 0 ){ return null; }

    $groups = array(
        'late'         => array( 'Visit deadline missed – please close these cases soon', '#b42318', '#fef2f2' ),
        'revisit_late' => array( 'Next visit not planned for more than ' . APPSTV_CASE_REVISIT_DAYS . ' days', '#b42318', '#fef2f2' ),
        'revisit'      => array( 'Needs another visit – plan the next visit', '#c2410c', '#fff7ed' ),
        'now'          => array( 'Visit today / in the visit window', '#854d0e', '#fefce8' ),
        'close'        => array( 'Visited – close when done', '#166534', '#f0fdf4' ),
        'later'        => array( 'Coming up', '#0b4f8a', '#f0f7ff' )
    );
    $by = array_fill_keys( array_keys( $groups ), array() );
    foreach( $cases as $c ){
        $c[ 'lv_reason_text' ] = array_key_exists( (string) $c[ 'lv_reason' ], APPSTV_CASE_REVISIT_REASONS ) ? APPSTV_CASE_REVISIT_REASONS[ $c[ 'lv_reason' ] ] : '';
        list( $group, $text ) = appstv_case_mail_standing( $c, $today );
        if( (int) $c[ 'revisit_tvs' ] > 0 ){ $text .= ' ' . (int) $c[ 'revisit_tvs' ] . ' TV case' . ( (int) $c[ 'revisit_tvs' ] > 1 ? 's need' : ' needs' ) . ' another visit.'; }
        $by[ $group ][] = array( $c, $text );
    }

    $e = 'appstv_case_mail_esc';
    $body = '';
    foreach( $groups as $key => $g ){
        if( count( $by[ $key ] ) === 0 ){ continue; }
        $body .= '<div style="margin:18px 0 8px 0; padding:8px 12px; border-radius:8px; background-color:' . $g[ 1 ] . '; color:#ffffff; font-size:14px; font-weight:700;">'
               . $e( $g[ 0 ] ) . ' (' . count( $by[ $key ] ) . ')</div>';
        foreach( $by[ $key ] as $item ){
            list( $c, $text ) = $item;
            $problems = array();
            foreach( (string) $c[ 'problem_types' ] === '' ? array() : explode( ',', $c[ 'problem_types' ] ) as $p ){
                $problems[] = array_key_exists( $p, APPSTV_CASE_MAIL_PROBLEMS ) ? APPSTV_CASE_MAIL_PROBLEMS[ $p ] : $p;
            }
            $problem = implode( ', ', $problems );
            $said = trim( preg_replace( '/\s+/', ' ', (string) $c[ 'problem' ] ) );
            if( $said !== '' ){ $problem .= ( $problem !== '' ? ' – ' : '' ) . ( mb_strlen( $said ) > 120 ? mb_substr( $said, 0, 117 ) . '…' : $said ); }
            $phone = (string) $c[ 'contact_phone' ];
            $tvs = $c[ 'case_kind' ] === 'main' ? ' · ' . (int) $c[ 'tv_count' ] . ' TV cases' . ( (int) $c[ 'open_tvs' ] < (int) $c[ 'tv_count' ] ? ' (' . (int) $c[ 'open_tvs' ] . ' open)' : '' )
                 : ( (int) $c[ 'tv_count' ] > 1 ? ' · ' . (int) $c[ 'tv_count' ] . ' TVs' : '' );
            $body .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 8px 0; border:1px solid #e4e6ef; border-left:4px solid ' . $g[ 1 ] . '; border-radius:8px; border-collapse:separate; background-color:' . $g[ 2 ] . ';">'
                   . '<tr><td style="padding:10px 12px; font-size:14px; line-height:1.5; color:#181c32;">'
                   . '<a href="' . $e( appstv_case_mail_link( $c[ 'id' ] ) ) . '" style="color:#0b5cad; font-weight:700; text-decoration:none;">' . $e( appstv_case_label( $c ) ) . '</a>'
                   . ' &nbsp;<b>' . $e( appstv_case_customer_name( $c ) ) . '</b>' . $e( $tvs ) . '<br>'
                   . '<span style="color:' . $g[ 1 ] . '; font-weight:700;">' . $e( $text ) . '</span><br>'
                   . '<span style="color:#3f4254;">Visit: ' . $e( appstv_case_mail_when( $c ) ) . '</span>'
                   . ( $problem !== '' ? '<br><span style="color:#3f4254;">' . $e( $problem ) . '</span>' : '' )
                   . ( (string) $c[ 'contact_name' ] !== '' || $phone !== '' ? '<br><span style="color:#5e6278; font-size:13px;">' . $e( $c[ 'contact_name' ] )
                       . ( $phone !== '' ? ' · <a href="tel:' . $e( preg_replace( '/[^0-9+]/', '', $phone ) ) . '" style="color:#0b5cad; text-decoration:none;">' . $e( $phone ) . '</a>' : '' ) . '</span>' : '' )
                   . '</td></tr></table>';
        }
    }

    $counts = array_map( 'count', $by );
    $urgent = $counts[ 'late' ] + $counts[ 'revisit_late' ];
    $total = count( $cases );
    $subject = 'Your service cases today: ' . ( $urgent ? $urgent . ' need action now · ' : '' ) . $total . ' open';
    $intro = "Hello $name, you have $total case" . ( $total > 1 ? 's' : '' ) . ' that ' . ( $total > 1 ? 'are' : 'is' ) . ' not closed'
           . ( $urgent ? ". $urgent of them " . ( $urgent > 1 ? 'are' : 'is' ) . ' late (in red).' : '.' );
    $html = appstv_case_mail_page( 'Your service cases · ' . appstv_case_mail_date( $today ), $intro, $body,
                                   array( 'text' => 'Open Service Cases', 'url' => appstv_case_mail_site_url() . '/adminpanel.php?what_do_you_want=service_cases' ),
                                   'Sent every morning at 9:00 while you have cases that are not closed. A case stays in red every day until it is closed.' );
    return array( $subject, $html, $counts );
}
