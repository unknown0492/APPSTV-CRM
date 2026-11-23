-- Shopify orders without a QuickBooks invoice (APPSTV CRM 1.0.39)
--
-- Shopify orders #SY-24251099 and #SY-24251100 got no QuickBooks invoice: their products had no QuickBooks item ID
-- (product_meta "quickbooks_product_id"), so QuickBooks refused the invoice. Both invoices were made by hand.
--
-- 1. Links 6 CRM products to the QuickBooks item with the same SKU (found read-only in QuickBooks on 2026-11-23,
--    approved by the owner). SKUs 103150, 104043, 104055, 104065 have no QuickBooks item yet: once the SKU is added
--    to an item in QuickBooks, the CRM links it by itself at the next invoice.
-- 2. Marks the invoices of #SY-24251099 and #SY-24251100 as made by hand, so the CRM never makes them again.
-- 3. New functionalities: create_crm_order_quickbooks_invoice, mark_crm_order_quickbooks_invoice_manual (Orders)
--    and set_crm_product_quickbooks_item (Products), granted by the template rule: every role that holds all the
--    other user functionalities of the feature group.
--
-- Safe to re-run (NOT EXISTS); written for MySQL 8. Rows are found by name / SKU / Shopify order name, never by ID.
-- Undo: migrations/undo/2026-11-25_quickbooks_invoice_links_undo.sql

-- ---------- 1. Product links ----------
-- (Plain string literals, no derived table: on MySQL 8 a derived table's text columns can clash with the table
--  collation - "Illegal mix of collations", see eslink 1.0.28.)
INSERT INTO product_meta ( product_id, product_meta_key, product_meta_value )
SELECT p.product_id, 'quickbooks_product_id',
       CASE p.sku WHEN '101075' THEN '32' WHEN '103032' THEN '34' WHEN '103043' THEN '35'
                  WHEN '103050' THEN '8'  WHEN '103055' THEN '7'  WHEN '104050' THEN '56' END
FROM products p
WHERE p.sku IN ( '101075', '103032', '103043', '103050', '103055', '104050' )
  AND NOT EXISTS ( SELECT 1 FROM product_meta pm
                   WHERE pm.product_id = p.product_id AND pm.product_meta_key = 'quickbooks_product_id' );

-- ---------- 2. Invoices made by hand ----------
INSERT INTO order_meta ( order_id, order_meta_key, order_meta_value )
SELECT om.order_id, 'quickbooks_invoice_manual', '1'
FROM order_meta om
WHERE om.order_meta_key = 'shopify_order_name'
  AND om.order_meta_value IN ( '#SY-24251099', '#SY-24251100' )
  AND NOT EXISTS ( SELECT 1 FROM order_meta x
                   WHERE x.order_id = om.order_id AND x.order_meta_key IN ( 'quickbooks_invoice_manual', 'quickbooks_invoice_id' ) );

-- ---------- 3. Functionalities ----------

-- 3a. create_crm_order_quickbooks_invoice (Orders, user)
INSERT INTO functionalities ( functionality_name, alias, functionality_description, functionality_type, is_page, is_a_content,
                              plugin_id, feature_group, audience )
SELECT 'create_crm_order_quickbooks_invoice', 'Create QuickBooks Invoice',
       'Allows to create the QuickBooks invoice of an order that has none', 'CREATE', 0, 1,
       existing.plugin_id, 'Orders', 'user'
FROM ( SELECT plugin_id FROM functionalities WHERE functionality_name = 'get_crm_orders' LIMIT 1 ) existing
WHERE NOT EXISTS ( SELECT 1 FROM functionalities f WHERE f.functionality_name = 'create_crm_order_quickbooks_invoice' );

SET @nf_id     = ( SELECT functionality_id FROM functionalities WHERE functionality_name = 'create_crm_order_quickbooks_invoice' LIMIT 1 );
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

-- 3b. mark_crm_order_quickbooks_invoice_manual (Orders, user). Counted after 3a, so a role needs 3a's grant too.
INSERT INTO functionalities ( functionality_name, alias, functionality_description, functionality_type, is_page, is_a_content,
                              plugin_id, feature_group, audience )
SELECT 'mark_crm_order_quickbooks_invoice_manual', 'Mark Invoice Made by Hand',
       'Allows to mark that the QuickBooks invoice of an order was made by hand in QuickBooks', 'UPDATE', 0, 1,
       existing.plugin_id, 'Orders', 'user'
FROM ( SELECT plugin_id FROM functionalities WHERE functionality_name = 'get_crm_orders' LIMIT 1 ) existing
WHERE NOT EXISTS ( SELECT 1 FROM functionalities f WHERE f.functionality_name = 'mark_crm_order_quickbooks_invoice_manual' );

SET @nf_id     = ( SELECT functionality_id FROM functionalities WHERE functionality_name = 'mark_crm_order_quickbooks_invoice_manual' LIMIT 1 );
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

-- 3c. set_crm_product_quickbooks_item (Products, user)
INSERT INTO functionalities ( functionality_name, alias, functionality_description, functionality_type, is_page, is_a_content,
                              plugin_id, feature_group, audience )
SELECT 'set_crm_product_quickbooks_item', 'Link QuickBooks Item',
       'Allows to find and set the QuickBooks item a product is invoiced as', 'UPDATE', 0, 1,
       existing.plugin_id, 'Products', 'user'
FROM ( SELECT plugin_id FROM functionalities WHERE functionality_name = 'create_crm_product' LIMIT 1 ) existing
WHERE NOT EXISTS ( SELECT 1 FROM functionalities f WHERE f.functionality_name = 'set_crm_product_quickbooks_item' );

SET @nf_id     = ( SELECT functionality_id FROM functionalities WHERE functionality_name = 'set_crm_product_quickbooks_item' LIMIT 1 );
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
