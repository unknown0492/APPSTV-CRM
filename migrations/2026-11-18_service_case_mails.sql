-- Service case e-mails (APPSTV CRM 1.0.28)
--
-- One table, service_case_mails, holds every case e-mail to staff:
--   kind 'opened'  queued when a case is opened (one row per assigned staff member), sent by the every-minute job
--   kind 'digest'  the daily 9:00 (Singapore) list of a staff member's cases, one row per staff member per day
-- dedupe_key stops the same e-mail being sent twice ('opened:<case>:<user>', 'digest:<user>:<YYYY-MM-DD>').
-- Nothing else changes: no functionalities, no permissions (the e-mails are sent by cron/appstv_service_cases.php).
--
-- Safe to re-run (CREATE TABLE IF NOT EXISTS); written for MySQL 8.

CREATE TABLE IF NOT EXISTS service_case_mails (
    id          INT NOT NULL AUTO_INCREMENT,
    kind        VARCHAR(10) NOT NULL COMMENT 'opened | added | digest',
    case_id     INT NULL COMMENT 'service_cases.id (opened); NULL for a digest',
    user_id     VARCHAR(100) NOT NULL COMMENT 'users.user_id of the staff member',
    dedupe_key  VARCHAR(150) NULL COMMENT 'Same key = same e-mail, sent once',
    state       VARCHAR(10) NOT NULL DEFAULT 'queued' COMMENT 'queued | sending | sent | failed | skipped',
    attempts    INT NOT NULL DEFAULT 0,
    to_email    VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Address used when it was sent',
    subject     VARCHAR(255) NOT NULL DEFAULT '',
    error       VARCHAR(255) NOT NULL DEFAULT '',
    created_on  VARCHAR(50) NOT NULL COMMENT 'ISO 8601',
    sent_on     VARCHAR(50) NOT NULL DEFAULT '' COMMENT 'ISO 8601 (or the last attempt)',
    PRIMARY KEY ( id ),
    UNIQUE KEY uq_service_case_mails_dedupe ( dedupe_key ),
    KEY idx_service_case_mails_state ( state, id ),
    KEY idx_service_case_mails_case ( case_id )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
