<?php
require_once 'load-all.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Undo Import</title>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
    .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    h1 { color: #333; border-bottom: 2px solid #f44336; padding-bottom: 10px; }
    .success { color: #4CAF50; padding: 5px 0; }
    .error { color: #f44336; padding: 5px 0; font-weight: bold; }
    .warning { color: #FF9800; padding: 5px 0; }
    .info { color: #2196F3; padding: 5px 0; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 3px; }
</style>";
echo "</head><body><div class='container'>";

echo "<h1>🔄 Undo Import - Delete Imported Records</h1>";

// ⚠️ Set this to the log file you want to undo
$log_file = __DIR__ . '/import-log-YYYYMMDD-HHMMSS.json';

// Check if log file exists
if (!file_exists($log_file)) {
    echo "<p class='error'>❌ Log file not found: <strong>$log_file</strong></p>";
    echo "<p class='info'>📝 Please update the <code>\$log_file</code> variable in this script to point to your actual import log file.</p>";
    echo "<p class='info'>💡 Log files are named like: <code>import-log-20250116-143022.json</code></p>";
    
    // List available log files
    $log_files = glob(__DIR__ . '/import-log-*.json');
    if (!empty($log_files)) {
        echo "<p class='info'>📂 Available log files found:</p>";
        echo "<ul>";
        foreach ($log_files as $file) {
            echo "<li>" . basename($file) . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p class='warning'>⚠️ No import log files found in current directory.</p>";
    }
    
    echo "</div></body></html>";
    exit;
}

// Load the import log
$import_log = json_decode(file_get_contents($log_file), true);

if ($import_log === null) {
    echo "<p class='error'>❌ Failed to parse log file. Invalid JSON format.</p>";
    echo "</div></body></html>";
    exit;
}

echo "<p class='info'>📄 Using log file: <strong>" . basename($log_file) . "</strong></p>";
echo "<p class='warning'>⚠️ <strong>WARNING:</strong> This will permanently delete all records created during this import!</p>";
echo "<hr>";

// Map table names to their ID field names
$field_map = [
    'customers'                     => 'customer_id',
    'order_invoices'                => 'invoice_id',
    'product_sn'                    => 'product_sn_id',
    'invoice_product_sn'            => 'ipsn_id',
    'product_sn_warranty'           => 'psnw_id',
    'product_warranty_replacements' => 'pwrp_id'
];

// Deletion order (reverse of creation to avoid foreign key issues)
$deletion_order = [
    'product_warranty_replacements',
    'product_sn_warranty',
    'invoice_product_sn',
    'product_sn',
    'order_invoices',
    'customers'
];

$stats = [
    'total_deleted' => 0,
    'tables_processed' => 0
];

echo "<h2>🗑️ Deletion Progress</h2>";

try {
    foreach ($deletion_order as $table) {
        $id_field = $field_map[$table];
        $ids = $import_log[$table] ?? [];
        
        if (empty($ids)) {
            echo "<p class='info'>⏭️ Skipped (no records): <strong>$table</strong></p>";
            continue;
        }
        
        // Remove duplicates
        $ids = array_unique($ids);
        $count = count($ids);
        
        // Escape IDs for SQL safety
        $escaped_ids = array_map(function($id) {
            return escape_string($id);
        }, $ids);
        
        $id_list = implode("','", $escaped_ids);
        $sql = "DELETE FROM `$table` WHERE `$id_field` IN ('$id_list')";
        
        $result = deleteQuery($sql);
        
        if ($result) {
            $deleted = mysqli_affected_rows($GLOBALS['con']);
            $stats['total_deleted'] += $deleted;
            $stats['tables_processed']++;
            echo "<p class='success'>✅ Deleted <strong>$deleted</strong> record(s) from: <strong>$table</strong></p>";
            
            if ($deleted !== $count) {
                echo "<p class='warning'>⚠️ Expected to delete $count records but deleted $deleted (some may have been already deleted or not found)</p>";
            }
        } else {
            echo "<p class='error'>❌ Failed to delete from: <strong>$table</strong></p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ <strong>Error:</strong> " . $e->getMessage() . "</p>";
}

// Display Summary
echo "<hr>";
echo "<h2>📊 Deletion Summary</h2>";
echo "<p><strong>Tables Processed:</strong> {$stats['tables_processed']}</p>";
echo "<p><strong>Total Records Deleted:</strong> {$stats['total_deleted']}</p>";

// Show breakdown by table
echo "<h3>Breakdown by Table:</h3>";
echo "<ul>";
foreach ($deletion_order as $table) {
    $ids = $import_log[$table] ?? [];
    $count = count(array_unique($ids));
    if ($count > 0) {
        echo "<li><strong>$table:</strong> $count record(s)</li>";
    }
}
echo "</ul>";

echo "<hr>";
echo "<p class='success'>✅ <strong>Undo completed!</strong></p>";
echo "<p class='info'>💡 The log file has been kept at: <code>" . basename($log_file) . "</code></p>";
echo "<p class='info'>You can delete it manually if you no longer need it.</p>";

echo "</div></body></html>";

?>