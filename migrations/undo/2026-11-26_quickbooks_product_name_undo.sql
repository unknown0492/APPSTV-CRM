-- UNDO of migrations/2026-11-26_quickbooks_product_name.sql (APPSTV CRM 1.0.42). Safe to re-run; MySQL 8.
--
-- Removes the sync_crm_products_quickbooks_names functionality with its grants, and the products.quickbooks_product_name
-- column (the synced names are lost; a later sync fills them again). Run it only together with a code rollback to 1.0.41:
-- 1.0.42 code reads the column.

DELETE rf FROM roles_functionalities rf
JOIN functionalities f ON f.functionality_id = rf.functionality_id
WHERE f.functionality_name = 'sync_crm_products_quickbooks_names';

DELETE FROM functionalities WHERE functionality_name = 'sync_crm_products_quickbooks_names';

SET @qpn_exists = ( SELECT COUNT(*) > 0 FROM information_schema.COLUMNS
                    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'quickbooks_product_name' );
SET @qpn_sql = IF( @qpn_exists, 'ALTER TABLE products DROP COLUMN quickbooks_product_name', 'DO 0' );
PREPARE qpn_stmt FROM @qpn_sql;
EXECUTE qpn_stmt;
DEALLOCATE PREPARE qpn_stmt;
