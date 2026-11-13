-- Service case visits (APPSTV CRM 1.0.26)
--
-- A case can need more than one trip to the site (spare part, tools, customer away ...). Every trip is now a row of
-- service_case_visits ending with a result: fixed, or revisit (needs another visit) with a reason.
--
-- 1. New table service_case_visits (case, visit number, date, staff, result, reason, spare part with its
--    Ordered / Arrived state, note).
-- 2. service_cases.status gets the value 'revisit' (needs another visit; the column is VARCHAR, only its comment changes).
-- 3. New functionalities (feature group "Service Cases", audience user), granted to every role that can update cases
--    (has appstv_case_update), so both technicians and managers can finish a visit and plan the next one:
--      appstv_case_visit_finish   ends a visit: fixed, or needs another visit (reason, part, note)
--      appstv_case_visit_part     marks the spare part of a visit as ordered / arrived
-- 4. Backfill: a visited or closed case that had a planned visit date gets that visit as "Visit 1", result fixed.
--
-- Safe to re-run (CREATE TABLE IF NOT EXISTS, every insert skips rows that exist); written for MySQL 8. Rows are found
-- by name, never by ID.

-- ── 1. Visits ────────────────────────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS service_case_visits (
    id             INT NOT NULL AUTO_INCREMENT,
    case_id        INT NOT NULL COMMENT 'service_cases.id',
    visit_no       INT NOT NULL COMMENT '1, 2, 3 ... per case',
    visited_on     VARCHAR(10) NOT NULL COMMENT 'YYYY-MM-DD (Singapore) of the trip',
    staff          VARCHAR(500) NOT NULL DEFAULT '' COMMENT 'Comma list of users.user_id who went',
    result         VARCHAR(10) NOT NULL COMMENT 'fixed | revisit',
    reason         VARCHAR(20) NOT NULL DEFAULT '' COMMENT 'revisit: part | tools | not_available | senior | other',
    part_name      VARCHAR(200) NOT NULL DEFAULT '' COMMENT 'reason part: which spare part',
    part_status    VARCHAR(10) NOT NULL DEFAULT '' COMMENT 'reason part: needed | ordered | arrived',
    part_status_by VARCHAR(100) NOT NULL DEFAULT '',
    part_status_on VARCHAR(50) NOT NULL DEFAULT '' COMMENT 'ISO 8601',
    note           TEXT NULL,
    created_by     VARCHAR(100) NOT NULL DEFAULT '',
    created_on     VARCHAR(50) NOT NULL COMMENT 'ISO 8601',
    PRIMARY KEY ( id ),
    UNIQUE KEY uq_service_case_visits_no ( case_id, visit_no ),
    KEY idx_service_case_visits_part ( part_status )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 2. Status comment ────────────────────────────────────────────────────────────────────────────────────────
ALTER TABLE service_cases MODIFY COLUMN status VARCHAR(20) NOT NULL DEFAULT 'open'
    COMMENT 'open | scheduled | visited | revisit (needs another visit) | closed';

-- ── 3. Functionalities ───────────────────────────────────────────────────────────────────────────────────────
INSERT INTO functionalities ( functionality_name, alias, functionality_description, functionality_type, is_page, is_a_content,
                              plugin_id, feature_group, audience )
SELECT n.name, n.alias, n.description, n.type, 0, 1, existing.plugin_id, 'Service Cases', 'user'
FROM (
          SELECT 'appstv_case_visit_finish' AS name, 'Service Cases - finish visit' AS alias,
                 'Ends a visit to the site: fixed, or needs another visit with the reason' AS description, 'UPDATE' AS type
    UNION SELECT 'appstv_case_visit_part', 'Service Cases - spare part state', 'Marks the spare part of a visit as ordered or arrived', 'UPDATE'
) n
JOIN ( SELECT plugin_id FROM functionalities WHERE functionality_name = 'appstv_case_update' LIMIT 1 ) existing
WHERE NOT EXISTS ( SELECT 1 FROM functionalities f WHERE f.functionality_name = n.name );

-- Grants: every role that can update cases
INSERT INTO roles_functionalities ( role_id, functionality_id )
SELECT owners.role_id, f.functionality_id
FROM ( SELECT rf.role_id
       FROM roles_functionalities rf
       JOIN functionalities c ON c.functionality_id = rf.functionality_id AND c.functionality_name = 'appstv_case_update' ) owners
JOIN functionalities f ON f.functionality_name IN ( 'appstv_case_visit_finish', 'appstv_case_visit_part' )
WHERE NOT EXISTS ( SELECT 1 FROM roles_functionalities x WHERE x.role_id = owners.role_id AND x.functionality_id = f.functionality_id );

-- ── 4. Backfill: the visit of cases visited / closed before 1.0.26 ───────────────────────────────────────────
INSERT INTO service_case_visits ( case_id, visit_no, visited_on, staff, result, created_by, created_on )
SELECT c.id, 1, c.visit_date, c.assigned_user_id, 'fixed', '', c.updated_on
FROM service_cases c
WHERE c.status IN ( 'visited', 'closed' ) AND c.visit_date <> ''
  AND NOT EXISTS ( SELECT 1 FROM service_case_visits v WHERE v.case_id = c.id );
