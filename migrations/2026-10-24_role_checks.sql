-- Role checks on more webservice actions (appstv_crm / myappstv.com 1.0.1)
--
-- 1. 1.0.1 adds checkAuthorizationForFunction() to actions that had no role check. Four of them are used by admin
--    pages but were never granted to any role (they only worked because they were unguarded):
--      delete_plugin_name    Plugins page
--      is_user_id_available  Create user page
--      reset_password        Manage users page (send a password reset to a user)
--      upload_logo_image     Site configuration page
--    They are granted to Administrator so those pages keep working.
--
-- 2. QuickBooks connect: authenticate_quickbooks_app (start) and generate_quickbooks_tokens (Intuit's OAuth callback)
--    were public - anyone could call the callback and overwrite the stored QuickBooks tokens. 1.0.1 takes them off the
--    public list, so they must now be registered functionalities; they are registered here and granted to
--    Administrator only (connect while signed in as Administrator). The two QuickBooks test actions
--    (test_quickbooks_api_call, test_sparse_update_invoice) are NOT registered, so nobody can call them.
--    refresh_tokens_before_they_expire_quickbooks (cron) stays public.
--
-- Every other action guarded in 1.0.1 is already granted to the roles that use it.
-- Safe to re-run (skips rows and grants that exist, and names that are not registered); written for MySQL 8.

INSERT INTO functionalities
    ( functionality_name, alias, functionality_description, functionality_type, is_page, is_a_content, plugin_id )
SELECT n.name, n.alias, n.description, n.type, 0, 1, p.plugin_id
FROM (
          SELECT 'authenticate_quickbooks_app' AS name, 'QuickBooks - connect' AS alias,
                 'Starts the QuickBooks sign-in (OAuth) to connect the CRM to the QuickBooks company' AS description, 'UPDATE' AS type
    UNION SELECT 'generate_quickbooks_tokens', 'QuickBooks - store tokens',
                 'QuickBooks OAuth callback: stores the QuickBooks access / refresh tokens', 'UPDATE'
) n
JOIN plugins p ON p.plugin_name = 'appstv_crm_apps'
WHERE NOT EXISTS ( SELECT 1 FROM functionalities f WHERE f.functionality_name = n.name );

INSERT INTO roles_functionalities ( role_id, functionality_id )
SELECT r.role_id, f.functionality_id
FROM roles r
JOIN functionalities f ON f.functionality_name IN ( 'delete_plugin_name', 'is_user_id_available', 'reset_password', 'upload_logo_image',
                                                    'authenticate_quickbooks_app', 'generate_quickbooks_tokens' )
WHERE r.role_name = 'Administrator'
  AND NOT EXISTS ( SELECT 1 FROM roles_functionalities x WHERE x.role_id = r.role_id AND x.functionality_id = f.functionality_id );
