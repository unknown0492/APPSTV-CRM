<?php

/**
 * Excel Import Script for Warranty Data
 * 
 * This script imports warranty and invoice data from an Excel file into the database
 * File should be placed in the same directory as webservice.php
 * 
 * Usage: Run this file directly in the browser
 */

// Include all necessary files
require_once 'load-all.php';

// Include SimpleXLSX library for Excel reading
require_once LIB_PATH . '/Classes/SimpleXLSX.php';

// Include all necessary class files
//require_once PLU_PATH . '/appstv_crm_customer/includes/customer.php';
require_once PLU_PATH . '/appstv_crm_order/includes/order_invoice.php';
require_once PLU_PATH . '/appstv_crm_order/includes/invoice_product_sn.php';
require_once PLU_PATH . '/appstv_crm_products/includes/product_sn_warranty.php';
require_once PLU_PATH . '/appstv_crm_products/includes/product_warranty_replacement.php';

// Set execution time to unlimited for large files
set_time_limit(0);
ini_set('memory_limit', '512M');

// Output buffer to show progress in real-time
ob_implicit_flush(true);
ob_end_flush();

echo "<!DOCTYPE html>";
echo "<html><head><title>Excel Import Progress</title>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
    .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    h1 { color: #333; border-bottom: 2px solid #4CAF50; padding-bottom: 10px; }
    .success { color: #4CAF50; padding: 5px 0; }
    .error { color: #f44336; padding: 5px 0; font-weight: bold; }
    .info { color: #2196F3; padding: 5px 0; }
    .warning { color: #FF9800; padding: 5px 0; }
    .row-header { background: #e8f5e9; padding: 10px; margin: 10px 0; border-left: 4px solid #4CAF50; font-weight: bold; }
    .summary { background: #e3f2fd; padding: 15px; margin: 20px 0; border-radius: 5px; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 3px; overflow-x: auto; }
</style>";
echo "</head><body><div class='container'>";

echo "<h1>📊 Excel Import - Warranty Data</h1>";

// Configuration
$excelFilePath = DAT_PATH . '/warranty-record.xlsx';  // Place your Excel file in the same directory

// Check if file exists
if (!file_exists($excelFilePath)) {
    echo "<p class='error'>❌ Error: Excel file '$excelFilePath' not found!</p>";
    echo "<p class='info'>Please place your Excel file in the same directory as this script and name it 'warranty-data.xlsx'</p>";
    echo "</div></body></html>";
    exit;
}

echo "<p class='info'>📁 Reading Excel file: <strong>$excelFilePath</strong></p>";
echo "<p class='info'>⏰ Started at: <strong>" . date('Y-m-d H:i:s') . "</strong></p>";
echo "<hr>";

// Statistics
$stats = [
    'total_rows' => 0,
    'successful_rows' => 0,
    'failed_rows' => 0,
    'customers_created' => 0,
    'invoices_created' => 0,
    'product_sn_created' => 0,
    'warranties_created' => 0,
    'replacements_created' => 0
];

try {
    // Load Excel file using SimpleXLSX
    if ($xlsx = SimpleXLSX::parse($excelFilePath)) {
        $rows = $xlsx->rows();
        $totalRows = count($rows);
        
        echo "<p class='success'>✅ Excel file loaded successfully!</p>";
        echo "<p class='info'>📋 Total rows in Excel: <strong>" . ($totalRows - 1) . "</strong> (excluding header)</p>";
        echo "<hr>";
        
        // Process each row (starting from index 1, skipping header at index 0)
        for ($i = 1; $i < $totalRows; $i++) {
            $row = $rows[$i];
            $rowNumber = $i + 1; // Excel row number (header is row 1)
            $stats['total_rows']++;
            $rowSuccess = true;
            $rowErrors = [];
            
            echo "<div class='row-header'>🔄 Processing Row #$rowNumber</div>";
            
            try {
                // Read all cell values and trim whitespace
                $companyName = isset($row[0]) ? trim($row[0]) : '';
                $invoiceNo = isset($row[1]) ? trim($row[1]) : '';
                $invoiceDate = isset($row[2]) ? trim($row[2]) : '';
                $productID = isset($row[3]) ? trim($row[3]) : '';
                $dateOfPurchase = isset($row[4]) ? trim($row[4]) : '';
                // Skip columns 5-7 (Warranty Expired, Extended Warranty columns - all NIL)
                $serialNo = isset($row[8]) ? trim($row[8]) : '';
                $dateOfService = isset($row[9]) ? trim($row[9]) : '';
                $newSerialNo = isset($row[10]) ? trim($row[10]) : '';
                $remark = isset($row[11]) ? trim($row[11]) : '';
                $refNo = isset($row[12]) ? trim($row[12]) : '';
                
                echo "<p class='info'>📝 Company: <strong>$companyName</strong> | Invoice: <strong>$invoiceNo</strong> | Serial: <strong>$serialNo</strong></p>";
                
                // =================================================================
                // STEP 1: Process Company Name / Location → Customer
                // =================================================================
                $customerID = null;
                
                if (empty($companyName)) {
                    $companyName = "Name Forgotten " . rand(1000, 9999);
                    echo "<p class='warning'>⚠️ Company name was empty, using: $companyName</p>";
                }
                
                // Check if customer exists
                $e_company_name = escape_string($companyName);
                $sql = "SELECT customer_id FROM customers WHERE company_name='$e_company_name' LIMIT 1";
                $result = selectQuery($sql);
                
                if (mysqli_num_rows($result) > 0) {
                    $val = mysqli_fetch_assoc($result);
                    $customerID = $val['customer_id'];
                    echo "<p class='success'>✅ Customer found: ID = $customerID</p>";
                } else {
                    // Create new customer
                    $customer = new Customer();
                    $customer->setCustomerID(Customer::generateCustomerID());
                    $customer->setCustomerSource('20365841');
                    $customer->setCompanyName($companyName);
                    $customer->setCreatedAt(date('Y-m-d H:i:s'));
                    
                    $createdCustomer = $customer->createCustomer();
                    if ($createdCustomer !== null) {
                        $customerID = $createdCustomer->getCustomerID();
                        $stats['customers_created']++;
                        echo "<p class='success'>✅ New customer created: ID = $customerID</p>";
                    } else {
                        $rowErrors[] = "Failed to create customer";
                        $rowSuccess = false;
                    }
                }
                
                // =================================================================
                // STEP 2: Process Invoice No. + Invoice Date → Order Invoice
                // =================================================================
                $invoiceID = null;
                
                if (!empty($invoiceNo)) {
                    $e_invoice_no = escape_string($invoiceNo);
                    $sql = "SELECT invoice_id FROM order_invoices WHERE invoice_no='$e_invoice_no' LIMIT 1";
                    $result = selectQuery($sql);
                    
                    if (mysqli_num_rows($result) > 0) {
                        $val = mysqli_fetch_assoc($result);
                        $invoiceID = $val['invoice_id'];
                        echo "<p class='success'>✅ Invoice found: ID = $invoiceID</p>";
                    } else {
                        // Create new invoice
                        $invoice = new OrderInvoice();
                        $invoice->setInvoiceNo($invoiceNo);
                        $invoice->setInvoiceDate($invoiceDate);
                        
                        $createdInvoice = $invoice->createInvoice();
                        if ($createdInvoice !== null) {
                            $invoiceID = $createdInvoice->getInvoiceID();
                            $stats['invoices_created']++;
                            echo "<p class='success'>✅ New invoice created: ID = $invoiceID, No = $invoiceNo, Date = $invoiceDate</p>";
                        } else {
                            $rowErrors[] = "Failed to create invoice";
                            $rowSuccess = false;
                        }
                    }
                }
                
                // =================================================================
                // STEP 3: Read TV Model (Product ID)
                // =================================================================
                if (empty($productID)) {
                    $rowErrors[] = "Product ID is empty";
                    $rowSuccess = false;
                } else {
                    echo "<p class='info'>📦 Product ID: $productID</p>";
                }
                
                // =================================================================
                // STEP 4: Process Serial Number → Product SN + Link to Invoice
                // =================================================================
                $productSNID = null;
                
                if (!empty($serialNo) && $rowSuccess) {
                    $e_serial_no = escape_string($serialNo);
                    $sql = "SELECT product_sn_id FROM product_sn WHERE serial_number='$e_serial_no' LIMIT 1";
                    $result = selectQuery($sql);
                    
                    if (mysqli_num_rows($result) > 0) {
                        $val = mysqli_fetch_assoc($result);
                        $productSNID = $val['product_sn_id'];
                        echo "<p class='success'>✅ Product SN found: ID = $productSNID</p>";
                    } else {
                        // Create new product_sn entry
                        $generatedProductSNID = generateUniqueID("psn");
                        $e_product_id = escape_string($productID);
                        $currentTimestamp = date('Y-m-d H:i:s');
                        
                        $sql = "INSERT INTO product_sn (product_sn_id, product_id, serial_number, created_on, allotted_to_customer) "
                             . "VALUES ('$generatedProductSNID', '$e_product_id', '$e_serial_no', '$currentTimestamp', 1)";
                        
                        $rows = insertQuery($sql);
                        if ($rows > 0) {
                            $productSNID = $generatedProductSNID;
                            $stats['product_sn_created']++;
                            echo "<p class='success'>✅ New Product SN created: ID = $productSNID</p>";
                        } else {
                            $rowErrors[] = "Failed to create product SN";
                            $rowSuccess = false;
                        }
                    }
                    
                    // Link Invoice to Product SN via invoice_product_sn table
                    if ($rowSuccess && !empty($invoiceID) && !empty($productSNID)) {
                        $ipsnObj = new InvoiceProductSN();
                        $ipsnObj->setInvoiceID($invoiceID);
                        $ipsnObj->setProductSnID($productSNID);
                        
                        $createdIPSN = $ipsnObj->createInvoiceProductSN();
                        if ($createdIPSN !== null) {
                            echo "<p class='success'>✅ Invoice linked to Product SN</p>";
                        } else {
                            echo "<p class='warning'>⚠️ Failed to link invoice to product SN (may already exist)</p>";
                        }
                    }
                }
                
                // =================================================================
                // STEP 5: Process Date of Purchase → Product SN Warranty
                // =================================================================
                $psnwID = null;
                
                if (!empty($dateOfPurchase) && $rowSuccess && !empty($productSNID)) {
                    // Calculate warranty period (3 years)
                    $warrantyPeriodDays = calculateWarrantyPeriodDays($dateOfPurchase);
                    
                    $warranty = new ProductSNWarranty();
                    $warranty->setProductSnID($productSNID);
                    $warranty->setWarrantyStartDate($dateOfPurchase);
                    $warranty->setWarrantyPeriod($warrantyPeriodDays);
                    $warranty->setRemarks('Values Loading from Excel Sheet');
                    
                    $createdWarranty = $warranty->createWarranty();
                    if ($createdWarranty !== null) {
                        $psnwID = $createdWarranty->getPsnwID();
                        $stats['warranties_created']++;
                        echo "<p class='success'>✅ Warranty created: ID = $psnwID, Start Date = $dateOfPurchase, Period = $warrantyPeriodDays days</p>";
                    } else {
                        $rowErrors[] = "Failed to create warranty";
                        $rowSuccess = false;
                    }
                }
                
                // =================================================================
                // STEP 6: Process Date of Service → Warranty Replacement (Conditional)
                // =================================================================
                if (!empty($dateOfService) && $rowSuccess && !empty($psnwID)) {
                    echo "<p class='info'>🔧 Processing service/replacement data...</p>";
                    
                    $newProductSNID = null;
                    $newInvoiceID = null;
                    
                    // Process New Serial Number
                    if (!empty($newSerialNo)) {
                        $e_new_serial_no = escape_string($newSerialNo);
                        $sql = "SELECT product_sn_id FROM product_sn WHERE serial_number='$e_new_serial_no' LIMIT 1";
                        $result = selectQuery($sql);
                        
                        if (mysqli_num_rows($result) > 0) {
                            $val = mysqli_fetch_assoc($result);
                            $newProductSNID = $val['product_sn_id'];
                            echo "<p class='success'>✅ New Product SN found: ID = $newProductSNID</p>";
                        } else {
                            // Create new product_sn for replacement
                            $generatedNewProductSNID = generateUniqueID("psn");
                            $e_product_id = escape_string($productID);
                            $currentTimestamp = date('Y-m-d H:i:s');
                            
                            $sql = "INSERT INTO product_sn (product_sn_id, product_id, serial_number, created_on, allotted_to_customer) "
                                 . "VALUES ('$generatedNewProductSNID', '$e_product_id', '$e_new_serial_no', '$currentTimestamp', 1)";
                            
                            $rows = insertQuery($sql);
                            if ($rows > 0) {
                                $newProductSNID = $generatedNewProductSNID;
                                $stats['product_sn_created']++;
                                echo "<p class='success'>✅ New Product SN created for replacement: ID = $newProductSNID</p>";
                            } else {
                                $rowErrors[] = "Failed to create new product SN for replacement";
                                $rowSuccess = false;
                            }
                        }
                    }
                    
                    // Process Ref No. (New Invoice)
                    if ($rowSuccess && !empty($refNo) && $refNo !== 'NA') {
                        $e_ref_no = escape_string($refNo);
                        $sql = "SELECT invoice_id FROM order_invoices WHERE invoice_no='$e_ref_no' LIMIT 1";
                        $result = selectQuery($sql);
                        
                        if (mysqli_num_rows($result) > 0) {
                            $val = mysqli_fetch_assoc($result);
                            $newInvoiceID = $val['invoice_id'];
                            echo "<p class='success'>✅ Replacement invoice found: ID = $newInvoiceID</p>";
                        } else {
                            // Create new invoice for replacement
                            $newInvoice = new OrderInvoice();
                            $newInvoice->setInvoiceNo($refNo);
                            $newInvoice->setInvoiceDate($dateOfService);
                            
                            $createdNewInvoice = $newInvoice->createInvoice();
                            if ($createdNewInvoice !== null) {
                                $newInvoiceID = $createdNewInvoice->getInvoiceID();
                                $stats['invoices_created']++;
                                echo "<p class='success'>✅ New replacement invoice created: ID = $newInvoiceID</p>";
                            } else {
                                $rowErrors[] = "Failed to create replacement invoice";
                                $rowSuccess = false;
                            }
                        }
                    }
                    
                    // Create Warranty Replacement Record
                    if ($rowSuccess) {
                        $replacement = new ProductWarrantyReplacement();
                        $replacement->setIsRepairable(0);
                        $replacement->setPsnwID($psnwID);
                        $replacement->setDateOfService($dateOfService);
                        $replacement->setNewInvoiceID($newInvoiceID);  // Can be NULL
                        $replacement->setNewProductSnID($newProductSNID);
                        $replacement->setRemarks($remark);
                        
                        $createdReplacement = $replacement->createReplacement();
                        if ($createdReplacement !== null) {
                            $stats['replacements_created']++;
                            echo "<p class='success'>✅ Warranty replacement record created</p>";
                        } else {
                            $rowErrors[] = "Failed to create warranty replacement";
                            $rowSuccess = false;
                        }
                    }
                }
                
                // =================================================================
                // Row Summary
                // =================================================================
                if ($rowSuccess) {
                    $stats['successful_rows']++;
                    echo "<p class='success'>✅ <strong>Row #$rowNumber processed successfully!</strong></p>";
                } else {
                    $stats['failed_rows']++;
                    echo "<p class='error'>❌ <strong>Row #$rowNumber failed with errors:</strong></p>";
                    echo "<ul>";
                    foreach ($rowErrors as $error) {
                        echo "<li class='error'>$error</li>";
                    }
                    echo "</ul>";
                }
                
            } catch (Exception $e) {
                $stats['failed_rows']++;
                echo "<p class='error'>❌ <strong>Exception in Row #$rowNumber:</strong> " . $e->getMessage() . "</p>";
            }
            
            echo "<hr>";
            flush();
        }
        
    } else {
        echo "<p class='error'>❌ <strong>Error parsing Excel file:</strong> " . SimpleXLSX::parseError() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ <strong>Fatal Error:</strong> " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

// Display Summary
echo "<div class='summary'>";
echo "<h2>📊 Import Summary</h2>";
echo "<p><strong>Total Rows Processed:</strong> {$stats['total_rows']}</p>";
echo "<p class='success'><strong>✅ Successful:</strong> {$stats['successful_rows']}</p>";
echo "<p class='error'><strong>❌ Failed:</strong> {$stats['failed_rows']}</p>";
echo "<hr>";
echo "<p><strong>📝 Customers Created:</strong> {$stats['customers_created']}</p>";
echo "<p><strong>📄 Invoices Created:</strong> {$stats['invoices_created']}</p>";
echo "<p><strong>🔢 Product SNs Created:</strong> {$stats['product_sn_created']}</p>";
echo "<p><strong>🛡️ Warranties Created:</strong> {$stats['warranties_created']}</p>";
echo "<p><strong>🔧 Replacements Created:</strong> {$stats['replacements_created']}</p>";
echo "<hr>";
echo "<p>⏰ <strong>Completed at:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "</div>";

echo "</div></body></html>";

/**
 * Calculate warranty period in days (3 years from start date)
 * Accounts for leap years
 * 
 * @param string $startDate Date in dd-mm-YYYY format
 * @return int Number of days
 */
function calculateWarrantyPeriodDays($startDate) {
    // Parse the date (dd-mm-YYYY format)
    $parts = explode('-', $startDate);
    if (count($parts) !== 3) {
        return 1095; // Default to 3 years (365 * 3)
    }
    
    $day = $parts[0];
    $month = $parts[1];
    $year = $parts[2];
    
    // Create DateTime object
    $start = DateTime::createFromFormat('d-m-Y', $startDate);
    if (!$start) {
        return 1095; // Default if date parsing fails
    }
    
    // Add 3 years
    $end = clone $start;
    $end->modify('+3 years');
    
    // Calculate difference in days
    $interval = $start->diff($end);
    $days = $interval->days;
    
    return $days;
}



?>