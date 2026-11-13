-- Create User modal: "User ID" availability check refused for everyone (APPSTV CRM 1.0.37)
--
-- scodezy_is_user_id_available calls checkAuthorizationForFunction(), so only roles holding it may call it, but no
-- role held it: typing a User ID in the Create User modal showed "no privileges", even for Administrator.
-- Fix: grant it to every role that can create users (holds scodezy_create_user); on live that is Administrator.
--
-- Safe to re-run (NOT EXISTS); written for MySQL 8. Rows are found by name, never by ID.
-- Undo: migrations/undo/2026-11-24_user_id_check_grant_undo.sql

INSERT INTO roles_functionalities ( role_id, functionality_id )
SELECT owners.role_id, f.functionality_id
FROM ( SELECT DISTINCT rf.role_id FROM roles_functionalities rf
       JOIN functionalities cf ON cf.functionality_id = rf.functionality_id
       WHERE cf.functionality_name = 'scodezy_create_user' ) owners
JOIN functionalities f ON f.functionality_name = 'scodezy_is_user_id_available'
WHERE NOT EXISTS ( SELECT 1 FROM roles_functionalities x WHERE x.role_id = owners.role_id AND x.functionality_id = f.functionality_id );
