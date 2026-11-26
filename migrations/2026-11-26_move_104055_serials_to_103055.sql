-- Move the serials of SKU 104055 to SKU 103055 (APPSTV CRM 1.0.43)
--
-- The 268 serials under product 104055 "esTV 55" inch" (product_id 0324268998) are APPSTV VH 55 TVs: their serials
-- have the same format and date range as the sold 103055 serials. They were filed under the wrong SKU by the
-- warranty import of 2026-10-28 19:28:11. 104055 has no QuickBooks item; 103055 "APPSTV VH Series 55" TV"
-- (product_id 0510252686) is linked to the QuickBooks item "esTV 55" Professional 4K TV (APPSTV VH 55)".
--
-- Only product_sn.product_id changes. Warranties, invoice links and the rest point to product_sn_id and follow the
-- serial. Product 104055 itself stays (with no serials); nothing is deleted.
--
-- Serial 55230700349 is under both products (Mercure Icon on 103055, Hotel Grand Chancellor on 104055). The owner
-- decided on 2026-11-26 to keep both rows: duplicate serials are real, each row is its own TV. Live has no UNIQUE
-- (product_id, serial_number) on product_sn, so the move succeeds there.
--
-- Moves only rows from that import, and only when product 103055 exists. Safe to re-run (moved rows no longer match).
-- Undo: migrations/undo/2026-11-26_move_104055_serials_to_103055_undo.sql

UPDATE product_sn
SET product_id = '0510252686'
WHERE product_id = '0324268998'
  AND created_on = '2026-10-28 19:28:11'
  AND EXISTS ( SELECT 1 FROM ( SELECT product_id FROM products WHERE product_id = '0510252686' AND sku = '103055' ) target );
