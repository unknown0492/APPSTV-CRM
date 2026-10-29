-- My Profile redesign (Scodezy base, ported from gen3 1.0.61)
--
-- New actions of My Profile: read the profile, choose the picture, choose the name shown next to it, and resend /
-- cancel / confirm-by-code an email change. Each is granted to every role that can already edit its own profile
-- (has scodezy_update_self_profile_details), so nobody gains or loses access to My Profile.
-- Website Configuration > Profiles settings live in configurations and need no rows (defaults apply).
--
-- Safe to re-run (inserts skip rows that exist); written for MySQL 8. Rows are found by name, never by ID.

INSERT INTO functionalities
    ( functionality_name, alias, functionality_description, functionality_type, is_page, is_a_content, plugin_id )
SELECT n.name, n.alias, n.description, n.type, 0, 1, p.plugin_id
FROM (
          SELECT 'scodezy_get_my_profile' AS name, 'My Profile - read' AS alias, 'Reads the signed-in user''s profile' AS description, 'READ' AS type
    UNION SELECT 'scodezy_set_self_avatar', 'My Profile - picture', 'Chooses the own profile picture (initials, avatar, upload)', 'UPDATE'
    UNION SELECT 'scodezy_set_self_display_name', 'My Profile - display name', 'Chooses what appears next to the own picture', 'UPDATE'
    UNION SELECT 'scodezy_resend_self_email_verification', 'My Profile - resend email confirmation', 'Sends the email change link / code again', 'UPDATE'
    UNION SELECT 'scodezy_cancel_self_email_update', 'My Profile - cancel email change', 'Cancels the pending email change', 'UPDATE'
    UNION SELECT 'scodezy_confirm_self_email_code', 'My Profile - confirm email code', 'Confirms an email change with the 6-digit code', 'UPDATE'
) n
JOIN plugins p ON p.plugin_name = 'user_management'
WHERE NOT EXISTS ( SELECT 1 FROM functionalities f WHERE f.functionality_name = n.name );

INSERT INTO roles_functionalities ( role_id, functionality_id )
SELECT owners.role_id, f.functionality_id
FROM ( SELECT DISTINCT rf.role_id
       FROM roles_functionalities rf
       JOIN functionalities pf ON pf.functionality_id = rf.functionality_id
       WHERE pf.functionality_name = 'scodezy_update_self_profile_details' ) owners
JOIN functionalities f ON f.functionality_name IN ( 'scodezy_get_my_profile', 'scodezy_set_self_avatar', 'scodezy_set_self_display_name',
                                                    'scodezy_resend_self_email_verification', 'scodezy_cancel_self_email_update',
                                                    'scodezy_confirm_self_email_code' )
WHERE NOT EXISTS ( SELECT 1 FROM roles_functionalities x WHERE x.role_id = owners.role_id AND x.functionality_id = f.functionality_id );
