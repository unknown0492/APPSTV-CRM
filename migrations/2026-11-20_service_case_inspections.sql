-- Service cases: inspection of each TV on site (APPSTV CRM 1.0.32)
--
-- 1. service_case_inspections: one row per TV of a case (service_case_tvs.id). The staff answer the steps of the
--    "Inspect" form on the TVs tab (warranty, outside damage, can it be opened here, liquid inside, repair on site);
--    every answer is saved at once and the server works out the next step and the result:
--      not_covered   physical / liquid damage, the customer does not want a paid repair
--      declined      out of warranty, the customer does not want a paid repair
--      repaired      repaired on site (the repair is also written into the warranty records)
--      waiting_part  needs a spare part (another visit)
--      to_office     has to be taken to the office (the consent signature follows in 1.0.33)
--      revisit       not repaired on site for another reason (another visit)
--    Out-of-warranty TVs and paid repairs carry a service charge note: to_collect -> collected (by hand).
-- 2. service_case_media: + tv_id and purpose, so a photo belongs to its TV and step (outside_damage, inside_liquid,
--    working). Such photos still show on the Photos & videos tab.
-- 3. appstv_case_inspect (Inspect TVs): answering the form and ticking the service charge as collected. Given to
--    Administrator and APPSTV Admin; add it to the technicians' role in Role Management.
--
-- Safe to re-run (CREATE TABLE IF NOT EXISTS, columns checked first, NOT EXISTS on inserts); written for MySQL 8.
-- Rows are found by name, never by ID.

-- ── 1. Inspections ───────────────────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS service_case_inspections (
    id              INT NOT NULL AUTO_INCREMENT,
    case_id         INT NOT NULL COMMENT 'service_cases.id',
    case_tv_id      INT NOT NULL COMMENT 'service_case_tvs.id (one inspection per TV of a case)',
    warranty        VARCHAR(10) NOT NULL DEFAULT '' COMMENT 'under | out (from the serial; chosen by staff when not known)',
    paid            VARCHAR(3) NOT NULL DEFAULT '' COMMENT 'Not covered / out of warranty: customer wants a paid repair yes | no',
    outside         VARCHAR(10) NOT NULL DEFAULT '' COMMENT 'none | damage',
    outside_kinds   VARCHAR(30) NOT NULL DEFAULT '' COMMENT 'Comma list: physical,liquid',
    outside_note    TEXT NULL COMMENT 'Where / what the damage is',
    open_here       VARCHAR(3) NOT NULL DEFAULT '' COMMENT 'Can it be opened on site: yes | no',
    no_open_reason  VARCHAR(20) NOT NULL DEFAULT '' COMMENT 'no_space | mounted | not_allowed | other',
    no_open_note    TEXT NULL,
    inside          VARCHAR(10) NOT NULL DEFAULT '' COMMENT 'none | liquid',
    inside_note     TEXT NULL,
    repair          VARCHAR(10) NOT NULL DEFAULT '' COMMENT 'repaired | part | office | other',
    repair_note     TEXT NULL COMMENT 'What was done, or why it was not repaired',
    part_name       VARCHAR(200) NOT NULL DEFAULT '',
    result          VARCHAR(15) NOT NULL DEFAULT '' COMMENT ''''' (not finished) | not_covered | declined | repaired | waiting_part | to_office | revisit',
    pwrp_id         VARCHAR(50) NULL COMMENT 'product_warranty_replacements.pwrp_id of the repair written by the inspection',
    charge          VARCHAR(12) NOT NULL DEFAULT '' COMMENT 'Service charge: '''' (none) | to_collect | collected',
    charge_by       VARCHAR(100) NOT NULL DEFAULT '',
    charge_on       VARCHAR(50) NOT NULL DEFAULT '' COMMENT 'ISO 8601',
    started_by      VARCHAR(100) NOT NULL DEFAULT '',
    started_on      VARCHAR(50) NOT NULL COMMENT 'ISO 8601',
    updated_by      VARCHAR(100) NOT NULL DEFAULT '',
    updated_on      VARCHAR(50) NOT NULL COMMENT 'ISO 8601',
    finished_on     VARCHAR(50) NOT NULL DEFAULT '' COMMENT 'ISO 8601, when the result was reached',
    PRIMARY KEY ( id ),
    UNIQUE KEY uq_service_case_inspections_tv ( case_tv_id ),
    KEY idx_service_case_inspections_case ( case_id ),
    KEY idx_service_case_inspections_result ( result ),
    KEY idx_service_case_inspections_charge ( charge )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 2. Photos per TV and step ────────────────────────────────────────────────────────────────────────────────
SET @has_col = ( SELECT COUNT(*) FROM information_schema.columns
                 WHERE table_schema = DATABASE() AND table_name = 'service_case_media' AND column_name = 'tv_id' );
SET @sql = IF( @has_col = 0, 'ALTER TABLE service_case_media ADD COLUMN tv_id INT NULL COMMENT ''service_case_tvs.id the photo belongs to (1.0.32)'' AFTER draft_id', 'SELECT 1' );
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_col = ( SELECT COUNT(*) FROM information_schema.columns
                 WHERE table_schema = DATABASE() AND table_name = 'service_case_media' AND column_name = 'purpose' );
SET @sql = IF( @has_col = 0, 'ALTER TABLE service_case_media ADD COLUMN purpose VARCHAR(20) NOT NULL DEFAULT '''' COMMENT ''Inspection step: outside_damage | inside_liquid | working ('''' = general)'' AFTER tv_id', 'SELECT 1' );
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_idx = ( SELECT COUNT(*) FROM information_schema.statistics
                 WHERE table_schema = DATABASE() AND table_name = 'service_case_media' AND index_name = 'idx_service_case_media_tv' );
SET @sql = IF( @has_idx = 0, 'ALTER TABLE service_case_media ADD KEY idx_service_case_media_tv ( tv_id, purpose )', 'SELECT 1' );
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ── 3. Inspect TVs ───────────────────────────────────────────────────────────────────────────────────────────
INSERT INTO functionalities ( functionality_name, alias, functionality_description, functionality_type, is_page, is_a_content,
                              plugin_id, feature_group, audience )
SELECT 'appstv_case_inspect', 'Service Cases - inspect TVs',
       'Answers the inspection of a TV on site (damage, repair on site) and ticks its service charge as collected', 'UPDATE', 0, 1,
       existing.plugin_id, 'Service Cases', 'user'
FROM ( SELECT plugin_id FROM functionalities WHERE functionality_name = 'appstv_case_update' LIMIT 1 ) existing
WHERE NOT EXISTS ( SELECT 1 FROM functionalities f WHERE f.functionality_name = 'appstv_case_inspect' );

INSERT INTO roles_functionalities ( role_id, functionality_id )
SELECT r.role_id, f.functionality_id
FROM roles r
JOIN functionalities f ON f.functionality_name = 'appstv_case_inspect'
WHERE r.role_name IN ( 'Administrator', 'APPSTV Admin' )
  AND NOT EXISTS ( SELECT 1 FROM roles_functionalities x WHERE x.role_id = r.role_id AND x.functionality_id = f.functionality_id );
