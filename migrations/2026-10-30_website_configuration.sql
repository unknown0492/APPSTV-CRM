-- Website Configuration page (Scodezy base feature, ported from gen3 1.0.56-1.0.62)
--
-- Administrator > Website Configuration (plugin website_configuration): identity, logos & favicon, sign-in options,
-- maintenance, social sign-in keys (future scope). Administrator only.
-- Also removes the old "Site Configuration" menu row: it points to a functionality that no longer exists and to a page
-- file that is not in the plugin (the old code folder website_configuration1 is deleted in this release).
--
-- Safe to re-run (inserts skip rows that exist); written for MySQL 8. Rows are found by name, never by ID.

INSERT INTO functionalities
    ( functionality_name, alias, functionality_description, functionality_type, is_page, is_a_content, plugin_id )
SELECT n.name, n.alias, n.description, n.type, n.is_page, n.is_a_content, p.plugin_id
FROM (
          SELECT 'website_configuration' AS name, 'Website Configuration' AS alias, 'Website Configuration page' AS description,
                 'ACCESS' AS type, 1 AS is_page, 0 AS is_a_content
    UNION SELECT 'scodezy_get_website_configuration', 'Website Configuration - read', 'Reads the website settings (secrets only as saved flags)', 'READ', 0, 1
    UNION SELECT 'scodezy_save_website_configuration', 'Website Configuration - save', 'Saves one section of the website settings', 'UPDATE', 0, 1
    UNION SELECT 'scodezy_upload_website_image', 'Website Configuration - logos', 'Uploads or sets a logo / favicon', 'UPDATE', 0, 1
) n
JOIN plugins p ON p.plugin_name = 'website_configuration'
WHERE NOT EXISTS ( SELECT 1 FROM functionalities f WHERE f.functionality_name = n.name );

-- Page under Administrator, after Logs
INSERT INTO pages
    ( page_name, parent_id, hierarchy, page_sequence, icon, visible, page_title, functionality_id,
      title, description, tags, image, content, plugin_id )
SELECT 'website_configuration', parent.page_id, 2, 9, 'ki-outline ki-setting-2 fs-2', 1, 'Website Configuration', f.functionality_id,
       'Website Configuration', 'Name, branding, sign-in options and maintenance settings of this website', '', '', '', pl.plugin_id
FROM ( SELECT page_id FROM pages WHERE page_name = 'administrator' ) parent
JOIN functionalities f ON f.functionality_name = 'website_configuration'
JOIN plugins pl ON pl.plugin_name = 'website_configuration'
WHERE NOT EXISTS ( SELECT 1 FROM ( SELECT page_name FROM pages ) existing WHERE existing.page_name = 'website_configuration' );

-- Administrator only
INSERT INTO roles_functionalities ( role_id, functionality_id )
SELECT r.role_id, f.functionality_id
FROM roles r
JOIN functionalities f ON f.functionality_name IN ( 'website_configuration', 'scodezy_get_website_configuration',
                                                    'scodezy_save_website_configuration', 'scodezy_upload_website_image' )
WHERE r.role_name = 'Administrator'
  AND NOT EXISTS ( SELECT 1 FROM roles_functionalities rf WHERE rf.role_id = r.role_id AND rf.functionality_id = f.functionality_id );

-- Text that was fixed in includes/*.php, now editable (same values as before)
INSERT INTO configurations ( config_key, config_value )
SELECT n.k, n.v
FROM (
          SELECT 'login_page_tagline' AS k, 'Your support system for APPSTV Products' AS v
    UNION SELECT 'copyright_year', '2024'
    UNION SELECT 'copyright_owner', 'APPSTV'
    UNION SELECT 'copyright_url', 'https://appstv.com.sg'
) n
WHERE NOT EXISTS ( SELECT 1 FROM ( SELECT config_key FROM configurations ) c WHERE c.config_key = n.k );

-- Old "Site Configuration" menu row (its functionality does not exist; no child pages)
DELETE FROM pages
WHERE page_name = 'site_config_page'
  AND NOT EXISTS ( SELECT 1 FROM functionalities f WHERE f.functionality_id = pages.functionality_id )
  AND NOT EXISTS ( SELECT 1 FROM ( SELECT parent_id FROM pages ) children
                   WHERE children.parent_id = ( SELECT page_id FROM ( SELECT page_id, page_name FROM pages ) me WHERE me.page_name = 'site_config_page' ) );
