-- Service cases: customer consent to take a TV to the office (APPSTV CRM 1.0.33)
--
-- When the inspection ends with "To be taken to the office", the customer signs on the staff member's phone with
-- a finger. The sentence shown above the signature is kept word for word:
--   I, Mr. Tan Ah Kow, authorize Sohail Shaikh to take the TV APPSTV QLED VQ Series 4K UHD 55" (serial number
--   50230700014) on Friday, 13 November 2026 at 3:45 PM for inspection, and repairs, thereby giving them my
--   consent by signing below.
-- The signature is stored as a photo of the case (service_case_media, purpose 'consent'; it cannot be removed).
-- The TV is then "At the office" (service_case_inspections.result = 'at_office') and its inspection is locked.
-- No new permission: signing is a step of appstv_case_inspect (Inspect TVs).
--
-- Safe to re-run (CREATE TABLE IF NOT EXISTS, columns checked first); written for MySQL 8.

CREATE TABLE IF NOT EXISTS service_case_consents (
    id              INT NOT NULL AUTO_INCREMENT,
    case_id         INT NOT NULL COMMENT 'service_cases.id',
    case_tv_id      INT NOT NULL COMMENT 'service_case_tvs.id',
    inspection_id   INT NOT NULL COMMENT 'service_case_inspections.id',
    kind            VARCHAR(20) NOT NULL DEFAULT 'take_to_office' COMMENT 'What the customer agreed to',
    title           VARCHAR(10) NOT NULL DEFAULT '' COMMENT 'Mr. | Ms. | Mrs. | Dr. | '''' (none)',
    signer_name     VARCHAR(200) NOT NULL,
    staff_user_id   VARCHAR(100) NOT NULL DEFAULT '' COMMENT 'Staff member signed in on the phone',
    staff_name      VARCHAR(200) NOT NULL DEFAULT '' COMMENT 'Name as written in the statement',
    tv_text         VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Model and size as written in the statement',
    serial_number   VARCHAR(50) NOT NULL DEFAULT '',
    statement       TEXT NOT NULL COMMENT 'The exact sentence shown above the signature',
    shown_at        VARCHAR(50) NOT NULL COMMENT 'ISO 8601: date and time written in the statement (Singapore)',
    signed_on       VARCHAR(50) NOT NULL COMMENT 'ISO 8601: when it was saved',
    media_id        INT NOT NULL COMMENT 'service_case_media.id of the signature picture',
    user_agent      VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Device it was signed on',
    PRIMARY KEY ( id ),
    UNIQUE KEY uq_service_case_consents_inspection ( inspection_id, kind ),
    KEY idx_service_case_consents_case ( case_id )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @has_col = ( SELECT COUNT(*) FROM information_schema.columns
                 WHERE table_schema = DATABASE() AND table_name = 'service_case_inspections' AND column_name = 'consent_id' );
SET @sql = IF( @has_col = 0, 'ALTER TABLE service_case_inspections ADD COLUMN consent_id INT NULL COMMENT ''service_case_consents.id: the customer agreed the TV is taken to the office (1.0.33)'' AFTER pwrp_id', 'SELECT 1' );
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE service_case_inspections MODIFY COLUMN result VARCHAR(15) NOT NULL DEFAULT ''
    COMMENT ''''' (not finished) | not_covered | declined | repaired | waiting_part | to_office | at_office (consent signed) | revisit';

ALTER TABLE service_case_media MODIFY COLUMN purpose VARCHAR(20) NOT NULL DEFAULT ''
    COMMENT 'Inspection step: outside_damage | inside_liquid | working | consent (signature, cannot be removed) | '''' = general';

ALTER TABLE service_case_visits MODIFY COLUMN reason VARCHAR(20) NOT NULL DEFAULT ''
    COMMENT 'revisit: part | tools | not_available | senior | office (TV taken to the office, 1.0.33) | other';
