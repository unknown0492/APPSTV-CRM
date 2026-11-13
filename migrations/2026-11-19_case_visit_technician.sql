-- Service cases: who can be sent on site visits, and who can change a case's staff (APPSTV CRM 1.0.29)
--
-- 1. appstv_case_visit_technician  (permission only: there is no web service call with this name)
--    Users of a role that has it are the only ones offered as staff of a service case: in the wizard, the case
--    window, the "Assigned to" filter and the "Who went" list of Finish visit. Until now this list was fixed in the
--    code to the roles Administrator and APPSTV Admin, so those two roles get it and nothing changes at deploy.
--    Untick it in Role Management for a role whose users never go on site.
-- 2. appstv_case_staff_set  (Manage case staff) adds / removes staff on an existing case (on a "one case per TV"
--    case: the main case and all its TV cases). Given to Administrator and APPSTV Admin.
--
-- Feature group "Service Cases", audience user. Safe to re-run (NOT EXISTS); written for MySQL 8. Rows are found by
-- name, never by ID. Granted by role name on purpose (not the template's "whole feature" rule): who goes on site
-- is a decision about people, not about having every other case permission.

-- ── 1. Visit technician ──────────────────────────────────────────────────────────────────────────────────────
INSERT INTO functionalities ( functionality_name, alias, functionality_description, functionality_type, is_page, is_a_content,
                              plugin_id, feature_group, audience )
SELECT 'appstv_case_visit_technician', 'Service Cases - visit technician',
       'Users of this role can be assigned to service cases and sent on site visits', 'READ', 0, 1,
       existing.plugin_id, 'Service Cases', 'user'
FROM ( SELECT plugin_id FROM functionalities WHERE functionality_name = 'appstv_case_update' LIMIT 1 ) existing
WHERE NOT EXISTS ( SELECT 1 FROM functionalities f WHERE f.functionality_name = 'appstv_case_visit_technician' );

-- ── 2. Manage case staff ─────────────────────────────────────────────────────────────────────────────────────
INSERT INTO functionalities ( functionality_name, alias, functionality_description, functionality_type, is_page, is_a_content,
                              plugin_id, feature_group, audience )
SELECT 'appstv_case_staff_set', 'Service Cases - manage case staff',
       'Adds or removes the staff of an existing case (a main case together with all its TV cases)', 'UPDATE', 0, 1,
       existing.plugin_id, 'Service Cases', 'user'
FROM ( SELECT plugin_id FROM functionalities WHERE functionality_name = 'appstv_case_update' LIMIT 1 ) existing
WHERE NOT EXISTS ( SELECT 1 FROM functionalities f WHERE f.functionality_name = 'appstv_case_staff_set' );

-- ── Grants: Administrator and APPSTV Admin ───────────────────────────────────────────────────────────────────
INSERT INTO roles_functionalities ( role_id, functionality_id )
SELECT r.role_id, f.functionality_id
FROM roles r
JOIN functionalities f ON f.functionality_name IN ( 'appstv_case_visit_technician', 'appstv_case_staff_set' )
WHERE r.role_name IN ( 'Administrator', 'APPSTV Admin' )
  AND NOT EXISTS ( SELECT 1 FROM roles_functionalities x WHERE x.role_id = r.role_id AND x.functionality_id = f.functionality_id );
