-- UNDO of migrations/2026-11-23_service_menu.sql (APPSTV CRM 1.0.36): Warranty and Service Cases go back under CRM,
-- the Service heading and its functionality are removed, Technician gets appstv_crm back. Safe to re-run; MySQL 8.

SET @crm_page = ( SELECT page_id FROM pages WHERE page_name = 'appstv_crm' LIMIT 1 );

UPDATE pages SET parent_id = @crm_page, hierarchy = 2, page_sequence = 1
WHERE page_name IN ( 'manage_appstv_warranty', 'service_cases' ) AND @crm_page IS NOT NULL;

DELETE FROM pages WHERE page_name = 'appstv_service';

DELETE rf FROM roles_functionalities rf
JOIN functionalities f ON f.functionality_id = rf.functionality_id
WHERE f.functionality_name = 'appstv_service';

DELETE FROM functionalities WHERE functionality_name = 'appstv_service';

INSERT INTO roles_functionalities ( role_id, functionality_id )
SELECT r.role_id, f.functionality_id
FROM roles r JOIN functionalities f ON f.functionality_name = 'appstv_crm'
WHERE r.role_slug = 'technician'
  AND NOT EXISTS ( SELECT 1 FROM roles_functionalities x WHERE x.role_id = r.role_id AND x.functionality_id = f.functionality_id );
