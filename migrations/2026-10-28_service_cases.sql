-- Service Cases (appstv_crm / myappstv.com 1.0.7)
--
-- 1. New tables: service_cases (a customer's call), service_case_tvs (TVs of a case, linked to the service event
--    recorded through the case), service_case_notes (timeline: notes and every change).
-- 2. New page "Service Cases" (service_cases) in the Warranty plugin, under the CRM menu, and its webservice actions,
--    plus the service actions of the Warranty page (appstv_record_service, appstv_add_extended_warranty).
--    Granted to Administrator and APPSTV Admin.
--
-- Safe to re-run (CREATE TABLE IF NOT EXISTS, every insert skips rows that exist); written for MySQL 8. Rows are found
-- by name, never by ID.

CREATE TABLE IF NOT EXISTS service_cases (
    id               INT NOT NULL AUTO_INCREMENT COMMENT 'Case number, shown as SC-<id>',
    customer_id      VARCHAR(50) NOT NULL COMMENT 'customers.customer_id',
    contact_name     VARCHAR(200) NOT NULL DEFAULT '' COMMENT 'Person who called / to meet',
    contact_phone    VARCHAR(50) NOT NULL DEFAULT '',
    problem          TEXT NOT NULL,
    status           VARCHAR(20) NOT NULL DEFAULT 'open' COMMENT 'open | scheduled | visited | closed',
    assigned_user_id VARCHAR(100) NOT NULL DEFAULT '' COMMENT 'users.user_id of the staff member, empty = nobody',
    visit_date       VARCHAR(10) NOT NULL DEFAULT '' COMMENT 'YYYY-MM-DD, empty = not planned',
    visit_time       VARCHAR(5) NOT NULL DEFAULT '' COMMENT 'HH:MM, optional',
    outcome          VARCHAR(20) NOT NULL DEFAULT '' COMMENT 'repaired | replaced | no_fault | not_covered (when closed)',
    created_by       VARCHAR(100) NOT NULL DEFAULT '',
    created_on       VARCHAR(50) NOT NULL COMMENT 'ISO 8601',
    updated_on       VARCHAR(50) NOT NULL COMMENT 'ISO 8601, last change or note',
    closed_by        VARCHAR(100) NOT NULL DEFAULT '',
    closed_on        VARCHAR(50) NOT NULL DEFAULT '',
    PRIMARY KEY ( id ),
    KEY idx_service_cases_status ( status, visit_date ),
    KEY idx_service_cases_customer ( customer_id ),
    KEY idx_service_cases_assigned ( assigned_user_id )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS service_case_tvs (
    id             INT NOT NULL AUTO_INCREMENT,
    case_id        INT NOT NULL COMMENT 'service_cases.id',
    product_sn_id  VARCHAR(50) NULL COMMENT 'product_sn.product_sn_id, NULL when the serial number is not in the system',
    serial_number  VARCHAR(50) NOT NULL,
    pwrp_id        VARCHAR(50) NULL COMMENT 'product_warranty_replacements.pwrp_id of the service recorded through this case',
    added_on       VARCHAR(50) NOT NULL COMMENT 'ISO 8601',
    PRIMARY KEY ( id ),
    KEY idx_service_case_tvs_case ( case_id ),
    KEY idx_service_case_tvs_sn ( product_sn_id ),
    KEY idx_service_case_tvs_serial ( serial_number )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS service_case_notes (
    id          INT NOT NULL AUTO_INCREMENT,
    case_id     INT NOT NULL COMMENT 'service_cases.id',
    user_id     VARCHAR(100) NOT NULL DEFAULT '' COMMENT 'Who wrote it / made the change',
    note_type   VARCHAR(20) NOT NULL DEFAULT 'note' COMMENT 'created | note | status | assign | visit | tv | service | close | reopen',
    note        TEXT NOT NULL,
    created_on  VARCHAR(50) NOT NULL COMMENT 'ISO 8601',
    PRIMARY KEY ( id ),
    KEY idx_service_case_notes_case ( case_id )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Page functionality and webservice actions (Warranty plugin)
INSERT INTO functionalities
    ( functionality_name, alias, functionality_description, functionality_type, is_page, is_a_content, plugin_id )
SELECT n.name, n.alias, n.description, n.type, n.is_page, n.is_a_content, p.plugin_id
FROM (
          SELECT 'service_cases' AS name, 'Service Cases' AS alias,
                 'Service Cases page: customer calls, visits, assigned staff, notes and the service done' AS description,
                 'ACCESS' AS type, 1 AS is_page, 0 AS is_a_content
    UNION SELECT 'appstv_case_staff', 'Service Cases - staff list', 'Users that cases can be assigned to', 'READ', 0, 1
    UNION SELECT 'appstv_case_find_tv', 'Service Cases - find TV', 'Finds TVs by serial number with their warranty and customer', 'READ', 0, 1
    UNION SELECT 'appstv_case_list', 'Service Cases - list', 'Lists cases with filters and counts', 'READ', 0, 1
    UNION SELECT 'appstv_case_get', 'Service Cases - read case', 'Reads one case with its TVs and timeline', 'READ', 0, 1
    UNION SELECT 'appstv_case_create', 'Service Cases - open case', 'Opens a new case', 'CREATE', 0, 1
    UNION SELECT 'appstv_case_update', 'Service Cases - update case', 'Changes contact, problem, staff, visit date or status of a case', 'UPDATE', 0, 1
    UNION SELECT 'appstv_case_add_note', 'Service Cases - add note', 'Adds a note to the timeline of a case', 'CREATE', 0, 1
    UNION SELECT 'appstv_case_set_tv', 'Service Cases - add / remove TV', 'Adds a TV to a case or removes one', 'UPDATE', 0, 1
    UNION SELECT 'appstv_case_close', 'Service Cases - close case', 'Closes a case with its outcome', 'UPDATE', 0, 1
    UNION SELECT 'appstv_record_service', 'Warranty - record service', 'Records a repair or a replacement on a TV', 'CREATE', 0, 1
    UNION SELECT 'appstv_add_extended_warranty', 'Warranty - add extended warranty', 'Adds an extended warranty to a TV', 'CREATE', 0, 1
) n
JOIN plugins p ON p.plugin_name = 'appstv_warranty'
WHERE NOT EXISTS ( SELECT 1 FROM functionalities f WHERE f.functionality_name = n.name );

-- Menu page under CRM
INSERT INTO pages
    ( page_name, parent_id, hierarchy, page_sequence, icon, visible, page_title, functionality_id,
      title, description, tags, image, content, plugin_id )
SELECT 'service_cases', parent.page_id, 2, 1, 'fa-solid fa-screwdriver-wrench', 1, 'Service Cases', f.functionality_id,
       'Service Cases', 'Customer calls about their TVs: visits, assigned staff, notes and the service done', '', '', '', pl.plugin_id
FROM ( SELECT page_id FROM pages WHERE page_name = 'appstv_crm' ) parent
JOIN functionalities f ON f.functionality_name = 'service_cases'
JOIN plugins pl ON pl.plugin_name = 'appstv_warranty'
WHERE NOT EXISTS ( SELECT 1 FROM ( SELECT page_name FROM pages ) existing WHERE existing.page_name = 'service_cases' );

-- Grants: Administrator and APPSTV Admin
INSERT INTO roles_functionalities ( role_id, functionality_id )
SELECT r.role_id, f.functionality_id
FROM roles r
JOIN functionalities f ON f.functionality_name IN ( 'service_cases', 'appstv_case_staff', 'appstv_case_find_tv', 'appstv_case_list', 'appstv_case_get',
                                                    'appstv_case_create', 'appstv_case_update', 'appstv_case_add_note', 'appstv_case_set_tv',
                                                    'appstv_case_close', 'appstv_record_service', 'appstv_add_extended_warranty' )
WHERE r.role_name IN ( 'Administrator', 'APPSTV Admin' )
  AND NOT EXISTS ( SELECT 1 FROM roles_functionalities rf WHERE rf.role_id = r.role_id AND rf.functionality_id = f.functionality_id );
