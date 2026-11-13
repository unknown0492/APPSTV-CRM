-- UNDO of migrations/2026-11-24_user_id_check_grant.sql (APPSTV CRM 1.0.37): no role holds
-- scodezy_is_user_id_available again (as before 1.0.37). Safe to re-run; MySQL 8.

DELETE rf FROM roles_functionalities rf
JOIN functionalities f ON f.functionality_id = rf.functionality_id
WHERE f.functionality_name = 'scodezy_is_user_id_available';
