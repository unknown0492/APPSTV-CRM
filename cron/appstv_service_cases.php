<?php
/*
 * Service case scheduled jobs (appstv_crm 1.0.22) - command line only, prints nothing when there is nothing to do.
 *
 *   php cron/appstv_service_cases.php media    every minute: converts uploaded videos (one at a time, lock file)
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

define( 'APPSTV_VIDEO_TARGET_BYTES', 24 * 1024 * 1024 );    // Aim (limit 25 MB, 1 MB spare for the container)
define( 'APPSTV_VIDEO_LIMIT_BYTES', 25 * 1024 * 1024 );     // Must stay under
define( 'APPSTV_VIDEO_ATTEMPTS', 3 );                       // Tries before "failed"

$task = isset( $argv[ 1 ] ) ? $argv[ 1 ] : '';
if( $task === 'media' ){
    appstv_cron_media();
} else {
    fwrite( STDERR, "Usage: php cron/appstv_service_cases.php media\n" );
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
