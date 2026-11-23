-- Shopify orders: first status-history entry said "cancelled" (APPSTV CRM 1.0.40)
--
-- Until 1.0.40 the Shopify orders/create webhook wrote "cancelled" into order_status_history for every new order,
-- although the order itself got the right status. On 2026-11-23 live had 15 such rows, all on paid orders that are
-- not cancelled. This corrects each one to the status the order was created with, by the webhook's own rule
-- (financial_status paid -> confirmed, pending -> processing, voided -> cancelled, otherwise processed).
-- Dates are kept.
--
-- Only the FIRST history row of a Shopify order, written by SHOPIFY_WEBHOOK, still "cancelled", on an order that
-- isn't cancelled. A real cancellation (orders/cancelled webhook) writes a later row on a cancelled order and is
-- never touched.
--
-- Safe to re-run (corrected rows no longer match); written for MySQL 8 (the first-row list goes through a derived
-- table, as MySQL can't read the updated table in a subquery).
-- Undo: migrations/undo/2026-11-26_shopify_status_history_fix_undo.sql

UPDATE order_status_history h
JOIN orders o ON o.order_id = h.order_id
JOIN ( SELECT MIN( id ) AS first_id FROM order_status_history GROUP BY order_id ) firsts ON firsts.first_id = h.id
SET h.order_status = CASE o.financial_status
                         WHEN 'paid'    THEN 'confirmed'
                         WHEN 'pending' THEN 'processing'
                         WHEN 'voided'  THEN 'cancelled'
                         ELSE 'processed'
                     END
WHERE h.user_id = 'SHOPIFY_WEBHOOK'
  AND h.order_status = 'cancelled'
  AND o.order_source = 's7YtgHu79D_so'
  AND o.is_cancelled = '0';
