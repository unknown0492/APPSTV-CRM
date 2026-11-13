-- Service cases: TVs at the office, replacements, delivery, returned TVs, closing by itself (APPSTV CRM 1.0.34)
--
-- 1. service_case_inspections + office and delivery columns. A TV at the office (consent signed) is marked by the
--    office as repaired there, or beyond repair and replaced (the replacement serial joins the case); both are
--    written into the warranty records. It is then "Ready to deliver" (result 'ready'); delivered and installed,
--    with photos (service_case_media purpose 'delivery'), it is "Delivered" (result 'delivered').
--    A case closes by itself when every TV is done (not covered / no repair / repaired / delivered); a main case
--    when all its TV cases are closed.
-- 2. product_sn_stock / product_sn_stock_log: a TV that was replaced comes back to the office ("returned", faulty).
--    Repaired, it is "ready to give out" (repaired_stock) and can be given to ANOTHER customer as a replacement:
--    that same TV record is used again (no duplicate), so its warranty page shows both lives. Kept in its own
--    table so product_sn (shared with sales and imports) is not changed.
-- 3. Permissions (Administrator + APPSTV Admin):
--      appstv_case_office       (permission only) marks a TV at the office as repaired / replaced
--      appstv_returned_tv_list  Returned TVs list (Service Cases page)
--      appstv_returned_tv_set   marks a returned TV as repaired (ready to give out) or faulty again
--
-- Safe to re-run (CREATE TABLE IF NOT EXISTS, columns checked first, NOT EXISTS on inserts); written for MySQL 8.
-- Rows are found by name, never by ID.

-- ── 1. Office and delivery ───────────────────────────────────────────────────────────────────────────────────
SET @has_col = ( SELECT COUNT(*) FROM information_schema.columns
                 WHERE table_schema = DATABASE() AND table_name = 'service_case_inspections' AND column_name = 'office_result' );
SET @sql = IF( @has_col = 0, 'ALTER TABLE service_case_inspections
    ADD COLUMN office_result VARCHAR(10) NOT NULL DEFAULT '''' COMMENT ''At the office: repaired | replaced'' AFTER consent_id,
    ADD COLUMN office_note TEXT NULL COMMENT ''What the office did'' AFTER office_result,
    ADD COLUMN office_pwrp_id VARCHAR(50) NULL COMMENT ''product_warranty_replacements.pwrp_id of the office repair / replacement'' AFTER office_note,
    ADD COLUMN new_case_tv_id INT NULL COMMENT ''service_case_tvs.id of the replacement TV'' AFTER office_pwrp_id,
    ADD COLUMN office_by VARCHAR(100) NOT NULL DEFAULT '''' AFTER new_case_tv_id,
    ADD COLUMN office_on VARCHAR(50) NOT NULL DEFAULT '''' COMMENT ''ISO 8601'' AFTER office_by,
    ADD COLUMN delivery_note TEXT NULL AFTER office_on,
    ADD COLUMN delivered_by VARCHAR(100) NOT NULL DEFAULT '''' AFTER delivery_note,
    ADD COLUMN delivered_on VARCHAR(50) NOT NULL DEFAULT '''' COMMENT ''ISO 8601, delivered and installed'' AFTER delivered_by', 'SELECT 1' );
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE service_case_inspections MODIFY COLUMN result VARCHAR(15) NOT NULL DEFAULT ''
    COMMENT ''''' (not finished) | not_covered | declined | repaired | waiting_part | to_office | at_office | ready (to deliver) | delivered | revisit';

ALTER TABLE service_case_media MODIFY COLUMN purpose VARCHAR(20) NOT NULL DEFAULT ''
    COMMENT 'Inspection step: outside_damage | inside_liquid | working | delivery | consent (signature, cannot be removed) | '''' = general';

ALTER TABLE service_case_visits MODIFY COLUMN reason VARCHAR(20) NOT NULL DEFAULT ''
    COMMENT 'revisit: part | tools | not_available | senior | office (TV taken to the office) | deliver (deliver the TV, 1.0.34) | other';

-- ── 2. Returned TVs ──────────────────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS product_sn_stock (
    product_sn_id  VARCHAR(50) NOT NULL COMMENT 'product_sn.product_sn_id',
    state          VARCHAR(15) NOT NULL COMMENT 'returned (faulty, at the office) | repaired_stock (ready to give out) | given (given out again)',
    case_id        INT NULL COMMENT 'service_cases.id it came back from (returned) or went out with (given)',
    updated_by     VARCHAR(100) NOT NULL DEFAULT '',
    updated_on     VARCHAR(50) NOT NULL COMMENT 'ISO 8601',
    PRIMARY KEY ( product_sn_id ),
    KEY idx_product_sn_stock_state ( state )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS product_sn_stock_log (
    id             INT NOT NULL AUTO_INCREMENT,
    product_sn_id  VARCHAR(50) NOT NULL,
    state          VARCHAR(15) NOT NULL COMMENT 'returned | repaired_stock | given',
    note           TEXT NULL,
    case_id        INT NULL,
    user_id        VARCHAR(100) NOT NULL DEFAULT '',
    created_on     VARCHAR(50) NOT NULL COMMENT 'ISO 8601',
    PRIMARY KEY ( id ),
    KEY idx_product_sn_stock_log_sn ( product_sn_id )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 3. Permissions ───────────────────────────────────────────────────────────────────────────────────────────
INSERT INTO functionalities ( functionality_name, alias, functionality_description, functionality_type, is_page, is_a_content,
                              plugin_id, feature_group, audience )
SELECT n.name, n.alias, n.description, n.type, 0, 1, existing.plugin_id, 'Service Cases', 'user'
FROM (
          SELECT 'appstv_case_office' AS name, 'Service Cases - TVs at the office' AS alias,
                 'Marks a TV at the office as repaired there, or beyond repair and replaced' AS description, 'UPDATE' AS type
    UNION SELECT 'appstv_returned_tv_list', 'Service Cases - returned TVs', 'Lists the replaced TVs that came back to the office', 'READ'
    UNION SELECT 'appstv_returned_tv_set', 'Service Cases - returned TV state', 'Marks a returned TV as repaired (ready to give out) or faulty again', 'UPDATE'
) n
JOIN ( SELECT plugin_id FROM functionalities WHERE functionality_name = 'appstv_case_update' LIMIT 1 ) existing
WHERE NOT EXISTS ( SELECT 1 FROM functionalities f WHERE f.functionality_name = n.name );

INSERT INTO roles_functionalities ( role_id, functionality_id )
SELECT r.role_id, f.functionality_id
FROM roles r
JOIN functionalities f ON f.functionality_name IN ( 'appstv_case_office', 'appstv_returned_tv_list', 'appstv_returned_tv_set' )
WHERE r.role_name IN ( 'Administrator', 'APPSTV Admin' )
  AND NOT EXISTS ( SELECT 1 FROM roles_functionalities x WHERE x.role_id = r.role_id AND x.functionality_id = f.functionality_id );
