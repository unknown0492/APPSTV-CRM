-- Record Sale page (appstv_crm / myappstv.com 1.0.6)
--
-- 1. New table sale_drafts: one unfinished Record Sale form per user (saved automatically while the form is filled,
--    deleted when the sale is saved or the user discards it).
-- 2. New page "Record Sale" (record_sale) in the Orders plugin, under the CRM menu, and its webservice actions.
--    Granted to Administrator and APPSTV Admin.
-- 3. appstv_decode_qr_barcode (photo decoding on the server, Warranty plugin) is also granted to APPSTV Admin, for the
--    Take photo button of the Record Sale scanner.
-- 4. The Link Serial Number modal is removed in 1.0.6 (replaced by Record Sale): its 4 actions are unregistered.
--
-- Safe to re-run (CREATE TABLE IF NOT EXISTS, every insert skips rows that exist); written for MySQL 8. Rows are found
-- by name, never by ID (IDs differ between localhost and live).

CREATE TABLE IF NOT EXISTS sale_drafts (
    id           INT NOT NULL AUTO_INCREMENT,
    user_id      VARCHAR(100) NOT NULL COMMENT 'users.user_id of the person filling the form',
    draft_data   MEDIUMTEXT NOT NULL COMMENT 'The whole form as JSON',
    serial_count INT NOT NULL DEFAULT 0 COMMENT 'Number of serial numbers in the draft (shown when offering to continue)',
    created_on   VARCHAR(50) NOT NULL COMMENT 'ISO 8601',
    updated_on   VARCHAR(50) NOT NULL COMMENT 'ISO 8601',
    PRIMARY KEY ( id ),
    UNIQUE KEY uq_sale_drafts_user ( user_id )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Page functionality and webservice actions (Orders plugin)
INSERT INTO functionalities
    ( functionality_name, alias, functionality_description, functionality_type, is_page, is_a_content, plugin_id )
SELECT n.name, n.alias, n.description, n.type, n.is_page, n.is_a_content, p.plugin_id
FROM (
          SELECT 'record_sale' AS name, 'Record Sale' AS alias,
                 'Record Sale page: customer, invoice and the serial numbers of the TVs sold, with their warranty' AS description,
                 'ACCESS' AS type, 1 AS is_page, 0 AS is_a_content
    UNION SELECT 'appstv_sale_search_customers', 'Record Sale - search customers',
                 'Searches customers by company, name, email, mobile or customer ID', 'READ', 0, 1
    UNION SELECT 'appstv_sale_check_invoice', 'Record Sale - check invoice',
                 'Looks up an invoice number: its customer and how many TVs it holds', 'READ', 0, 1
    UNION SELECT 'appstv_sale_check_serials', 'Record Sale - check serial numbers',
                 'Reads the existing records of serial numbers (new, in stock, already sold)', 'READ', 0, 1
    UNION SELECT 'appstv_sale_read_serial_file', 'Record Sale - read serial number file',
                 'Reads serial numbers from an uploaded .xlsx, .csv or .txt file', 'READ', 0, 1
    UNION SELECT 'appstv_sale_get_draft', 'Record Sale - get draft',
                 'Returns the unfinished Record Sale form of the signed-in user', 'READ', 0, 1
    UNION SELECT 'appstv_sale_save_draft', 'Record Sale - save draft',
                 'Saves the unfinished Record Sale form of the signed-in user', 'UPDATE', 0, 1
    UNION SELECT 'appstv_sale_discard_draft', 'Record Sale - discard draft',
                 'Deletes the unfinished Record Sale form of the signed-in user', 'DELETE', 0, 1
    UNION SELECT 'appstv_sale_save', 'Record Sale - save sale',
                 'Saves a sale: customer, invoice, TVs, invoice links and warranties in one transaction', 'CREATE', 0, 1
) n
JOIN plugins p ON p.plugin_name = 'appstv_crm_order'
WHERE NOT EXISTS ( SELECT 1 FROM functionalities f WHERE f.functionality_name = n.name );

-- Menu page under CRM (next to Orders, Products, Warranty)
INSERT INTO pages
    ( page_name, parent_id, hierarchy, page_sequence, icon, visible, page_title, functionality_id,
      title, description, tags, image, content, plugin_id )
SELECT 'record_sale', parent.page_id, 2, 1, 'fa-solid fa-file-invoice', 1, 'Record Sale', f.functionality_id,
       'Record Sale', 'Record the TVs sold to a customer: invoice, serial numbers and warranty', '', '', '', pl.plugin_id
FROM ( SELECT page_id FROM pages WHERE page_name = 'appstv_crm' ) parent
JOIN functionalities f ON f.functionality_name = 'record_sale'
JOIN plugins pl ON pl.plugin_name = 'appstv_crm_order'
WHERE NOT EXISTS ( SELECT 1 FROM ( SELECT page_name FROM pages ) existing WHERE existing.page_name = 'record_sale' );

-- Grants: Administrator and APPSTV Admin
INSERT INTO roles_functionalities ( role_id, functionality_id )
SELECT r.role_id, f.functionality_id
FROM roles r
JOIN functionalities f ON f.functionality_name IN ( 'record_sale', 'appstv_sale_search_customers', 'appstv_sale_check_invoice',
                                                    'appstv_sale_check_serials', 'appstv_sale_read_serial_file', 'appstv_sale_get_draft',
                                                    'appstv_sale_save_draft', 'appstv_sale_discard_draft', 'appstv_sale_save',
                                                    'appstv_decode_qr_barcode' )
WHERE r.role_name IN ( 'Administrator', 'APPSTV Admin' )
  AND NOT EXISTS ( SELECT 1 FROM roles_functionalities rf WHERE rf.role_id = r.role_id AND rf.functionality_id = f.functionality_id );

-- Link Serial Number modal removed: unregister its actions (grants first, then the actions)
DELETE rf FROM roles_functionalities rf
JOIN functionalities f ON f.functionality_id = rf.functionality_id
WHERE f.functionality_name IN ( 'appstv_search_customers', 'appstv_verify_serial_number',
                                'appstv_read_serial_number_from_file', 'appstv_link_serial_number_to_customer' );

DELETE FROM functionalities
WHERE functionality_name IN ( 'appstv_search_customers', 'appstv_verify_serial_number',
                              'appstv_read_serial_number_from_file', 'appstv_link_serial_number_to_customer' );
