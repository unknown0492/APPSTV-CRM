-- Move the serials of SKU 103150 to SKU 102050 (APPSTV CRM 1.0.44)
--
-- The 366 serials under product 103150 "APPSTV VH Series 50" Google TV" (product_id 1028267394) are APPSTV VH 50S
-- TVs: their serials carry the "S" (10250S2604xxxx), and all were sold to Days Inn (Venue Thomson) on invoice 13122.
-- They were filed under the wrong SKU by the warranty import of 2026-10-28 19:28:11. 103150 has no QuickBooks item;
-- 102050 "APPSTV VH 50S" (product_id 1126264708) is linked to the QuickBooks item "APPSTV VH 50S Professional TV".
-- Owner decision 2026-11-26.
--
-- 1. 102050 becomes a product with serial numbers (has_sn 0 -> 1), like every other TV model, so it shows as a TV
--    model in service cases, the warranty page and Record Sale, and order delivery asks for its serials.
-- 2. product_sn.product_id 1028267394 -> 1126264708 for the rows of that import.
--
-- Warranties (366) and invoice links point to product_sn_id and follow the serial. Product 103150 stays (with no
-- serials); nothing is deleted. 102050 had no serials, so no duplicates are created.
--
-- Runs only when product 102050 exists. Safe to re-run (done rows no longer match).
-- Undo: migrations/undo/2026-11-26_move_103150_serials_to_102050_undo.sql

UPDATE products
SET has_sn = 1
WHERE product_id = '1126264708'
  AND sku = '102050';

UPDATE product_sn
SET product_id = '1126264708'
WHERE product_id = '1028267394'
  AND created_on = '2026-10-28 19:28:11'
  AND EXISTS ( SELECT 1 FROM ( SELECT product_id FROM products WHERE product_id = '1126264708' AND sku = '102050' ) target );
