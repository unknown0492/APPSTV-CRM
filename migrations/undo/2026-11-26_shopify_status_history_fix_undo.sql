-- UNDO of migrations/2026-11-26_shopify_status_history_fix.sql (APPSTV CRM 1.0.40). Safe to re-run; MySQL 8.
--
-- Puts "cancelled" back on the first SHOPIFY_WEBHOOK history row of Shopify orders that are not cancelled, for
-- rows written before 1.0.40 was deployed (created_at before 2026-11-24): later orders were written correctly.

UPDATE order_status_history h
JOIN orders o ON o.order_id = h.order_id
JOIN ( SELECT MIN( id ) AS first_id FROM order_status_history GROUP BY order_id ) firsts ON firsts.first_id = h.id
SET h.order_status = 'cancelled'
WHERE h.user_id = 'SHOPIFY_WEBHOOK'
  AND h.order_status <> 'cancelled'
  AND h.created_at < '2026-11-24'
  AND o.order_source = 's7YtgHu79D_so'
  AND o.is_cancelled = '0';
