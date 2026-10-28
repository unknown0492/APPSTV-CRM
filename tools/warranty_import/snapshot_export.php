<?php
// Command line only: on the web server this file must never answer a browser (it would expose customer data)
if( PHP_SAPI !== 'cli' ){ http_response_code( 404 ); exit; }
/**
 * Exports what the warranty-import generator needs, as JSON, from a database (default: the side copy).
 * Used by tools/warranty_import/warranty_import.py (appstv_crm v1.0.5).
 * Usage: php snapshot_export.php [database] > snapshot.json   (read-only: SELECTs only; default database = the side copy)
 */
require dirname( __DIR__, 2 ) . '/configurations/config.php';
$db  = isset( $argv[1] ) ? $argv[1] : 'appstv_crm_livecopy';     // Database to read
$con = mysqli_connect( $host, $database_username, $database_password, $db ) or die( "connect failed\n" );
mysqli_set_charset( $con, 'utf8mb4' );

/**
 * @description Runs a SELECT and returns all rows
 * @param {string} $sql - Query
 * @returns {array}
 */
function rows( $sql ){ global $con; $r = mysqli_query( $con, $sql ) or die( mysqli_error( $con ) ); return mysqli_fetch_all( $r, MYSQLI_ASSOC ); }

$out = array(
    'database'  => $db,
    'customers' => rows( "SELECT customer_id, company_name FROM customers" ),
    'invoices'  => rows( "SELECT invoice_id, invoice_no, invoice_date, customer_id FROM order_invoices" ),
    'products'  => rows( "SELECT product_id, sku, name, has_sn FROM products" ),
    'product_sn'=> rows( "SELECT ps.product_sn_id, ps.product_id, ps.serial_number, ps.allotted_to_customer,
                                 EXISTS(SELECT 1 FROM invoice_product_sn i WHERE i.product_sn_id=ps.product_sn_id) AS invoiced,
                                 EXISTS(SELECT 1 FROM order_product_sn o WHERE o.product_sn_id=ps.product_sn_id) AS in_order,
                                 EXISTS(SELECT 1 FROM product_warranty_replacements r WHERE r.new_product_sn_id=ps.product_sn_id) AS is_replacement
                          FROM product_sn ps" ),
    'ids'       => array()
);
foreach( array( 'customers' => 'customer_id', 'products' => 'product_id', 'product_sn' => 'product_sn_id', 'order_invoices' => 'invoice_id',
                'invoice_product_sn' => 'ipsn_id', 'product_sn_warranty' => 'psnw_id', 'product_sn_extended_warranty' => 'psnexw_id',
                'product_warranty_replacements' => 'pwrp_id' ) as $t => $c ){
    $out['ids'][ $t ] = array_column( rows( "SELECT $c FROM $t" ), $c );
}
echo json_encode( $out );
