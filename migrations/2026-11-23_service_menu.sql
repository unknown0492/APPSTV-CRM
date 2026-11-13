-- Left menu: new top-level heading "Service" holding Warranty and Service Cases (APPSTV CRM 1.0.36)
--
-- 1. Functionality appstv_service (ACCESS, menu heading only): the left menu shows a heading only to roles holding
--    its functionality, the same way "CRM" works with appstv_crm
-- 2. Page appstv_service: hierarchy 1, sequence 2 (after CRM and Administrator, before Site Configuration)
-- 3. Warranty (manage_appstv_warranty) and Service Cases (service_cases) move from CRM to Service, in that order
-- 4. appstv_service is granted to every role holding either page (Administrator, APPSTV Admin, Technician)
-- 5. Technician loses appstv_crm (its CRM heading would be empty), only while it holds none of the CRM pages
--    (Orders, Products, Record Sale)
--
-- Safe to re-run (NOT EXISTS checks, updates by name); written for MySQL 8. Rows are found by name, never by ID.
-- Undo: migrations/undo/2026-11-23_service_menu_undo.sql

-- ── 1. Functionality ─────────────────────────────────────────────────────────────────────────────────────────
INSERT INTO functionalities ( functionality_name, alias, functionality_description, functionality_type, is_page, is_a_content,
                              plugin_id, feature_group, audience )
SELECT 'appstv_service', 'Service menu', 'Shows the Service heading of the left menu (Warranty, Service Cases)', 'ACCESS', 1, 0,
       existing.plugin_id, 'Service menu', 'user'
FROM ( SELECT plugin_id FROM functionalities WHERE functionality_name = 'manage_appstv_warranty' LIMIT 1 ) existing
WHERE NOT EXISTS ( SELECT 1 FROM functionalities f WHERE f.functionality_name = 'appstv_service' );

-- ── 2. Page (menu heading) ───────────────────────────────────────────────────────────────────────────────────
INSERT INTO pages
    ( page_name, parent_id, hierarchy, page_sequence, icon, visible, page_title, functionality_id,
      title, description, tags, image, content, plugin_id )
SELECT 'appstv_service', 0, 1, 2, 'fa-solid fa-screwdriver-wrench', 1, 'Service', f.functionality_id,
       'Service', 'Warranty and service cases', '', '', '', f.plugin_id
FROM functionalities f
WHERE f.functionality_name = 'appstv_service'
  AND NOT EXISTS ( SELECT 1 FROM ( SELECT page_name FROM pages ) existing WHERE existing.page_name = 'appstv_service' );

-- ── 3. Move Warranty and Service Cases under Service ─────────────────────────────────────────────────────────
SET @svc_page = ( SELECT page_id FROM pages WHERE page_name = 'appstv_service' LIMIT 1 );

UPDATE pages SET parent_id = @svc_page, hierarchy = 2, page_sequence = 1
WHERE page_name = 'manage_appstv_warranty' AND @svc_page IS NOT NULL;

UPDATE pages SET parent_id = @svc_page, hierarchy = 2, page_sequence = 2
WHERE page_name = 'service_cases' AND @svc_page IS NOT NULL;

-- ── 4. Grants: every role holding Warranty or Service Cases ──────────────────────────────────────────────────
INSERT INTO roles_functionalities ( role_id, functionality_id )
SELECT owners.role_id, f.functionality_id
FROM ( SELECT DISTINCT rf.role_id FROM roles_functionalities rf
       JOIN functionalities pf ON pf.functionality_id = rf.functionality_id
       WHERE pf.functionality_name IN ( 'manage_appstv_warranty', 'service_cases' ) ) owners
JOIN functionalities f ON f.functionality_name = 'appstv_service'
WHERE NOT EXISTS ( SELECT 1 FROM roles_functionalities x WHERE x.role_id = owners.role_id AND x.functionality_id = f.functionality_id );

-- ── 5. Technician: drop the CRM heading when it has no CRM page ──────────────────────────────────────────────
SET @tech_role = ( SELECT role_id FROM roles WHERE role_slug = 'technician' LIMIT 1 );
SET @tech_crm_pages = ( SELECT COUNT(*) FROM roles_functionalities rf
                        JOIN functionalities f ON f.functionality_id = rf.functionality_id
                        WHERE rf.role_id = @tech_role AND f.functionality_name IN ( 'manage_orders', 'manage_products', 'record_sale' ) );

DELETE rf FROM roles_functionalities rf
JOIN functionalities f ON f.functionality_id = rf.functionality_id
WHERE rf.role_id = @tech_role AND f.functionality_name = 'appstv_crm' AND @tech_crm_pages = 0;
