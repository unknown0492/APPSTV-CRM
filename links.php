<?php

/**
 * This file will cover the actual URL and convert the fake URL into Actual URL
 * 
 */

require './load-all.php';

$code = @$_REQUEST[ 'code' ];

if( !isset( $code ) || ($code === "") ){
    // redirect to 404 not found page
    exit( 'Page not found !' );
}

// Escape $code so that we can safely use it in the DB
$e_code = escape_string( $code );

// Retrieve the correspoind URL for this code from private_links table
$sql = "SELECT * FROM private_links WHERE code='$code'";
//echo $sql;
$result_set = selectQuery( $sql );
if( $result_set === NULL ){
    exit( 'Page not found 1 !' );
}
if( mysqli_num_rows( $result_set ) == 0 ){
    exit( 'Page not found 2 !' );
}
    
$val = mysqli_fetch_object( $result_set );
$real_link = $val->real_link;

//echo $real_link;
if( $val->type == "file" ){
    $mime_type = ($val->mime_type === "auto")?mime_content_type( $real_link ):$val->mime_type;
    header( 'Content-Type: ' . $mime_type );       // https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Disposition
    $fileName = "file";
    if( $val->name !== "" ){
        $fileName = $val->name;
    }
    header( "Content-Disposition: attachment; filename=\"$fileName\"" );
}
readfile($real_link);

// Set the private link as used
setPrivateLinkAsUsed( $code );

?>