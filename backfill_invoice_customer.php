<?php

/**
 * Backfill Script: Update order_invoices.customer_id
 * 
 * Reads the warranty Excel file row by row, matches company_name → customer_id
 * and invoice_no → invoice_id, then updates order_invoices.customer_id
 * 
 * Usage: Run this file directly in the browser
 * Place in the project root (same level as webservice.php)
 */

require_once 'load-all.php';
require_once LIB_PATH . '/Classes/SimpleXLSX.php';

use Shuchkin\SimpleXLSX;

set_time_limit( 0 );
ini_set( 'memory_limit', '512M' );
ob_implicit_flush( true );
ob_end_flush();

echo "<!DOCTYPE html>";
echo "<html><head><title>Backfill Invoice Customer IDs</title>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
    .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    h1 { color: #333; border-bottom: 2px solid #4CAF50; padding-bottom: 10px; }
    .success { color: #4CAF50; padding: 3px 0; }
    .error   { color: #f44336; padding: 3px 0; font-weight: bold; }
    .info    { color: #2196F3; padding: 3px 0; }
    .warning { color: #FF9800; padding: 3px 0; }
    .skipped { color: #9E9E9E; padding: 3px 0; }
    .summary { background: #e3f2fd; padding: 15px; margin: 20px 0; border-radius: 5px; }
    .row-header { background: #e8f5e9; padding: 8px; margin: 8px 0; border-left: 4px solid #4CAF50; font-weight: bold; }
</style>";
echo "</head><body><div class='container'>";
echo "<h1>🔁 Backfill: order_invoices.customer_id</h1>";

// ── Load Excel file ────────────────────────────────────────────────
$excelFilePath = DAT_PATH . '/warranty-record-done.xlsx';

if ( !file_exists( $excelFilePath ) ) {
    echo "<p class='error'>❌ Excel file not found at: $excelFilePath</p>";
    echo "</div></body></html>";
    exit;
}

echo "<p class='info'>📁 Reading: <strong>$excelFilePath</strong></p>";
echo "<p class='info'>⏰ Started at: <strong>" . date( 'Y-m-d H:i:s' ) . "</strong></p><hr/>";

$xlsx = SimpleXLSX::parse( $excelFilePath );
if ( !$xlsx ) {
    echo "<p class='error'>❌ Failed to parse Excel file: " . SimpleXLSX::parseError() . "</p>";
    echo "</div></body></html>";
    exit;
}

$rows = $xlsx->rows();

// ── Stats ──────────────────────────────────────────────────────────
$stats = [
    'rows_processed'  => 0,
    'updated'         => 0,
    'already_set'     => 0,
    'skipped_empty'   => 0,
    'customer_not_found' => 0,
    'invoice_not_found'  => 0,
    'warnings'        => 0
];

$rowIndex = 0;

foreach ( $rows as $row ) {

    $rowIndex++;

    // Skip header row
    if ( $rowIndex === 1 ) {
        echo "<p class='skipped'>⏭️ Skipping header row</p>";
        continue;
    }

    // ── Read relevant columns ──────────────────────────────────────
    $companyName = isset( $row[0] ) ? trim( (string) $row[0] ) : '';
    $invoiceNo   = isset( $row[1] ) ? trim( (string) $row[1] ) : '';

    // Skip rows with empty company name or invoice number
    if ( $companyName === '' || $invoiceNo === '' ) {
        echo "<p class='skipped'>⏭️ Row $rowIndex: Skipping — company name or invoice no is empty</p>";
        $stats['skipped_empty']++;
        continue;
    }

    $stats['rows_processed']++;

    echo "<div class='row-header'>Row $rowIndex — Company: <em>$companyName</em> | Invoice No: <em>$invoiceNo</em></div>";

    // ── 1. Find customer_id by company_name ────────────────────────
    $e_company_name = escape_string( $companyName );
    $sql_customer   = "SELECT customer_id FROM customers WHERE company_name = '$e_company_name' LIMIT 1";
    $result_customer = selectQuery( $sql_customer );

    if ( $result_customer === NULL || mysqli_num_rows( $result_customer ) === 0 ) {
        echo "<p class='warning'>⚠️ Customer not found for company: <strong>$companyName</strong> — skipping row</p>";
        $stats['customer_not_found']++;
        $stats['warnings']++;
        continue;
    }

    $customer    = mysqli_fetch_assoc( $result_customer );
    $customer_id = $customer['customer_id'];
    echo "<p class='info'>👤 Customer found: ID = <strong>$customer_id</strong></p>";

    // ── 2. Find invoice_id by invoice_no ───────────────────────────
    $e_invoice_no   = escape_string( $invoiceNo );
    $sql_invoice    = "SELECT invoice_id, customer_id FROM order_invoices WHERE invoice_no = '$e_invoice_no' LIMIT 1";
    $result_invoice = selectQuery( $sql_invoice );

    if ( $result_invoice === NULL || mysqli_num_rows( $result_invoice ) === 0 ) {
        echo "<p class='warning'>⚠️ Invoice not found for invoice_no: <strong>$invoiceNo</strong> — skipping row</p>";
        $stats['invoice_not_found']++;
        $stats['warnings']++;
        continue;
    }

    $invoice            = mysqli_fetch_assoc( $result_invoice );
    $invoice_id         = $invoice['invoice_id'];
    $existing_customer  = $invoice['customer_id'];

    // ── 3. Skip if customer_id already correctly set ───────────────
    if ( $existing_customer === $customer_id ) {
        echo "<p class='skipped'>✅ Invoice <strong>$invoice_id</strong> already has correct customer_id — skipping</p>";
        $stats['already_set']++;
        continue;
    }

    // ── 4. Update order_invoices.customer_id ──────────────────────
    $e_customer_id = escape_string( $customer_id );
    $e_invoice_id  = escape_string( $invoice_id );
    $sql_update    = "UPDATE order_invoices SET customer_id = '$e_customer_id' WHERE invoice_id = '$e_invoice_id'";
    $rows_affected = updateQuery( $sql_update );

    if ( $rows_affected > 0 ) {
        echo "<p class='success'>✅ Updated invoice <strong>$invoice_id</strong> (no: $invoiceNo) → customer_id = <strong>$customer_id</strong></p>";
        $stats['updated']++;
    } else {
        echo "<p class='warning'>⚠️ Update query ran but no rows affected for invoice <strong>$invoice_id</strong></p>";
        $stats['warnings']++;
    }
}

// ── Summary ────────────────────────────────────────────────────────
echo "<hr/>";
echo "<div class='summary'>";
echo "<h3>📊 Summary</h3>";
echo "<p>Rows processed: <strong>{$stats['rows_processed']}</strong></p>";
echo "<p>✅ Updated: <strong>{$stats['updated']}</strong></p>";
echo "<p>⏭️ Already correct: <strong>{$stats['already_set']}</strong></p>";
echo "<p>⏭️ Skipped (empty fields): <strong>{$stats['skipped_empty']}</strong></p>";
echo "<p>⚠️ Customer not found: <strong>{$stats['customer_not_found']}</strong></p>";
echo "<p>⚠️ Invoice not found: <strong>{$stats['invoice_not_found']}</strong></p>";
echo "<p>⚠️ Total warnings: <strong>{$stats['warnings']}</strong></p>";
echo "<p>⏰ Completed at: <strong>" . date( 'Y-m-d H:i:s' ) . "</strong></p>";
echo "</div>";

echo "</div></body></html>";
?>