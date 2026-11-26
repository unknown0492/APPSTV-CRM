-- QuickBooks name of each CRM product (APPSTV CRM 1.0.42)
--
-- The same product often has a different name in QuickBooks than in the CRM. The CRM now keeps the QuickBooks item's
-- name next to its own, and the Manage Products list shows it when the product name is hovered.
--
-- 1. New column products.quickbooks_product_name: the name of the linked QuickBooks item ('' = not linked or not
--    synced yet). It is filled when a product is linked (QuickBooks Item modal, or the automatic link by SKU at
--    invoice time), when its QuickBooks inventory is read, and by the new sync (row icon or "Sync names from
--    QuickBooks"). Products already linked get their name at the first sync.
-- 2. New functionality sync_crm_products_quickbooks_names (Products, user), granted by the template rule: every role
--    that holds all the other user functionalities of the Products feature group.
--
-- Safe to re-run; written for MySQL 8. Undo: migrations/undo/2026-11-26_quickbooks_product_name_undo.sql

-- ---------- 1. Column (added only when missing: MySQL 8 has no ADD COLUMN IF NOT EXISTS) ----------
SET @qpn_missing = ( SELECT COUNT(*) = 0 FROM information_schema.COLUMNS
                     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'quickbooks_product_name' );
SET @qpn_sql = IF( @qpn_missing,
                   'ALTER TABLE products ADD COLUMN quickbooks_product_name VARCHAR(200) NOT NULL DEFAULT '''' AFTER name',
                   'DO 0' );
PREPARE qpn_stmt FROM @qpn_sql;
EXECUTE qpn_stmt;
DEALLOCATE PREPARE qpn_stmt;

-- ---------- 2. Functionality ----------
INSERT INTO functionalities ( functionality_name, alias, functionality_description, functionality_type, is_page, is_a_content,
                              plugin_id, feature_group, audience )
SELECT 'sync_crm_products_quickbooks_names', 'Sync QuickBooks Names',
       'Allows to copy the names of the linked QuickBooks items into the CRM products', 'UPDATE', 0, 1,
       existing.plugin_id, existing.feature_group, 'user'
FROM ( SELECT plugin_id, feature_group FROM functionalities WHERE functionality_name = 'get_crm_products' LIMIT 1 ) existing
WHERE NOT EXISTS ( SELECT 1 FROM functionalities f WHERE f.functionality_name = 'sync_crm_products_quickbooks_names' );

SET @nf_id     = ( SELECT functionality_id FROM functionalities WHERE functionality_name = 'sync_crm_products_quickbooks_names' LIMIT 1 );
SET @nf_plugin = ( SELECT plugin_id FROM functionalities WHERE functionality_id = @nf_id );
SET @nf_group  = ( SELECT feature_group FROM functionalities WHERE functionality_id = @nf_id );
SET @nf_others = ( SELECT COUNT(*) FROM functionalities
                   WHERE plugin_id = @nf_plugin AND feature_group = @nf_group AND audience = 'user' AND functionality_id <> @nf_id );

INSERT INTO roles_functionalities ( role_id, functionality_id )
SELECT owners.role_id, @nf_id
FROM (
    SELECT rf.role_id
    FROM roles_functionalities rf
    JOIN functionalities f ON f.functionality_id = rf.functionality_id
    WHERE f.plugin_id = @nf_plugin AND f.feature_group = @nf_group AND f.audience = 'user' AND f.functionality_id <> @nf_id
    GROUP BY rf.role_id
    HAVING COUNT(*) = @nf_others
) owners
WHERE @nf_id IS NOT NULL AND @nf_others > 0
  AND NOT EXISTS ( SELECT 1 FROM roles_functionalities x WHERE x.role_id = owners.role_id AND x.functionality_id = @nf_id );
