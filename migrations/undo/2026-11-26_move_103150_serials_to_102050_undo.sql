-- UNDO of migrations/2026-11-26_move_103150_serials_to_102050.sql (APPSTV CRM 1.0.44). Safe to re-run; MySQL 8.
--
-- Puts the moved serials back on product 103150 (1028267394) and sets 102050 back to has_sn = 0. 102050 had no
-- serials before 1.0.44, so the 2026-10-28 19:28:11 import timestamp picks exactly the 366 moved rows.
-- Note: if serials were added to 102050 after 1.0.44, has_sn = 0 hides them from the TV model lists.

UPDATE product_sn
SET product_id = '1028267394'
WHERE product_id = '1126264708'
  AND created_on = '2026-10-28 19:28:11';

UPDATE products
SET has_sn = 0
WHERE product_id = '1126264708'
  AND sku = '102050';
