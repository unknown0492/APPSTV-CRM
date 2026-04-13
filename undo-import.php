<?php
require_once 'library/functions.php';

// ⚠️ Set this to the log file you want to undo
$log_file = __DIR__ . '/import-log-YYYYMMDD-HHMMSS.json';

if (!file_exists($log_file)) {
    die("<p>❌ Log file not found: $log_file</p>");
}

$import_log = json_decode(file_get_contents($log_file), true);

$field_map = [
    'customers'                     => 'customer_id',
    'order_invoices'                => 'invoice_id',
    'invoice_product_sn'            => 'ipsnid',
    'product_sn_warranty'           => 'psnw_id',
    'product_sn_extended_warranty'  => 'psnexw_id',
    'product_warranty_replacements' => 'psnr_id'
];

echo "<pre>";
foreach ($field_map as $table => $id_field) {
    $ids = $import_log[$table] ?? [];
    if (empty($ids)) {
        echo "⏭️  Skipped (no records): $table\n";
        continue;
    }

    $id_list = implode("','", $ids);
    $sql = "DELETE FROM `$table` WHERE `$id_field` IN ('$id_list')";
    selectQuery($sql);
    echo "✅ Deleted " . count($ids) . " record(s) from: $table\n";
}
echo "</pre>";
?>