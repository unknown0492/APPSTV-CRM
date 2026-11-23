-- UNDO of migrations/2026-11-25_quickbooks_invoice_links.sql (APPSTV CRM 1.0.39). Safe to re-run; MySQL 8.
--
-- Removes the 3 functionalities and their grants, the "made by hand" marks of #SY-24251099 / #SY-24251100, and the
-- 6 product links that migration added (only where the stored item is still the one it set).
-- Links the CRM made by itself afterwards, invoice errors and invoices created by 1.0.39 are kept: they are real data.

DELETE rf FROM roles_functionalities rf
JOIN functionalities f ON f.functionality_id = rf.functionality_id
WHERE f.functionality_name IN ( 'create_crm_order_quickbooks_invoice', 'mark_crm_order_quickbooks_invoice_manual', 'set_crm_product_quickbooks_item' );

DELETE FROM functionalities
WHERE functionality_name IN ( 'create_crm_order_quickbooks_invoice', 'mark_crm_order_quickbooks_invoice_manual', 'set_crm_product_quickbooks_item' );

DELETE x FROM order_meta x
JOIN order_meta om ON om.order_id = x.order_id AND om.order_meta_key = 'shopify_order_name'
WHERE x.order_meta_key = 'quickbooks_invoice_manual'
  AND om.order_meta_value IN ( '#SY-24251099', '#SY-24251100' );

DELETE pm FROM product_meta pm
JOIN products p ON p.product_id = pm.product_id
WHERE pm.product_meta_key = 'quickbooks_product_id'
  AND p.sku IN ( '101075', '103032', '103043', '103050', '103055', '104050' )
  AND pm.product_meta_value = CASE p.sku WHEN '101075' THEN '32' WHEN '103032' THEN '34' WHEN '103043' THEN '35'
                                         WHEN '103050' THEN '8'  WHEN '103055' THEN '7'  WHEN '104050' THEN '56' END;
