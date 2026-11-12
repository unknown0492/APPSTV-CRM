-- Service case OPENING wizard (appstv_crm / myappstv.com 1.0.19)
--
-- 1. service_cases: + main case / TV case structure (parent_case_id, case_kind, tv_index), complainant (complainant_id,
--    contact_email, contact_position), complained_on (created_on stays the time it was logged), tv_count,
--    warranty_status (under / out / mixed / unknown), problem_types, visit window (visit_from, visit_to), draft_id.
-- 2. New tables: customer_contacts (people who complained, per customer), service_case_channels, service_case_staff
--    (more than one staff member per case), service_case_models (models mentioned), service_case_media (photos and
--    videos with their processing state), service_case_drafts (shared drafts), service_case_history (who changed
--    what and when, for cases and drafts), service_case_digest_log (daily 9:00 e-mail, once per staff per day).
-- 3. New webservice functionalities of the wizard (feature group "Service Cases"), granted to every role that can
--    open cases (has appstv_case_create).
--
-- Safe to re-run (columns and indexes are checked first, CREATE TABLE IF NOT EXISTS, every insert skips rows that
-- exist); written for MySQL 8. Rows are found by name, never by ID. Live had no cases when this was written.

-- ── 1. service_cases columns ─────────────────────────────────────────────────────────────────────────────────
SET @has_col = ( SELECT COUNT(*) FROM information_schema.columns
                 WHERE table_schema = DATABASE() AND table_name = 'service_cases' AND column_name = 'parent_case_id' );
SET @sql = IF( @has_col = 0, 'ALTER TABLE service_cases ADD COLUMN parent_case_id INT NULL COMMENT ''Main case of a TV case (service_cases.id), NULL otherwise'' AFTER id', 'SELECT 1' );
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_col = ( SELECT COUNT(*) FROM information_schema.columns
                 WHERE table_schema = DATABASE() AND table_name = 'service_cases' AND column_name = 'case_kind' );
SET @sql = IF( @has_col = 0, 'ALTER TABLE service_cases ADD COLUMN case_kind VARCHAR(10) NOT NULL DEFAULT ''single'' COMMENT ''single | main (has TV cases) | tv (one TV of a main case)'' AFTER parent_case_id', 'SELECT 1' );
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_col = ( SELECT COUNT(*) FROM information_schema.columns
                 WHERE table_schema = DATABASE() AND table_name = 'service_cases' AND column_name = 'tv_index' );
SET @sql = IF( @has_col = 0, 'ALTER TABLE service_cases ADD COLUMN tv_index SMALLINT NOT NULL DEFAULT 0 COMMENT ''Number of a TV case inside its main case (SC-12-01 = 1), 0 otherwise'' AFTER case_kind', 'SELECT 1' );
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_col = ( SELECT COUNT(*) FROM information_schema.columns
                 WHERE table_schema = DATABASE() AND table_name = 'service_cases' AND column_name = 'complainant_id' );
SET @sql = IF( @has_col = 0, 'ALTER TABLE service_cases ADD COLUMN complainant_id INT NULL COMMENT ''customer_contacts.id of the person who complained'' AFTER customer_id', 'SELECT 1' );
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_col = ( SELECT COUNT(*) FROM information_schema.columns
                 WHERE table_schema = DATABASE() AND table_name = 'service_cases' AND column_name = 'contact_email' );
SET @sql = IF( @has_col = 0, 'ALTER TABLE service_cases ADD COLUMN contact_email VARCHAR(200) NOT NULL DEFAULT '''' COMMENT ''Complainant e-mail (copy at opening)'' AFTER contact_phone', 'SELECT 1' );
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_col = ( SELECT COUNT(*) FROM information_schema.columns
                 WHERE table_schema = DATABASE() AND table_name = 'service_cases' AND column_name = 'contact_position' );
SET @sql = IF( @has_col = 0, 'ALTER TABLE service_cases ADD COLUMN contact_position VARCHAR(100) NOT NULL DEFAULT '''' COMMENT ''Complainant position, e.g. Front Office'' AFTER contact_email', 'SELECT 1' );
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_col = ( SELECT COUNT(*) FROM information_schema.columns
                 WHERE table_schema = DATABASE() AND table_name = 'service_cases' AND column_name = 'complained_on' );
SET @sql = IF( @has_col = 0, 'ALTER TABLE service_cases ADD COLUMN complained_on VARCHAR(50) NOT NULL DEFAULT '''' COMMENT ''ISO 8601, when the customer complained (created_on = when it was logged)'' AFTER contact_position', 'SELECT 1' );
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_col = ( SELECT COUNT(*) FROM information_schema.columns
                 WHERE table_schema = DATABASE() AND table_name = 'service_cases' AND column_name = 'tv_count' );
SET @sql = IF( @has_col = 0, 'ALTER TABLE service_cases ADD COLUMN tv_count SMALLINT NOT NULL DEFAULT 1 COMMENT ''Number of TVs with a problem (as told)'' AFTER complained_on', 'SELECT 1' );
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_col = ( SELECT COUNT(*) FROM information_schema.columns
                 WHERE table_schema = DATABASE() AND table_name = 'service_cases' AND column_name = 'warranty_status' );
SET @sql = IF( @has_col = 0, 'ALTER TABLE service_cases ADD COLUMN warranty_status VARCHAR(10) NOT NULL DEFAULT ''unknown'' COMMENT ''under | out | mixed (some TVs are, some not) | unknown'' AFTER tv_count', 'SELECT 1' );
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_col = ( SELECT COUNT(*) FROM information_schema.columns
                 WHERE table_schema = DATABASE() AND table_name = 'service_cases' AND column_name = 'problem_types' );
SET @sql = IF( @has_col = 0, 'ALTER TABLE service_cases ADD COLUMN problem_types VARCHAR(255) NOT NULL DEFAULT '''' COMMENT ''Comma list: no_power,no_picture,no_sound,remote,wifi_apps,broken_screen,physical_damage,liquid_damage,other'' AFTER warranty_status', 'SELECT 1' );
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_col = ( SELECT COUNT(*) FROM information_schema.columns
                 WHERE table_schema = DATABASE() AND table_name = 'service_cases' AND column_name = 'visit_from' );
SET @sql = IF( @has_col = 0, 'ALTER TABLE service_cases ADD COLUMN visit_from VARCHAR(10) NOT NULL DEFAULT '''' COMMENT ''YYYY-MM-DD, start of the visit window'' AFTER problem', 'SELECT 1' );
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_col = ( SELECT COUNT(*) FROM information_schema.columns
                 WHERE table_schema = DATABASE() AND table_name = 'service_cases' AND column_name = 'visit_to' );
SET @sql = IF( @has_col = 0, 'ALTER TABLE service_cases ADD COLUMN visit_to VARCHAR(10) NOT NULL DEFAULT '''' COMMENT ''YYYY-MM-DD, end of the visit window (daily reminder until then, red after)'' AFTER visit_from', 'SELECT 1' );
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_col = ( SELECT COUNT(*) FROM information_schema.columns
                 WHERE table_schema = DATABASE() AND table_name = 'service_cases' AND column_name = 'draft_id' );
SET @sql = IF( @has_col = 0, 'ALTER TABLE service_cases ADD COLUMN draft_id INT NULL COMMENT ''service_case_drafts.id the case was opened from'' AFTER visit_to', 'SELECT 1' );
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_idx = ( SELECT COUNT(*) FROM information_schema.statistics
                 WHERE table_schema = DATABASE() AND table_name = 'service_cases' AND index_name = 'idx_service_cases_parent' );
SET @sql = IF( @has_idx = 0, 'ALTER TABLE service_cases ADD KEY idx_service_cases_parent ( parent_case_id, tv_index )', 'SELECT 1' );
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_idx = ( SELECT COUNT(*) FROM information_schema.statistics
                 WHERE table_schema = DATABASE() AND table_name = 'service_cases' AND index_name = 'idx_service_cases_visit_to' );
SET @sql = IF( @has_idx = 0, 'ALTER TABLE service_cases ADD KEY idx_service_cases_visit_to ( status, visit_to )', 'SELECT 1' );
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ── 2. New tables ────────────────────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS customer_contacts (
    id            INT NOT NULL AUTO_INCREMENT,
    customer_id   VARCHAR(50) NOT NULL COMMENT 'customers.customer_id',
    name          VARCHAR(200) NOT NULL,
    phone         VARCHAR(50) NOT NULL DEFAULT '',
    email         VARCHAR(200) NOT NULL DEFAULT '',
    position      VARCHAR(100) NOT NULL DEFAULT '' COMMENT 'e.g. Front Office, Engineering',
    created_by    VARCHAR(100) NOT NULL DEFAULT '',
    created_on    VARCHAR(50) NOT NULL COMMENT 'ISO 8601',
    last_used_on  VARCHAR(50) NOT NULL DEFAULT '' COMMENT 'ISO 8601, last case opened with this person (suggestions sort by it)',
    PRIMARY KEY ( id ),
    KEY idx_customer_contacts_customer ( customer_id, last_used_on )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS service_case_channels (
    case_id  INT NOT NULL COMMENT 'service_cases.id',
    channel  VARCHAR(20) NOT NULL COMMENT 'call | whatsapp | email | sms',
    PRIMARY KEY ( case_id, channel )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS service_case_staff (
    case_id      INT NOT NULL COMMENT 'service_cases.id',
    user_id      VARCHAR(100) NOT NULL COMMENT 'users.user_id',
    added_by     VARCHAR(100) NOT NULL DEFAULT '',
    added_on     VARCHAR(50) NOT NULL COMMENT 'ISO 8601',
    notified_on  VARCHAR(50) NOT NULL DEFAULT '' COMMENT 'ISO 8601, when the "case assigned" e-mail went out',
    PRIMARY KEY ( case_id, user_id ),
    KEY idx_service_case_staff_user ( user_id )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS service_case_models (
    case_id     INT NOT NULL COMMENT 'service_cases.id',
    product_id  VARCHAR(50) NOT NULL COMMENT 'products.product_id, or unsure for "Not sure"',
    PRIMARY KEY ( case_id, product_id )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS service_case_drafts (
    id              INT NOT NULL AUTO_INCREMENT,
    data            MEDIUMTEXT NOT NULL COMMENT 'JSON of the wizard answers',
    customer_label  VARCHAR(200) NOT NULL DEFAULT '' COMMENT 'Customer shown in the drafts list',
    step            TINYINT NOT NULL DEFAULT 1 COMMENT 'Wizard step last shown',
    status          VARCHAR(10) NOT NULL DEFAULT 'draft' COMMENT 'draft | opened (became a case) | deleted',
    case_id         INT NULL COMMENT 'service_cases.id once opened',
    revision        INT NOT NULL DEFAULT 1 COMMENT 'Raised on every save; a save based on an older revision is refused',
    created_by      VARCHAR(100) NOT NULL DEFAULT '',
    created_on      VARCHAR(50) NOT NULL COMMENT 'ISO 8601',
    updated_by      VARCHAR(100) NOT NULL DEFAULT '',
    updated_on      VARCHAR(50) NOT NULL COMMENT 'ISO 8601',
    deleted_by      VARCHAR(100) NOT NULL DEFAULT '',
    deleted_on      VARCHAR(50) NOT NULL DEFAULT '',
    PRIMARY KEY ( id ),
    KEY idx_service_case_drafts_status ( status, updated_on )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS service_case_media (
    id             INT NOT NULL AUTO_INCREMENT,
    case_id        INT NULL COMMENT 'service_cases.id (NULL while it belongs to a draft)',
    draft_id       INT NULL COMMENT 'service_case_drafts.id it was uploaded in',
    kind           VARCHAR(10) NOT NULL COMMENT 'photo | video',
    file_name      VARCHAR(200) NOT NULL DEFAULT '' COMMENT 'Stored file (ready version)',
    original_name  VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Name on the device it came from',
    source_file    VARCHAR(200) NOT NULL DEFAULT '' COMMENT 'Uploaded video before conversion, removed once converted',
    mime           VARCHAR(100) NOT NULL DEFAULT '',
    size_bytes     BIGINT NOT NULL DEFAULT 0 COMMENT 'Stored size',
    source_bytes   BIGINT NOT NULL DEFAULT 0 COMMENT 'Size as uploaded',
    width          INT NOT NULL DEFAULT 0,
    height         INT NOT NULL DEFAULT 0,
    duration_s     DECIMAL(8,2) NOT NULL DEFAULT 0 COMMENT 'Videos only',
    state          VARCHAR(12) NOT NULL DEFAULT 'uploading' COMMENT 'uploading | queued | processing | ready | failed',
    error          VARCHAR(255) NOT NULL DEFAULT '',
    attempts       TINYINT NOT NULL DEFAULT 0 COMMENT 'Conversion attempts',
    uploaded_by    VARCHAR(100) NOT NULL DEFAULT '',
    uploaded_on    VARCHAR(50) NOT NULL COMMENT 'ISO 8601',
    processed_on   VARCHAR(50) NOT NULL DEFAULT '' COMMENT 'ISO 8601',
    deleted_by     VARCHAR(100) NOT NULL DEFAULT '',
    deleted_on     VARCHAR(50) NOT NULL DEFAULT '' COMMENT 'ISO 8601, set when removed (file deleted, row kept for the history)',
    PRIMARY KEY ( id ),
    KEY idx_service_case_media_case ( case_id ),
    KEY idx_service_case_media_draft ( draft_id ),
    KEY idx_service_case_media_state ( state )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS service_case_history (
    id          INT NOT NULL AUTO_INCREMENT,
    case_id     INT NULL COMMENT 'service_cases.id (main or TV case)',
    draft_id    INT NULL COMMENT 'service_case_drafts.id (changes made while it was a draft)',
    user_id     VARCHAR(100) NOT NULL DEFAULT '' COMMENT 'Who made the change (empty = the system)',
    action      VARCHAR(30) NOT NULL COMMENT 'draft_created | draft_saved | draft_deleted | opened | changed | staff_added | staff_removed | tv_case_added | tv_case_removed | media_added | media_removed | serial_added | serial_removed | status',
    field       VARCHAR(50) NOT NULL DEFAULT '' COMMENT 'Field changed (action changed)',
    old_value   TEXT NULL,
    new_value   TEXT NULL,
    detail      VARCHAR(500) NOT NULL DEFAULT '' COMMENT 'Readable line, e.g. the reason of a removal',
    created_on  VARCHAR(50) NOT NULL COMMENT 'ISO 8601',
    PRIMARY KEY ( id ),
    KEY idx_service_case_history_case ( case_id, id ),
    KEY idx_service_case_history_draft ( draft_id, id )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS service_case_digest_log (
    user_id     VARCHAR(100) NOT NULL COMMENT 'users.user_id',
    sent_date   VARCHAR(10) NOT NULL COMMENT 'YYYY-MM-DD, Singapore',
    case_count  INT NOT NULL DEFAULT 0,
    sent_on     VARCHAR(50) NOT NULL COMMENT 'ISO 8601',
    PRIMARY KEY ( user_id, sent_date )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 3. Functionalities of the wizard ─────────────────────────────────────────────────────────────────────────
INSERT INTO functionalities ( functionality_name, alias, functionality_description, functionality_type, is_page, is_a_content,
                              plugin_id, feature_group, audience )
SELECT n.name, n.alias, n.description, n.type, 0, 1, existing.plugin_id, 'Service Cases', 'user'
FROM (
          SELECT 'appstv_case_customer_search' AS name, 'Service Cases - find customer' AS alias,
                 'Finds customers by company, name, phone or e-mail while typing' AS description, 'READ' AS type
    UNION SELECT 'appstv_case_customer_add', 'Service Cases - add customer', 'Adds a new customer from the Open case wizard', 'CREATE'
    UNION SELECT 'appstv_case_contacts', 'Service Cases - complainants', 'Lists the people who complained before for a customer', 'READ'
    UNION SELECT 'appstv_case_models', 'Service Cases - TV models', 'Lists the TV models to pick from', 'READ'
    UNION SELECT 'appstv_case_draft_list', 'Service Cases - list drafts', 'Lists the drafts of cases not opened yet', 'READ'
    UNION SELECT 'appstv_case_draft_get', 'Service Cases - read draft', 'Reads one draft with its history', 'READ'
    UNION SELECT 'appstv_case_draft_save', 'Service Cases - save draft', 'Starts or saves a draft of a case', 'UPDATE'
    UNION SELECT 'appstv_case_draft_delete', 'Service Cases - delete draft', 'Deletes a draft', 'DELETE'
    UNION SELECT 'appstv_case_history_get', 'Service Cases - history', 'Reads who changed what and when on a case', 'READ'
    UNION SELECT 'appstv_case_media_upload', 'Service Cases - upload photo / video', 'Uploads photos and videos to a case or draft, in pieces', 'CREATE'
    UNION SELECT 'appstv_case_media_delete', 'Service Cases - remove photo / video', 'Removes a photo or video of a case or draft', 'DELETE'
    UNION SELECT 'appstv_case_tv_case_add', 'Service Cases - add TV case', 'Adds TV cases to a main case', 'UPDATE'
    UNION SELECT 'appstv_case_tv_case_remove', 'Service Cases - remove TV case', 'Removes a TV case from a main case', 'UPDATE'
) n
JOIN ( SELECT plugin_id FROM functionalities WHERE functionality_name = 'appstv_case_create' LIMIT 1 ) existing
WHERE NOT EXISTS ( SELECT 1 FROM functionalities f WHERE f.functionality_name = n.name );

-- Grants: every role that can open cases
INSERT INTO roles_functionalities ( role_id, functionality_id )
SELECT owners.role_id, f.functionality_id
FROM ( SELECT rf.role_id
       FROM roles_functionalities rf
       JOIN functionalities c ON c.functionality_id = rf.functionality_id AND c.functionality_name = 'appstv_case_create' ) owners
JOIN functionalities f ON f.functionality_name IN ( 'appstv_case_customer_search', 'appstv_case_customer_add', 'appstv_case_contacts',
                                                    'appstv_case_models', 'appstv_case_draft_list', 'appstv_case_draft_get',
                                                    'appstv_case_draft_save', 'appstv_case_draft_delete', 'appstv_case_history_get',
                                                    'appstv_case_media_upload', 'appstv_case_media_delete',
                                                    'appstv_case_tv_case_add', 'appstv_case_tv_case_remove' )
WHERE NOT EXISTS ( SELECT 1 FROM roles_functionalities x WHERE x.role_id = owners.role_id AND x.functionality_id = f.functionality_id );
