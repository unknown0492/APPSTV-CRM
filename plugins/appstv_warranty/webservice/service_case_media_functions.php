<?php

/*
 * Service case photos and videos (v1.0.22)
 *
 * Upload in pieces (the server takes at most 2 MB per request): appstv_case_media_upload with step =
 *   start  - case_id or draft_id, name, size, type, kind (photo | video): makes the service_case_media row
 *            (state uploading) and answers media_id, upload key, piece size and number of pieces
 *   part   - media_id, key, index + the piece (multipart field "chunk"), written at its place in one file
 *   finish - media_id, key: checks the size and the contents; a photo is made ready at once (thumbnail made),
 *            a video is queued for conversion (scheduled job, cron/appstv_service_cases.php media)
 *
 * Photos arrive already cropped / rotated / reduced to 1600 px JPEG by the browser; the server re-reduces any
 * that are larger. Videos: up to 500 MB; converted by ffmpeg to H.264 MP4 under 25 MB.
 *
 * Files live in data/service_media (no web access, .htaccess "Require all denied"); they are read through
 * appstv_case_media_file, which checks the sign-in and streams with Range support (videos can be scrolled).
 */

define( 'APPSTV_MEDIA_CHUNK', 1536 * 1024 );                 // Piece size (under the 2 MB upload limit)
define( 'APPSTV_MEDIA_MAX_PHOTO', 30 * 1024 * 1024 );        // Largest photo accepted (browser normally sends < 1 MB)
define( 'APPSTV_MEDIA_MAX_VIDEO', 500 * 1024 * 1024 );       // Largest video accepted
define( 'APPSTV_MEDIA_PHOTO_PX', 1600 );                     // Long side of a stored photo
define( 'APPSTV_MEDIA_THUMB_PX', 400 );                      // Long side of a thumbnail
define( 'APPSTV_MEDIA_MAX_FILES', 60 );                      // Photos / videos per case or draft


/**
 * @description Folder of the stored files (made on first use, closed to the web)
 * @param {string} $sub - '' for the root, 'tmp' for pieces being uploaded
 * @returns {string} Absolute path with a trailing separator
 */
function appstv_media_dir( $sub = '' ){
    $root = dirname( __DIR__, 3 ) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'service_media';
    if( !is_dir( $root ) ){ @mkdir( $root, 0755, true ); }
    if( !is_file( $root . DIRECTORY_SEPARATOR . '.htaccess' ) ){
        @file_put_contents( $root . DIRECTORY_SEPARATOR . '.htaccess', "Require all denied\nDeny from all\n" );
    }
    $dir = $root . ( $sub !== '' ? DIRECTORY_SEPARATOR . $sub : '' );
    if( !is_dir( $dir ) ){ @mkdir( $dir, 0755, true ); }
    return $dir . DIRECTORY_SEPARATOR;
}

/**
 * @description Upload key of a media row (proves the pieces come from whoever started the upload)
 * @param {array} $row - service_case_media row
 * @returns {string}
 */
function appstv_media_key( $row ){
    return substr( hash_hmac( 'sha256', $row[ 'id' ] . '|' . $row[ 'uploaded_on' ] . '|' . $row[ 'uploaded_by' ], __FILE__ . php_uname( 'n' ) ), 0, 32 );
}

/**
 * @description Reads a media row (locked when asked)
 * @param {int} $media_id - service_case_media.id
 * @param {bool} $lock - Lock it inside a transaction
 * @returns {array} The row
 * @throws AppstvServiceRuleException when it does not exist or was removed
 */
function appstv_media_read( $media_id, $lock = false ){
    $row = mysqli_fetch_assoc( appstv_case_db( "SELECT * FROM service_case_media WHERE id = " . (int) $media_id . ( $lock ? " FOR UPDATE" : "" ) ) );
    if( $row === null || $row[ 'deleted_on' ] !== '' ){ throw new AppstvServiceRuleException( "This photo / video was removed." ); }
    return $row;
}

/**
 * @description Photos and videos as sent to the page
 * @param {string} $where - SQL condition on service_case_media (alias m)
 * @returns {array} [ { id, kind, state, name, size, width, height, duration, uploaded_by, uploaded_on, error, case_id } ]
 */
function appstv_media_list( $where ){
    $out = array();
    foreach( appstv_case_rows( "SELECT m.* FROM service_case_media m WHERE m.deleted_on = '' AND m.state <> 'uploading' AND ( $where ) ORDER BY m.id" ) as $row ){
        $out[] = array( 'id' => (int) $row[ 'id' ], 'kind' => $row[ 'kind' ], 'state' => $row[ 'state' ], 'name' => $row[ 'original_name' ],
                        'size' => (int) $row[ 'size_bytes' ], 'source_size' => (int) $row[ 'source_bytes' ],
                        'width' => (int) $row[ 'width' ], 'height' => (int) $row[ 'height' ], 'duration' => (float) $row[ 'duration_s' ],
                        'has_thumb' => $row[ 'thumb_file' ] !== '', 'error' => $row[ 'error' ], 'case_id' => $row[ 'case_id' ] === null ? null : (int) $row[ 'case_id' ],
                        'uploaded_by' => appstv_case_user_name( $row[ 'uploaded_by' ] ), 'uploaded_on' => $row[ 'uploaded_on' ] );
    }
    return $out;
}

/**
 * @description Removes the files of a media row from the disk
 * @param {array} $row - service_case_media row
 * @returns {void}
 */
function appstv_media_unlink( $row ){
    foreach( array( 'file_name', 'source_file', 'thumb_file' ) as $col ){
        if( isset( $row[ $col ] ) && $row[ $col ] !== '' && strpos( $row[ $col ], '..' ) === false ){
            @unlink( appstv_media_dir() . $row[ $col ] );
        }
    }
    @unlink( appstv_media_dir( 'tmp' ) . (int) $row[ 'id' ] . '.part' );
}

/**
 * @description Makes a JPEG of at most $px on the long side from an image file (GD)
 * @param {string} $src - Source image (JPEG / PNG / WebP / GIF)
 * @param {string} $dst - JPEG to write
 * @param {int} $px - Longest side
 * @param {int} $quality - JPEG quality
 * @returns {array|null} [ width, height ] written, null when the image cannot be read
 */
function appstv_media_jpeg( $src, $dst, $px, $quality ){
    $info = @getimagesize( $src );
    if( $info === false ){ return null; }
    $img = null;
    if( $info[ 2 ] === IMAGETYPE_JPEG ){ $img = @imagecreatefromjpeg( $src ); }
    else if( $info[ 2 ] === IMAGETYPE_PNG ){ $img = @imagecreatefrompng( $src ); }
    else if( $info[ 2 ] === IMAGETYPE_GIF ){ $img = @imagecreatefromgif( $src ); }
    else if( defined( 'IMAGETYPE_WEBP' ) && $info[ 2 ] === IMAGETYPE_WEBP && function_exists( 'imagecreatefromwebp' ) ){ $img = @imagecreatefromwebp( $src ); }
    if( !$img ){ return null; }
    // Photos straight from a camera: turn them upright (EXIF orientation)
    if( $info[ 2 ] === IMAGETYPE_JPEG && function_exists( 'exif_read_data' ) ){
        $exif = @exif_read_data( $src );
        $turn = array( 3 => 180, 6 => -90, 8 => 90 );
        if( $exif && isset( $exif[ 'Orientation' ], $turn[ $exif[ 'Orientation' ] ] ) ){ $img = imagerotate( $img, $turn[ $exif[ 'Orientation' ] ], 0 ); }
    }
    $w = imagesx( $img ); $h = imagesy( $img );
    $scale = min( 1, $px / max( $w, $h ) );
    $nw = max( 1, (int) round( $w * $scale ) ); $nh = max( 1, (int) round( $h * $scale ) );
    $out = imagecreatetruecolor( $nw, $nh );
    imagefill( $out, 0, 0, imagecolorallocate( $out, 255, 255, 255 ) );   // Transparent PNG -> white
    imagecopyresampled( $out, $img, 0, 0, 0, 0, $nw, $nh, $w, $h );
    $ok = imagejpeg( $out, $dst, $quality );
    return $ok ? array( $nw, $nh ) : null;
}

/**
 * @description Where an upload goes: the case (main case of a TV case stays the TV case itself) or the draft
 * @param {int} $case_id - service_cases.id (0 = none)
 * @param {int} $draft_id - service_case_drafts.id (0 = none)
 * @returns {array} [ case_id|null, draft_id|null, SQL condition of the same target ]
 * @throws AppstvServiceRuleException when neither exists
 */
function appstv_media_target( $case_id, $draft_id ){
    if( $case_id > 0 ){
        $case = appstv_case_read( $case_id );
        if( $case[ 'status' ] === 'closed' ){ throw new AppstvServiceRuleException( "This case is closed. Reopen it to add photos or videos." ); }
        return array( (int) $case[ 'id' ], null, "m.case_id = " . (int) $case[ 'id' ] );
    }
    if( $draft_id > 0 ){
        $draft = appstv_case_draft_read( $draft_id );
        return array( null, (int) $draft[ 'id' ], "m.draft_id = " . (int) $draft[ 'id' ] . " AND m.case_id IS NULL" );
    }
    throw new AppstvServiceRuleException( "Save the case or draft first." );
}


/**
 * Uploads a photo or video in pieces (step start | part | finish), see the top of this file
 */
function appstv_case_media_upload(){
    checkAuthorizationForFunction( __FUNCTION__ );
    $step = (string) request( 'step' );
    if( $step === 'part' ){ appstv_media_part(); return; }
    appstv_case_transaction( __FUNCTION__, function() use ( $step ){
        if( $step === 'start' ){ return appstv_media_start(); }
        if( $step === 'finish' ){ return appstv_media_finish(); }
        throw new AppstvServiceRuleException( "Unknown upload step." );
    } );
}

/**
 * @description Step start: checks the file and makes its row
 * @returns {array} [ message, data ]
 */
function appstv_media_start(){
    list( $case_id, $draft_id, $same ) = appstv_media_target( (int) request( 'case_id' ), (int) request( 'draft_id' ) );
    $kind = (string) request( 'kind' );
    $name = mb_substr( preg_replace( '/[\x00-\x1F\/\\\\]/u', '', trim( (string) request( 'name' ) ) ), 0, 200 );
    $size = (int) request( 'size' );
    $mime = substr( preg_replace( '/[^a-z0-9.+\/-]/i', '', (string) request( 'type' ) ), 0, 100 );
    if( !in_array( $kind, array( 'photo', 'video' ), true ) ){ throw new AppstvServiceRuleException( "Only photos and videos can be added." ); }
    if( $size <= 0 ){ throw new AppstvServiceRuleException( "The file is empty." ); }
    if( $kind === 'photo' && $size > APPSTV_MEDIA_MAX_PHOTO ){ throw new AppstvServiceRuleException( "This photo is too large (at most 30 MB)." ); }
    if( $kind === 'video' && $size > APPSTV_MEDIA_MAX_VIDEO ){ throw new AppstvServiceRuleException( "This video is too large (at most 500 MB)." ); }
    $count = (int) mysqli_fetch_row( appstv_case_db( "SELECT COUNT(*) FROM service_case_media m WHERE m.deleted_on = '' AND ( $same )" ) )[ 0 ];
    if( $count >= APPSTV_MEDIA_MAX_FILES ){ throw new AppstvServiceRuleException( "A case can hold up to " . APPSTV_MEDIA_MAX_FILES . " photos and videos." ); }

    $e_user = escape_string( appstv_case_user_id() );
    $now = create_iso_8601_datetime();
    appstv_case_db( "INSERT INTO service_case_media ( case_id, draft_id, kind, original_name, mime, source_bytes, state, uploaded_by, uploaded_on )
                     VALUES ( " . ( $case_id === null ? "NULL" : $case_id ) . ", " . ( $draft_id === null ? "NULL" : $draft_id ) . ", '$kind',
                              '" . escape_string( $name ) . "', '" . escape_string( $mime ) . "', $size, 'uploading', '$e_user', '$now' )" );
    global $con;
    $row = appstv_media_read( (int) mysqli_insert_id( $con ) );
    @file_put_contents( appstv_media_dir( 'tmp' ) . (int) $row[ 'id' ] . '.part', '' );
    return array( "Upload started.", array( 'media_id' => (int) $row[ 'id' ], 'key' => appstv_media_key( $row ),
                                            'chunk_size' => APPSTV_MEDIA_CHUNK, 'chunks' => (int) ceil( $size / APPSTV_MEDIA_CHUNK ) ) );
}

/**
 * @description Step part: writes one piece at its place (no transaction: nothing changes in the database)
 * @returns {void} Echoes the JSON answer
 */
function appstv_media_part(){
    $for = 'appstv_case_media_upload';
    connect();
    try {
        $row = appstv_media_read( request( 'media_id' ) );
        if( !hash_equals( appstv_media_key( $row ), (string) request( 'key' ) ) || $row[ 'state' ] !== 'uploading' ){
            throw new AppstvServiceRuleException( "This upload is not open any more. Add the file again." );
        }
        $index = (int) request( 'index' );
        $chunks = (int) ceil( (int) $row[ 'source_bytes' ] / APPSTV_MEDIA_CHUNK );
        if( $index < 0 || $index >= $chunks ){ throw new AppstvServiceRuleException( "Wrong piece number." ); }
        if( !isset( $_FILES[ 'chunk' ] ) || $_FILES[ 'chunk' ][ 'error' ] !== UPLOAD_ERR_OK || !is_uploaded_file( $_FILES[ 'chunk' ][ 'tmp_name' ] ) ){
            throw new AppstvServiceRuleException( "A piece of the file did not arrive. It is sent again." );
        }
        $expected = ( $index === $chunks - 1 ) ? (int) $row[ 'source_bytes' ] - $index * APPSTV_MEDIA_CHUNK : APPSTV_MEDIA_CHUNK;
        if( (int) $_FILES[ 'chunk' ][ 'size' ] !== $expected ){ throw new AppstvServiceRuleException( "A piece of the file has the wrong size." ); }
        $part = appstv_media_dir( 'tmp' ) . (int) $row[ 'id' ] . '.part';
        $out = @fopen( $part, 'c' );
        $in = @fopen( $_FILES[ 'chunk' ][ 'tmp_name' ], 'rb' );
        if( !$out || !$in ){ throw new Exception( "Cannot write the piece" ); }
        flock( $out, LOCK_EX );
        fseek( $out, $index * APPSTV_MEDIA_CHUNK );
        stream_copy_to_stream( $in, $out );
        flock( $out, LOCK_UN );
        fclose( $out ); fclose( $in );
    } catch( AppstvServiceRuleException $e ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, $for, array( 'message' => $e->getMessage() ) );
        return;
    } catch( Throwable $e ){
        error_log( $for . ' part: ' . $e->getMessage() );
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, $for, array( 'message' => "The server could not save a piece. It is sent again." ) );
        return;
    }
    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, $for, array( 'message' => "Piece saved.", 'data' => array( 'index' => $index ) ) );
}

/**
 * @description Step finish: checks the whole file; photo -> ready (+ thumbnail), video -> queued for conversion
 * @returns {array} [ message, data ]
 */
function appstv_media_finish(){
    $row = appstv_media_read( request( 'media_id' ), true );
    if( !hash_equals( appstv_media_key( $row ), (string) request( 'key' ) ) ){ throw new AppstvServiceRuleException( "This upload is not yours." ); }
    if( $row[ 'state' ] !== 'uploading' ){ return array( "Already received.", array( 'media' => appstv_media_list( "m.id = " . (int) $row[ 'id' ] ) ) ); }
    $id = (int) $row[ 'id' ];
    $part = appstv_media_dir( 'tmp' ) . $id . '.part';
    clearstatcache( true, $part );
    if( !is_file( $part ) || filesize( $part ) !== (int) $row[ 'source_bytes' ] ){
        throw new AppstvServiceRuleException( "Not all of the file arrived. Add it again." );
    }
    $mime = ( new finfo( FILEINFO_MIME_TYPE ) )->file( $part );
    $folder = date( 'Y' ) . '/' . date( 'm' );
    appstv_media_dir( $folder );
    $base = $folder . '/' . $id . '_' . bin2hex( random_bytes( 6 ) );
    $now = create_iso_8601_datetime();

    if( $row[ 'kind' ] === 'photo' ){
        if( strpos( (string) $mime, 'image/' ) !== 0 ){ @unlink( $part ); throw new AppstvServiceRuleException( "This file is not a photo." ); }
        $size = appstv_media_jpeg( $part, appstv_media_dir() . $base . '.jpg', APPSTV_MEDIA_PHOTO_PX, 82 );
        if( $size === null ){
            @unlink( $part );
            throw new AppstvServiceRuleException( "This photo format cannot be read (for example HEIC). Share it as JPEG, or take a screenshot of it." );
        }
        appstv_media_jpeg( appstv_media_dir() . $base . '.jpg', appstv_media_dir() . $base . '_t.jpg', APPSTV_MEDIA_THUMB_PX, 75 );
        @unlink( $part );
        $bytes = (int) filesize( appstv_media_dir() . $base . '.jpg' );
        appstv_case_db( "UPDATE service_case_media SET state = 'ready', file_name = '$base.jpg', thumb_file = '{$base}_t.jpg', mime = 'image/jpeg',
                                size_bytes = $bytes, width = {$size[0]}, height = {$size[1]}, processed_on = '$now' WHERE id = $id" );
    } else {
        if( strpos( (string) $mime, 'video/' ) !== 0 && $mime !== 'application/octet-stream' ){
            @unlink( $part ); throw new AppstvServiceRuleException( "This file is not a video." );
        }
        $ext = strtolower( pathinfo( $row[ 'original_name' ], PATHINFO_EXTENSION ) );
        $ext = preg_match( '/^[a-z0-9]{2,5}$/', $ext ) ? $ext : 'video';
        if( !@rename( $part, appstv_media_dir() . $base . '_src.' . $ext ) ){ throw new Exception( "Cannot move the video" ); }
        appstv_case_db( "UPDATE service_case_media SET state = 'queued', source_file = '{$base}_src.$ext', mime = '" . escape_string( (string) $mime ) . "' WHERE id = $id" );
    }
    $label = ( $row[ 'kind' ] === 'photo' ? 'Photo' : 'Video' ) . ' added: ' . $row[ 'original_name' ];
    appstv_case_history( $row[ 'case_id' ] === null ? null : (int) $row[ 'case_id' ], $row[ 'case_id' ] === null ? (int) $row[ 'draft_id' ] : null, 'media_added', '', null, null, $label );
    return array( ( $row[ 'kind' ] === 'photo' ? "Photo" : "Video" ) . " received.", array( 'media' => appstv_media_list( "m.id = $id" ) ) );
}


/**
 * Removes a photo or video (row kept for the history, files deleted)
 */
function appstv_case_media_delete(){
    checkAuthorizationForFunction( __FUNCTION__ );
    appstv_case_transaction( __FUNCTION__, function(){
        $row = appstv_media_read( request( 'media_id' ), true );
        if( $row[ 'case_id' ] !== null ){
            $case = appstv_case_read( $row[ 'case_id' ] );
            if( $case[ 'status' ] === 'closed' ){ throw new AppstvServiceRuleException( "This case is closed. Reopen it to remove photos or videos." ); }
        }
        if( $row[ 'state' ] === 'processing' ){ throw new AppstvServiceRuleException( "This video is being converted right now. Try again in a minute." ); }
        $e_user = escape_string( appstv_case_user_id() );
        $now = create_iso_8601_datetime();
        appstv_case_db( "UPDATE service_case_media SET deleted_by = '$e_user', deleted_on = '$now' WHERE id = " . (int) $row[ 'id' ] );
        appstv_media_unlink( $row );
        if( $row[ 'state' ] === 'uploading' ){
            return array( "Upload cancelled.", array( 'media_id' => (int) $row[ 'id' ] ) );   // Never arrived: nothing for the history
        }
        $label = ( $row[ 'kind' ] === 'photo' ? 'Photo' : 'Video' ) . ' removed: ' . $row[ 'original_name' ];
        appstv_case_history( $row[ 'case_id' ] === null ? null : (int) $row[ 'case_id' ], $row[ 'case_id' ] === null ? (int) $row[ 'draft_id' ] : null, 'media_removed', '', null, null, $label );
        return array( ( $row[ 'kind' ] === 'photo' ? "Photo" : "Video" ) . " removed.", array( 'media_id' => (int) $row[ 'id' ] ) );
    } );
}


/**
 * Sends a photo / video / thumbnail (variant = file | thumb | source). Range requests are answered so a video can
 * be scrolled. Answers 404 text when it is not there.
 */
function appstv_case_media_file(){
    checkAuthorizationForFunction( __FUNCTION__ );
    connect();
    $row = mysqli_fetch_assoc( appstv_case_db( "SELECT * FROM service_case_media WHERE id = " . (int) request( 'media_id' ) ) );
    $variant = (string) request( 'variant' );
    $col = array( 'thumb' => 'thumb_file', 'source' => 'source_file' )[ $variant ] ?? 'file_name';
    $rel = ( $row !== null && $row[ 'deleted_on' ] === '' ) ? (string) $row[ $col ] : '';
    // A video still being converted: its original is shown
    if( $rel === '' && $row !== null && $row[ 'deleted_on' ] === '' && $col === 'file_name' && $row[ 'kind' ] === 'video' ){ $rel = (string) $row[ 'source_file' ]; }
    $path = ( $rel !== '' && strpos( $rel, '..' ) === false ) ? appstv_media_dir() . $rel : '';
    while( ob_get_level() > 0 ){ ob_end_clean(); }
    if( $path === '' || !is_file( $path ) ){
        http_response_code( 404 );
        header( 'Content-Type: text/plain' );
        echo "Not found";
        return;
    }
    $size = filesize( $path );
    $type = ( $col === 'thumb_file' || ( $row[ 'kind' ] === 'photo' ) ) ? 'image/jpeg'
          : ( preg_match( '/\.mp4$/i', $path ) ? 'video/mp4' : ( $row[ 'mime' ] !== '' ? $row[ 'mime' ] : 'application/octet-stream' ) );
    $start = 0; $end = $size - 1;
    if( isset( $_SERVER[ 'HTTP_RANGE' ] ) && preg_match( '/bytes=(\d*)-(\d*)/', $_SERVER[ 'HTTP_RANGE' ], $m ) ){
        if( $m[ 1 ] === '' ){ $start = max( 0, $size - (int) $m[ 2 ] ); }
        else { $start = (int) $m[ 1 ]; if( $m[ 2 ] !== '' ){ $end = min( $end, (int) $m[ 2 ] ); } }
        if( $start > $end || $start >= $size ){ http_response_code( 416 ); header( "Content-Range: bytes */$size" ); return; }
        http_response_code( 206 );
        header( "Content-Range: bytes $start-$end/$size" );
    }
    header( 'Content-Type: ' . $type );
    header( 'Accept-Ranges: bytes' );
    header( 'Content-Length: ' . ( $end - $start + 1 ) );
    header( 'Cache-Control: private, max-age=86400' );
    header( 'X-Content-Type-Options: nosniff' );
    if( request( 'download' ) === '1' ){
        header( 'Content-Disposition: attachment; filename="' . preg_replace( '/[^A-Za-z0-9._ -]/', '_', $row[ 'original_name' ] ?: 'file' ) . '"' );
    }
    $fp = fopen( $path, 'rb' );
    fseek( $fp, $start );
    $left = $end - $start + 1;
    while( $left > 0 && !feof( $fp ) ){
        $buf = fread( $fp, (int) min( 1048576, $left ) );
        echo $buf;
        $left -= strlen( $buf );
        flush();
    }
    fclose( $fp );
}
