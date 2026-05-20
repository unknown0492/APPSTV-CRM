<?php
require './load-all.php'; 


$zbar_bin  = BASE_PATH . DIRECTORY_SEPARATOR . "library" . DIRECTORY_SEPARATOR . "zbar/bin" . DIRECTORY_SEPARATOR . "zbarimg.exe";
//$test_image = BASE_PATH . DIRECTORY_SEPARATOR . "barbar.jpeg"; // place a test image here
$test_image = BASE_PATH . DIRECTORY_SEPARATOR . "barcode.png"; // place a test image here

echo "ZBar binary exists: " . ( file_exists( $zbar_bin ) ? "YES" : "NO" ) . "<br/>";
echo "ZBar path: $zbar_bin <br/>";
echo "Test image exists: " . ( file_exists( $test_image ) ? "YES" : "NO" ) . "<br/>";

$escaped_bin  = escapeshellarg( $zbar_bin );
$escaped_file = escapeshellarg( $test_image );
$command      = "$escaped_bin --raw -q $escaped_file 2>&1";

echo "Command: $command <br/>";

$output      = [];
$return_code = 0;
exec( $command, $output, $return_code );

echo "Return code: $return_code <br/>";
echo "Output: <pre>" . print_r( $output, true ) . "</pre>";
?>