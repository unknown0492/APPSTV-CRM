-- UNDO of migrations/2026-11-26_move_104055_serials_to_103055.sql (APPSTV CRM 1.0.43). Safe to re-run; MySQL 8.
--
-- Puts the moved serials back on product 104055 (0324268998). 103055 had no serials from the 2026-10-28 19:28:11
-- import before 1.0.43 (its own serials were added on 2025-05-10 and 2026-04-15), so that timestamp picks exactly
-- the 268 moved rows.

UPDATE product_sn
SET product_id = '0324268998'
WHERE product_id = '0510252686'
  AND created_on = '2026-10-28 19:28:11';
