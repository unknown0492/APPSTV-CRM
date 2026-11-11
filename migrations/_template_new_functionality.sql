-- TEMPLATE (not a migration: never run this file). Copy it into a new dated migration when adding a functionality.
-- (gen3 1.0.107)
--
-- Every new functionality states:
--   feature_group   the feature it belongs to inside its plugin, exactly as the other rows of that feature spell it
--                   ("Carousel", "Home Screen" ...) - the Role editor groups and counts by it
--   audience        'user' (people in the admin panel), 'system' (TVs, hotel PC, cron, setup tool, PMS interface)
--                   or 'public' (before signing in)
--
-- Grant rule: a new 'user' functionality goes to every role that already has ALL the other user functionalities of
-- its feature group (= roles with the full feature keep the full feature). Roles with part of the feature are left
-- for an admin to decide in Role Management. A 'system' functionality is granted by role name instead
-- (esTV CRON, Android Client Proxy, esTV Cloud Server ...).
--
-- Replace: my_new_function, 'Feature - action', the description, 'UPDATE', 'Carousel', 'user',
--          and estv_save_carousel (any existing functionality of the same plugin).

INSERT INTO functionalities ( functionality_name, alias, functionality_description, functionality_type, is_page, is_a_content,
                              plugin_id, feature_group, audience )
SELECT 'my_new_function', 'Feature - action', 'What it does, in one sentence', 'UPDATE', 0, 1,
       existing.plugin_id, 'Carousel', 'user'
FROM ( SELECT plugin_id FROM functionalities WHERE functionality_name = 'estv_save_carousel' LIMIT 1 ) existing
WHERE NOT EXISTS ( SELECT 1 FROM functionalities f WHERE f.functionality_name = 'my_new_function' );

-- 'user' functionality: every role that has the whole feature group (all its other user functionalities)
SET @nf_id     = ( SELECT functionality_id FROM functionalities WHERE functionality_name = 'my_new_function' LIMIT 1 );
SET @nf_plugin = ( SELECT plugin_id FROM functionalities WHERE functionality_id = @nf_id );
SET @nf_group  = ( SELECT feature_group FROM functionalities WHERE functionality_id = @nf_id );
SET @nf_others = ( SELECT COUNT(*) FROM functionalities
                   WHERE plugin_id = @nf_plugin AND feature_group = @nf_group AND audience = 'user' AND functionality_id <> @nf_id );

INSERT INTO roles_functionalities ( role_id, functionality_id )
SELECT owners.role_id, @nf_id
FROM (
    SELECT rf.role_id
    FROM roles_functionalities rf
    JOIN functionalities f ON f.functionality_id = rf.functionality_id
    WHERE f.plugin_id = @nf_plugin AND f.feature_group = @nf_group AND f.audience = 'user' AND f.functionality_id <> @nf_id
    GROUP BY rf.role_id
    HAVING COUNT(*) = @nf_others
) owners
WHERE @nf_id IS NOT NULL AND @nf_others > 0
  AND NOT EXISTS ( SELECT 1 FROM roles_functionalities x WHERE x.role_id = owners.role_id AND x.functionality_id = @nf_id );

-- 'system' functionality: by role name instead of the block above
-- INSERT INTO roles_functionalities ( role_id, functionality_id )
-- SELECT r.role_id, f.functionality_id
-- FROM roles r JOIN functionalities f ON f.functionality_name = 'my_new_function'
-- WHERE r.role_name IN ( 'esTV CRON', 'Administrator' )
--   AND NOT EXISTS ( SELECT 1 FROM roles_functionalities x WHERE x.role_id = r.role_id AND x.functionality_id = f.functionality_id );
