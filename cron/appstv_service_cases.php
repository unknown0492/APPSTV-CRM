<?php
/*
 * Service case scheduled jobs (appstv_crm 1.0.28) - command line only, prints nothing when there is nothing to do.
 *
 *   php cron/appstv_service_cases.php every    every minute: sends queued e-mails (mail), then converts a video (media)
 *   php cron/appstv_service_cases.php digest   daily at 9:00 Singapore time: each staff member's list of open cases
 *   php cron/appstv_service_cases.php media    videos only;  ... mail    queued e-mails only
 *
 * Option --dry=<folder> (mail / digest, only with a database on this computer): writes the e-mails as .html files
 * there instead of sending them (tests).
 *
 * E-mails (1.0.28, service_case_mails): "new case" e-mails are queued when a case is opened and sent here, up to
 * APPSTV_CASE_MAIL_BATCH per run, 3 attempts each. The digest is sent once per staff member per day (dedupe_key),
 * so running it twice does not send twice.
 *
 * Video conversion: ffprobe reads the length; the bitrate is chosen so the MP4 stays under 25 MB (H.264 + AAC,
 * fast start), the picture is reduced (1080 / 720 / 480 / 360 lines) when the length needs it, never enlarged.
 * A result over 25 MB is redone once at 80 % of the bitrate. The original is deleted only when the MP4 is ready.
 * After 3 failed attempts the video stays "failed" with its original still viewable.
 *
 * ffmpeg / ffprobe: ~/bin/ffmpeg and ~/bin/ffprobe (static build in the account), else the ones on the PATH.
 */

if( php_sapi_name() !== 'cli' ){
    http_response_code( 404 );
    exit;
}

$root = dirname( __DIR__ );
chdir( $root );
require_once $root . '/load-all.php';
require_once $root . '/plugins/appstv_warranty/webservice/service_case_functions.php';
require_once $root . '/plugins/appstv_warranty/webservice/service_case_wizard_functions.php';
require_once $root . '/plugins/appstv_warranty/webservice/service_case_draft_functions.php';
require_once $root . '/plugins/appstv_warranty/webservice/service_case_media_functions.php';
require_once $root . '/plugins/appstv_warranty/webservice/service_case_visit_functions.php';
require_once $root . '/plugins/appstv_warranty/webservice/service_case_mail_functions.php';

define( 'APPSTV_VIDEO_TARGET_BYTES', 24 * 1024 * 1024 );    // Aim (limit 25 MB, 1 MB spare for the container)
define( 'APPSTV_VIDEO_LIMIT_BYTES', 25 * 1024 * 1024 );     // Must stay under
define( 'APPSTV_VIDEO_ATTEMPTS', 3 );                       // Tries before "failed"

$task = isset( $argv[ 1 ] ) ? $argv[ 1 ] : '';
$dry = '';                                                     // --dry=<folder>: write the e-mails to files (tests)
foreach( array_slice( $argv, 2 ) as $arg ){
    if( strpos( $arg, '--dry=' ) === 0 ){ $dry = substr( $arg, 6 ); }
}
if( $dry !== '' ){
    connect();
    global $con;
    if( !is_dir( $dry ) || !preg_match( '/^(localhost|127\.0\.0\.1)\b/i', mysqli_get_host_info( $con ) ) ){
        fwrite( STDERR, "--dry needs an existing folder and a database on this computer\n" );
        exit( 2 );
    }
}
if( $task === 'media' ){
    appstv_cron_media();
} else if( $task === 'mail' ){
    appstv_cron_mail( $dry );
} else if( $task === 'every' ){
    appstv_cron_mail( '' );
    appstv_cron_media();
} else if( $task === 'digest' ){
    appstv_cron_digest( $dry );
} else {
    fwrite( STDERR, "Usage: php cron/appstv_service_cases.php every | digest | mail | media [--dry=<folder>]\n" );
    exit( 2 );
}


/**
 * @description Path of a tool (ffmpeg / ffprobe): the account's static build first, else the PATH
 * @param {string} $name - ffmpeg | ffprobe
 * @returns {string|null} Command, null when not found
 */
function appstv_cron_tool( $name ){
    $home = getenv( 'HOME' ) ?: ( function_exists( 'posix_getpwuid' ) ? ( posix_getpwuid( posix_geteuid() )[ 'dir' ] ?? '' ) : '' );
    if( $home !== '' && is_executable( $home . '/bin/' . $name ) ){ return $home . '/bin/' . $name; }
    $out = array(); $code = 1;
    @exec( ( DIRECTORY_SEPARATOR === '\\' ? 'where ' : 'command -v ' ) . $name . ' 2>' . ( DIRECTORY_SEPARATOR === '\\' ? 'NUL' : '/dev/null' ), $out, $code );
    return ( $code === 0 && isset( $out[ 0 ] ) && trim( $out[ 0 ] ) !== '' ) ? trim( $out[ 0 ] ) : null;
}

/**
 * @description Runs a command, returns its exit code (output kept in $output)
 * @param {string} $cmd - Command line (arguments already escaped)
 * @param {array} $output - Lines printed
 * @returns {int} Exit code
 */
function appstv_cron_run( $cmd, &$output ){
    $output = array(); $code = 1;
    exec( $cmd . ' 2>&1', $output, $code );
    return $code;
}

/**
 * @description Converts the next queued video (one per run; a lock stops two runs at once)
 * @returns {void}
 */
function appstv_cron_media(){
    global $con;
    $lock = fopen( appstv_media_dir( 'tmp' ) . 'convert.lock', 'c' );
    if( !$lock || !flock( $lock, LOCK_EX | LOCK_NB ) ){ return; }      // Another run is converting
    set_time_limit( 0 );
    connect();

    // Leftovers: uploads never finished within a day, and videos stuck "processing" (the run was killed)
    $day_ago = ( new DateTime( '-1 day' ) )->format( 'c' );
    foreach( appstv_case_rows( "SELECT * FROM service_case_media WHERE state = 'uploading' AND deleted_on = '' AND uploaded_on < '$day_ago'" ) as $row ){
        appstv_media_unlink( $row );
        appstv_case_db( "UPDATE service_case_media SET deleted_on = '" . create_iso_8601_datetime() . "', error = 'Upload not finished' WHERE id = " . (int) $row[ 'id' ] );
    }
    appstv_case_db( "UPDATE service_case_media SET state = 'queued' WHERE state = 'processing' AND deleted_on = '' AND processed_on < '" . ( new DateTime( '-2 hours' ) )->format( 'c' ) . "'" );

    $row = mysqli_fetch_assoc( appstv_case_db( "SELECT * FROM service_case_media WHERE kind = 'video' AND state = 'queued' AND deleted_on = ''
                                                AND attempts < " . APPSTV_VIDEO_ATTEMPTS . " ORDER BY id LIMIT 1" ) );
    if( $row === null ){ return; }
    $id = (int) $row[ 'id' ];
    $ffmpeg = appstv_cron_tool( 'ffmpeg' );
    $ffprobe = appstv_cron_tool( 'ffprobe' );
    if( $ffmpeg === null || $ffprobe === null ){
        error_log( "appstv_service_cases media: ffmpeg / ffprobe not found" );
        return;                                                        // Stays queued until the tools are installed
    }
    appstv_case_db( "UPDATE service_case_media SET state = 'processing', attempts = attempts + 1, processed_on = '" . create_iso_8601_datetime() . "' WHERE id = $id" );

    $src = appstv_media_dir() . $row[ 'source_file' ];
    $base = preg_replace( '/_src\.[a-z0-9]+$/i', '', $row[ 'source_file' ] );
    $mp4 = appstv_media_dir() . $base . '.mp4';
    $poster = appstv_media_dir() . $base . '_t.jpg';
    $fail = function( $message ) use ( $id, $row ){
        $state = ( (int) $row[ 'attempts' ] + 1 >= APPSTV_VIDEO_ATTEMPTS ) ? 'failed' : 'queued';
        appstv_case_db( "UPDATE service_case_media SET state = '$state', error = '" . escape_string( substr( $message, 0, 250 ) ) . "' WHERE id = $id" );
        error_log( "appstv_service_cases media $id: $message" );
    };
    if( !is_file( $src ) ){ $fail( "The uploaded video is missing" ); return; }

    // Length and picture size
    $out = array();
    appstv_cron_run( escapeshellarg( $ffprobe ) . ' -v error -select_streams v:0 -show_entries format=duration:stream=width,height:stream_tags=rotate -of json ' . escapeshellarg( $src ), $out );
    $probe = json_decode( implode( "\n", $out ), true );
    $duration = isset( $probe[ 'format' ][ 'duration' ] ) ? (float) $probe[ 'format' ][ 'duration' ] : 0;
    $w = (int) ( $probe[ 'streams' ][ 0 ][ 'width' ] ?? 0 );
    $h = (int) ( $probe[ 'streams' ][ 0 ][ 'height' ] ?? 0 );
    if( $duration <= 0 || $w <= 0 || $h <= 0 ){ $fail( "The video cannot be read" ); return; }
    $short_side = min( $w, $h );                                       // Phones film upright: the short side is the "lines"

    $audio_k = 96;
    $total_k = (int) floor( APPSTV_VIDEO_TARGET_BYTES * 8 / 1000 / $duration );
    $video_k = max( 100, (int) floor( $total_k * 0.97 ) - $audio_k );
    $made = false;
    for( $try = 0; $try < 2 && !$made; $try++ ){
        $lines = ( $video_k >= 3000 ) ? 1080 : ( ( $video_k >= 1500 ) ? 720 : ( ( $video_k >= 600 ) ? 480 : 360 ) );   // Lines a bitrate keeps sharp
        $lines = min( $lines, $short_side - ( $short_side % 2 ) );
        // Scale the short side to $lines, keep the shape (even sizes for H.264)
        $scale = ( $w >= $h ) ? "scale=-2:$lines" : "scale=$lines:-2";
        $cmd = ( DIRECTORY_SEPARATOR === '/' ? 'nice -n 10 ' : '' ) . escapeshellarg( $ffmpeg ) . ' -y -hide_banner -loglevel error -i ' . escapeshellarg( $src )
             . ' -vf ' . escapeshellarg( $scale ) . ' -c:v libx264 -preset veryfast -profile:v high -pix_fmt yuv420p'
             . ' -b:v ' . $video_k . 'k -maxrate ' . (int) ( $video_k * 1.4 ) . 'k -bufsize ' . ( $video_k * 2 ) . 'k'
             . ' -c:a aac -b:a ' . $audio_k . 'k -ac 2 -movflags +faststart ' . escapeshellarg( $mp4 );
        if( appstv_cron_run( $cmd, $out ) !== 0 || !is_file( $mp4 ) ){
            @unlink( $mp4 );
            $fail( "Conversion failed: " . implode( ' ', array_slice( $out, -2 ) ) );
            return;
        }
        clearstatcache( true, $mp4 );
        if( filesize( $mp4 ) < APPSTV_VIDEO_LIMIT_BYTES ){ $made = true; }
        else { $video_k = max( 80, (int) floor( $video_k * 0.8 ) ); }
    }
    if( !$made ){ @unlink( $mp4 ); $fail( "The video is too long to fit in 25 MB" ); return; }

    // Poster frame (1 s in, or the first frame of a very short video)
    appstv_cron_run( escapeshellarg( $ffmpeg ) . ' -y -hide_banner -loglevel error -ss ' . ( $duration > 2 ? '1' : '0' ) . ' -i ' . escapeshellarg( $mp4 )
                   . ' -frames:v 1 -vf ' . escapeshellarg( 'scale=' . APPSTV_MEDIA_THUMB_PX . ':-2' ) . ' ' . escapeshellarg( $poster ), $out );
    appstv_cron_run( escapeshellarg( $ffprobe ) . ' -v error -select_streams v:0 -show_entries stream=width,height -of csv=p=0 ' . escapeshellarg( $mp4 ), $out );
    $dims = explode( ',', trim( (string) ( $out[ 0 ] ?? '0,0' ) ) );

    // Database first, then the original goes
    $row_now = mysqli_fetch_assoc( appstv_case_db( "SELECT deleted_on FROM service_case_media WHERE id = $id" ) );
    if( $row_now === null || $row_now[ 'deleted_on' ] !== '' ){ @unlink( $mp4 ); @unlink( $poster ); return; }   // Removed meanwhile
    appstv_case_db( "UPDATE service_case_media SET state = 'ready', file_name = '" . escape_string( $base . '.mp4' ) . "',
                            thumb_file = '" . ( is_file( $poster ) ? escape_string( $base . '_t.jpg' ) : '' ) . "', source_file = '', mime = 'video/mp4',
                            size_bytes = " . (int) filesize( $mp4 ) . ", width = " . (int) ( $dims[ 0 ] ?? 0 ) . ", height = " . (int) ( $dims[ 1 ] ?? 0 ) . ",
                            duration_s = " . round( $duration, 2 ) . ", error = '', processed_on = '" . create_iso_8601_datetime() . "' WHERE id = $id" );
    @unlink( $src );
}


/**
 * @description Sends the queued "new case" e-mails (each row is claimed first, so two runs never send the same one)
 * @param {string} $dry - '' to send, else a folder for the files
 * @returns {void}
 */
function appstv_cron_mail( $dry ){
    global $con;
    connect();
    // Rows left "sending" by a run that was killed go back to the queue
    appstv_case_db( "UPDATE service_case_mails SET state = 'queued' WHERE state = 'sending' AND kind IN ( 'opened', 'added' ) AND sent_on < '" . ( new DateTime( '-1 hour' ) )->format( 'c' ) . "'" );
    $rows = appstv_case_rows( "SELECT * FROM service_case_mails WHERE state = 'queued' AND kind IN ( 'opened', 'added' ) AND attempts < " . APPSTV_CASE_MAIL_ATTEMPTS . " ORDER BY id LIMIT " . APPSTV_CASE_MAIL_BATCH );
    foreach( $rows as $row ){
        $id = (int) $row[ 'id' ];
        $now = create_iso_8601_datetime();
        appstv_case_db( "UPDATE service_case_mails SET state = 'sending', attempts = attempts + 1, sent_on = '$now' WHERE id = $id AND state = 'queued'" );
        if( mysqli_affected_rows( $con ) !== 1 ){ continue; }           // Another run took it
        $user = appstv_case_mail_user( $row[ 'user_id' ] );
        if( $user === null || $user[ 'email' ] === '' ){
            appstv_case_db( "UPDATE service_case_mails SET state = 'skipped', error = '" . ( $user === null ? 'User not found' : 'No valid e-mail address' ) . "' WHERE id = $id" );
            continue;
        }
        $mail = appstv_case_mail_opened( $row[ 'case_id' ], $user[ 'name' ], $row[ 'kind' ] );
        if( $mail === null ){
            appstv_case_db( "UPDATE service_case_mails SET state = 'skipped', error = 'Case not found' WHERE id = $id" );
            continue;
        }
        $error = appstv_case_mail_send( $user[ 'email' ], $mail[ 0 ], $mail[ 1 ], $dry );
        $state = ( $error === '' ) ? 'sent' : ( ( (int) $row[ 'attempts' ] + 1 >= APPSTV_CASE_MAIL_ATTEMPTS ) ? 'failed' : 'queued' );
        appstv_case_db( "UPDATE service_case_mails SET state = '$state', error = '" . escape_string( $error ) . "', to_email = '" . escape_string( $user[ 'email' ] ) . "',
                                subject = '" . escape_string( mb_substr( $mail[ 0 ], 0, 250 ) ) . "', sent_on = '" . create_iso_8601_datetime() . "' WHERE id = $id" );
        if( $error !== '' ){ error_log( "appstv_service_cases mail $id: $error" ); }
    }
}

/**
 * @description Sends each staff member the list of their cases that are not closed (once per day: dedupe_key)
 * @param {string} $dry - '' to send, else a folder for the files
 * @returns {void}
 */
function appstv_cron_digest( $dry ){
    global $con;
    connect();
    set_time_limit( 0 );
    $today = appstv_case_today();
    // Everyone on a case that is not closed (also someone whose role lost the visit technician permission, 1.0.29)
    $staff = appstv_case_rows( "SELECT DISTINCT s.user_id FROM service_case_staff s JOIN service_cases c ON c.id = s.case_id
                                WHERE c.status <> 'closed' AND c.case_kind <> 'tv'
                                UNION
                                SELECT DISTINCT c.assigned_user_id FROM service_cases c
                                WHERE c.status <> 'closed' AND c.case_kind <> 'tv' AND c.assigned_user_id IS NOT NULL AND c.assigned_user_id <> ''" );
    foreach( $staff as $s ){
        $user = appstv_case_mail_user( $s[ 'user_id' ] );
        if( $user === null ){ continue; }
        $mail = appstv_case_mail_digest( $s[ 'user_id' ], $user[ 'name' ], $today );
        if( $mail === null ){ continue; }                               // No open case: no e-mail
        $e_user = escape_string( $s[ 'user_id' ] );
        $e_key = escape_string( 'digest:' . $s[ 'user_id' ] . ':' . $today );
        $now = create_iso_8601_datetime();
        appstv_case_db( "INSERT IGNORE INTO service_case_mails ( kind, case_id, user_id, dedupe_key, state, attempts, subject, created_on, sent_on )
                         VALUES ( 'digest', NULL, '$e_user', '$e_key', 'sending', 1, '" . escape_string( mb_substr( $mail[ 0 ], 0, 250 ) ) . "', '$now', '$now' )" );
        if( mysqli_affected_rows( $con ) !== 1 ){ continue; }           // Already sent today
        $id = (int) mysqli_insert_id( $con );
        if( $user[ 'email' ] === '' ){
            appstv_case_db( "UPDATE service_case_mails SET state = 'skipped', error = 'No valid e-mail address' WHERE id = $id" );
            continue;
        }
        $error = appstv_case_mail_send( $user[ 'email' ], $mail[ 0 ], $mail[ 1 ], $dry );
        appstv_case_db( "UPDATE service_case_mails SET state = '" . ( $error === '' ? 'sent' : 'failed' ) . "', error = '" . escape_string( $error ) . "',
                                to_email = '" . escape_string( $user[ 'email' ] ) . "', sent_on = '" . create_iso_8601_datetime() . "' WHERE id = $id" );
        if( $error !== '' ){ error_log( "appstv_service_cases digest {$s['user_id']}: $error" ); }
    }
}
