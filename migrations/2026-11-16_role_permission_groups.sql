-- Role permissions: feature groups and audience for every functionality (APPSTV CRM 1.0.15, same as gen3 1.0.107)
--
-- 1. functionalities.feature_group   the feature a functionality belongs to inside its plugin ("Carousel", "Home Screen",
--                                    "Device Commands" ...): a page and the calls it makes share one group, so the
--                                    Role editor can show "Carousel 9 / 12" and warn about a page without its calls
-- 2. functionalities.audience        user    = used by people in the admin panel
--                                    system  = called by machines only (TVs, hotel PC / cloud server, cron, setup tool,
--                                              PMS interface): kept apart in the Role editor, with a warning
--                                    public  = called before signing in (sign up, forgot password, app sign-in)
-- 3. Functionality scodezy_get_role_permissions (whole permission tree of a role in one call), granted to every role
--    that may read a role (scodezy_get_role)
-- 4. Backfill of feature_group / audience for the existing functionalities, by name
--
-- Safe to re-run (information_schema check / NOT EXISTS / only empty groups are filled); written for MySQL 8.
-- Rows are found by name, never by ID. New functionalities from now on set feature_group and audience themselves
-- (see migrations/_template_new_functionality.sql).

-- ── 1. + 2. Columns ──────────────────────────────────────────────────────────────────────────────────────────
SET @has_col = ( SELECT COUNT(*) FROM information_schema.columns
                 WHERE table_schema = DATABASE() AND table_name = 'functionalities' AND column_name = 'feature_group' );
SET @sql = IF( @has_col = 0,
    'ALTER TABLE functionalities ADD COLUMN feature_group VARCHAR(100) NOT NULL DEFAULT '''' COMMENT ''Feature inside the plugin, shown in the Role editor (1.0.107)'' AFTER plugin_id',
    'SELECT 1' );
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_col = ( SELECT COUNT(*) FROM information_schema.columns
                 WHERE table_schema = DATABASE() AND table_name = 'functionalities' AND column_name = 'audience' );
SET @sql = IF( @has_col = 0,
    'ALTER TABLE functionalities ADD COLUMN audience VARCHAR(10) NOT NULL DEFAULT ''user'' COMMENT ''user / system / public (1.0.107)'' AFTER feature_group',
    'SELECT 1' );
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_idx = ( SELECT COUNT(*) FROM information_schema.statistics
                 WHERE table_schema = DATABASE() AND table_name = 'functionalities' AND index_name = 'idx_functionality_group' );
SET @sql = IF( @has_idx = 0,
    'ALTER TABLE functionalities ADD KEY idx_functionality_group ( plugin_id, feature_group )',
    'SELECT 1' );
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ── 3. scodezy_get_role_permissions (plugin of the Role functions) ───────────────────────────────────────────
INSERT INTO functionalities ( functionality_name, alias, functionality_description, functionality_type, is_page, is_a_content, plugin_id )
SELECT 'scodezy_get_role_permissions', 'Roles - permission tree',
       'Every functionality grouped by plugin and feature, with the ones granted to the role, for the Role editor', 'READ', 0, 1, existing.plugin_id
FROM ( SELECT plugin_id FROM functionalities WHERE functionality_name = 'scodezy_get_role' LIMIT 1 ) existing
WHERE NOT EXISTS ( SELECT 1 FROM functionalities f WHERE f.functionality_name = 'scodezy_get_role_permissions' );

INSERT INTO roles_functionalities ( role_id, functionality_id )
SELECT owners.role_id, f.functionality_id
FROM ( SELECT DISTINCT rf.role_id FROM roles_functionalities rf JOIN functionalities sf ON sf.functionality_id = rf.functionality_id
       WHERE sf.functionality_name = 'scodezy_get_role' ) owners
JOIN functionalities f ON f.functionality_name = 'scodezy_get_role_permissions'
WHERE NOT EXISTS ( SELECT 1 FROM roles_functionalities x WHERE x.role_id = owners.role_id AND x.functionality_id = f.functionality_id );

-- ── 4. Backfill (names of this project; a missing name does nothing) ───────────────────────────────────────
UPDATE functionalities SET feature_group = 'API Apps' WHERE feature_group = '' AND functionality_name IN (
    'scodezy_create_app', 'scodezy_authenticate_app'
);
UPDATE functionalities SET feature_group = 'Admin Panel' WHERE feature_group = '' AND functionality_name IN (
    'adminpanel'
);
UPDATE functionalities SET feature_group = 'CRM & QuickBooks' WHERE feature_group = '' AND functionality_name IN (
    'appstv_crm', 'authenticate_quickbooks_app', 'generate_quickbooks_tokens'
);
UPDATE functionalities SET feature_group = 'Customers' WHERE feature_group = '' AND functionality_name IN (
    'create_customer'
);
UPDATE functionalities SET feature_group = 'Functionalities' WHERE feature_group = '' AND functionality_name IN (
    'manage_functionalities', 'scodezy_get_functionalities', 'scodezy_delete_functionality',
    'scodezy_create_functionality', 'scodezy_get_functionality', 'scodezy_update_functionality',
    'scodezy_delete_functionalities', 'scodezy_export_functionalities', 'scodezy_import_functionalities'
);
UPDATE functionalities SET feature_group = 'Legacy privileges' WHERE feature_group = '' AND functionality_name IN (
    'create_privilege_page', 'view_privileges_page', 'view_roles_page', 'create_privilege', 'view_privileges',
    'create_role_page', 'create_role', 'update_role', 'delete_role'
);
UPDATE functionalities SET feature_group = 'My Profile' WHERE feature_group = '' AND functionality_name IN (
    'scodezy_get_self_profile_settings', 'scodezy_update_self_profile_details', 'scodezy_update_self_email',
    'scodezy_verify_self_email_update', 'scodezy_update_self_phone', 'scodezy_update_self_phone_request_otp',
    'scodezy_verify_update_self_phone_otp', 'scodezy_update_self_password', 'profile', 'scodezy_get_my_profile',
    'scodezy_set_self_avatar', 'scodezy_set_self_display_name', 'scodezy_resend_self_email_verification',
    'scodezy_cancel_self_email_update', 'scodezy_confirm_self_email_code'
);
UPDATE functionalities SET feature_group = 'Orders' WHERE feature_group = '' AND functionality_name IN (
    'manage_orders', 'get_crm_orders', 'get_crm_order_information', 'see_crm_test_orders',
    'get_prepare_order_information', 'create_prepare_order', 'delete_prepared_but_undelivered_orders',
    'get_prepared_order_summary', 'update_prepare_order', 'get_confirm_delivery_information',
    'update_confirm_delivery'
);
UPDATE functionalities SET feature_group = 'Pages' WHERE feature_group = '' AND functionality_name IN (
    'scodezy_get_pages', 'scodezy_get_page', 'scodezy_update_page_visible_status', 'scodezy_create_page',
    'scodezy_delete_page', 'scodezy_delete_pages', 'scodezy_update_page', 'scodezy_get_parent_pages',
    'scodezy_get_child_pages', 'scodezy_update_page_sequence', 'scodezy_get_page_functionalities',
    'scodezy_export_pages', 'scodezy_import_pages', 'manage_pages'
);
UPDATE functionalities SET feature_group = 'Plugins' WHERE feature_group = '' AND functionality_name IN (
    'scodezy_get_all_plugin_information', 'scodezy_create_plugin', 'scodezy_delete_plugin', 'scodezy_get_plugin',
    'scodezy_update_plugin', 'scodezy_export_plugin', 'scodezy_import_plugin', 'manage_plugins'
);
UPDATE functionalities SET feature_group = 'Products' WHERE feature_group = '' AND functionality_name IN (
    'manage_products', 'get_crm_products', 'create_crm_product', 'get_crm_product_inventory',
    'update_crm_product_inventory', 'create_product_serial_number'
);
UPDATE functionalities SET feature_group = 'Record Sale' WHERE feature_group = '' AND functionality_name IN (
    'record_sale', 'appstv_sale_search_customers', 'appstv_sale_check_invoice', 'appstv_sale_check_serials',
    'appstv_sale_read_serial_file', 'appstv_sale_get_draft', 'appstv_sale_save_draft', 'appstv_sale_discard_draft',
    'appstv_sale_save'
);
UPDATE functionalities SET feature_group = 'Roles' WHERE feature_group = '' AND functionality_name IN (
    'scodezy_get_roles', 'scodezy_create_role', 'scodezy_delete_role', 'scodezy_get_role', 'scodezy_update_role',
    'manage_roles', 'scodezy_get_role_permissions'
);
UPDATE functionalities SET feature_group = 'Service Cases' WHERE feature_group = '' AND functionality_name IN (
    'service_cases', 'appstv_case_staff', 'appstv_case_find_tv', 'appstv_case_list', 'appstv_case_get',
    'appstv_case_create', 'appstv_case_update', 'appstv_case_add_note', 'appstv_case_set_tv', 'appstv_case_close'
);
UPDATE functionalities SET feature_group = 'Shopify' WHERE feature_group = '' AND functionality_name IN (
    'generate_shopify_app_token', 'install_shopify_app', 'create_shopify_webhook', 'update_shopify_webhook',
    'shopify_webhook_response', 'delete_shopify_webhook', 'test_hmac', 'simulate_shopify_webhook_response'
);
UPDATE functionalities SET feature_group = 'Sign Up & Password' WHERE feature_group = '' AND functionality_name IN (
    'scodezy_forgot_password', 'scodezy_sign_up', 'scodezy_is_user_id_available', 'scodezy_activate_new_account',
    'scodezy_update_password_on_reset_page'
);
UPDATE functionalities SET feature_group = 'Test entries' WHERE feature_group = '' AND functionality_name IN (
    'test7', 'test1', 'test2', 'e', 'test3', 'test4', 'test5', 'test6'
);
UPDATE functionalities SET feature_group = 'Users' WHERE feature_group = '' AND functionality_name IN (
    'scodezy_get_users', 'scodezy_create_user', 'scodezy_delete_user', 'scodezy_delete_users', 'scodezy_get_user',
    'scodezy_reset_user_password', 'scodezy_update_user', 'manage_users'
);
UPDATE functionalities SET feature_group = 'Warranty' WHERE feature_group = '' AND functionality_name IN (
    'appstv_decode_qr_barcode', 'appstv_read_sn_info', 'manage_appstv_warranty', 'appstv_record_service',
    'appstv_add_extended_warranty'
);
UPDATE functionalities SET feature_group = 'Website Configuration' WHERE feature_group = '' AND functionality_name IN (
    'website_configuration', 'scodezy_get_website_configuration', 'scodezy_save_website_configuration',
    'scodezy_upload_website_image'
);

UPDATE functionalities SET audience = 'system' WHERE audience = 'user' AND functionality_name IN (
    'shopify_webhook_response'
);

UPDATE functionalities SET audience = 'public' WHERE audience = 'user' AND functionality_name IN (
    'scodezy_forgot_password', 'scodezy_sign_up', 'scodezy_is_user_id_available', 'scodezy_activate_new_account',
    'scodezy_update_password_on_reset_page', 'scodezy_authenticate_app'
);

-- ── 5. Fallback for anything not named above ─────────────────────────────────────────────────────────────────
-- The alias text before " - " ("Carousel - save" -> "Carousel"), then the plugin's name, then "Legacy (no plugin)";
-- characters a feature group may not have are dropped
UPDATE functionalities
SET feature_group = LEFT( TRIM( REGEXP_REPLACE( SUBSTRING_INDEX( alias, ' - ', 1 ), '[^A-Za-z0-9 &/().,''-]', '' ) ), 100 )
WHERE feature_group = '' AND alias LIKE '% - %';

UPDATE functionalities f
JOIN plugins p ON p.plugin_id = f.plugin_id
SET f.feature_group = LEFT( TRIM( REGEXP_REPLACE( p.plugin_alias, '[^A-Za-z0-9 &/().,''-]', '' ) ), 100 )
WHERE f.feature_group = '';

UPDATE functionalities SET feature_group = 'Legacy (no plugin)' WHERE feature_group = '';
