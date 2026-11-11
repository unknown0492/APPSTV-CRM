<?php

/**
 * Retrieves all the roles data from the roles table
 * 
 * Parameters:
 * need_functionality_count: 1 or 0, whether the output requires the count of functionalities that are assigned to the role
 * 
 */
function scodezy_get_roles(){
    checkAuthorizationForFunction( __FUNCTION__ );
    
    $need_functionality_count = request( "need_functionality_count" );      // 1 or 0
    
    // If this parameter is present, then validate it
    if( $need_functionality_count != NULL ){
        //validateEmptyDigitString( $need_functionality_count, __FUNCTION__, "Need functionality count parameter is required !" );
        validate( $need_functionality_count, __FUNCTION__, getValidationRegex( "VLDTN_SINGLE_BINARY" ), "Need functionality count parameter is invalid !" );
    }
    
    //$sql = "SELECT R.*, count(RF.*) as count FROM roles R, roles_functionalities RF WHERE (R.role_id=RF.roles_functionalities) AND (R.role_id=1) GROUP BY R.role_id ORDER BY R.id";
    if( $need_functionality_count === "1" )
        $sql = "SELECT R.*, count(RF.role_id) as count FROM roles R LEFT JOIN roles_functionalities RF ON (R.role_id = RF.role_id) GROUP BY R.role_id ORDER BY R.role_id";
    else
        $sql = "SELECT * FROM roles ORDER BY role_id";
    // SELECT R.*, count(RF.*) as count FROM roles R, roles_functionalities RF WHERE (R.role_id=RF.role_id) AND (R.role_id=1) GROUP BY R.role_id ORDER BY R.role_id 
    $result_set = selectQuery( $sql );
    if( ($result_set == NULL) || 
            ( mysqli_num_rows( $result_set )== 0 ) ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Roles does not exist yet" );
        return;
    }
    
    $roles = array();
    while( ($val = mysqli_fetch_assoc( $result_set )) != NULL ){
        $roles[] = $val;
    }
    $data = array(
        "info" => "Roles have been retrieved",
        "data" => $roles
    );
    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $data );
}

function scodezy_get_role(){
    checkAuthorizationForFunction( __FUNCTION__ );
    
    $role_id = request( "role_id" );      
    
    validateEmptyDigitString( $role_id, __FUNCTION__, "Role ID is required !" );
    validate( $role_id, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), "Role ID is invalid !" );
    
    $e_role_id = escape_string( $role_id ); 
    
    $sql = "SELECT * FROM roles WHERE role_id='$e_role_id'";
    $result_set = selectQuery( $sql );
    if( ($result_set == NULL) || 
            ( mysqli_num_rows( $result_set )== 0 ) ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Role does not exist !" );
        return;
    }
    
    $role = mysqli_fetch_assoc( $result_set );
    $roles_functionalities = array();
    
    // Retrieve all the functionalities attached to the role_id
    $sql = "SELECT RF.role_id, F.* FROM roles R, roles_functionalities RF, functionalities F WHERE (R.role_id='$e_role_id') AND (R.role_id=RF.role_id) AND (RF.functionality_id=F.functionality_id) ORDER BY role_id, functionality_id";
    $result_set = selectQuery( $sql );
    while( ($val = mysqli_fetch_assoc( $result_set )) != NULL ){
        $roles_functionalities[] = $val;
    }
    
    $role[ 'roles_functionalities' ] = $roles_functionalities;
    
    $data = array(
        "info" => "Role have been retrieved",
        "data" => $role
    );
    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $data );
}

/* ── Role permissions (1.0.107) ──────────────────────────────────────────────────────────────────────────── */

/** Functionalities a role must keep while someone signed in with that role edits it: without them they lock themselves out */
define( 'SCODEZY_ROLE_EDITOR_FUNCTIONALITIES', 'adminpanel,manage_roles,scodezy_get_role_permissions,scodezy_update_role' );

/**
 * @description Role ID of the signed-in caller, from the access token
 * @returns {string} role_id, or '' when unknown
 */
function scodezyCallerRoleId(){
    global $globalAccessToken;
    $payload = getJWTPayload( $globalAccessToken );    // Token payload of this request
    return ( is_array( $payload ) && isset( $payload[ SESSION_AUTHORIZATION ] ) ) ? (string) $payload[ SESSION_AUTHORIZATION ] : '';
}

/**
 * @description Reads the "functionalities" request parameter (comma separated IDs), validates each ID, drops
 *              duplicates and checks that every ID exists. Ends the request with an error message when one is wrong.
 * @param {string} fn - Calling webservice function, for the error messages
 * @returns {array} Unique functionality IDs (strings)
 */
function scodezyRoleRequestFunctionalityIds( $fn ){
    $functionalities = request( 'functionalities' );    // "12,15,301"
    if( ( $functionalities === NULL ) || ( trim( (string) $functionalities ) === '' ) ){
        return array();
    }
    $ids = array();                                     // id => id, keeps the first of duplicates
    foreach( explode( ',', (string) $functionalities ) as $id ){
        $id = trim( $id );
        validateEmptyDigitString( $id, $fn, "Selected functionality is invalid !" );
        validate( $id, $fn, getValidationRegex( "VLDTN_DIGITS" ), "Chosen functionality is invalid !" );
        $ids[ $id ] = $id;
    }
    $ids = array_values( $ids );
    $result_set = selectQuery( "SELECT COUNT(*) AS n FROM functionalities WHERE functionality_id IN (" . implode( ',', $ids ) . ")" );
    $row = ( $result_set ) ? mysqli_fetch_assoc( $result_set ) : NULL;
    if( ( $row === NULL ) || ( (int) $row[ 'n' ] !== count( $ids ) ) ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, $fn, "Some of the chosen functionalities no longer exist. Please reopen the role and try again" );
        exit();
    }
    return $ids;
}

/**
 * @description Names of the role-editor functionalities that the role has now and the given set of IDs leaves out,
 *              when the caller is signed in with this same role (saving would lock them out of Role Management)
 * @param {string} role_id - Role being saved
 * @param {array} ids - Functionality IDs the role will have
 * @returns {array} Aliases that would be lost; empty when the save is safe
 */
function scodezyRoleLockoutLosses( $role_id, $ids ){
    if( (string) $role_id !== scodezyCallerRoleId() ){
        return array();
    }
    // Only the ones the role has now: a role that never had one (e.g. no adminpanel) can still be saved (1.0.109)
    $names     = "'" . implode( "','", explode( ',', SCODEZY_ROLE_EDITOR_FUNCTIONALITIES ) ) . "'";
    $e_role_id = escape_string( $role_id );
    $result_set = selectQuery( "SELECT f.functionality_id, f.alias FROM functionalities f "
                             . "JOIN roles_functionalities rf ON rf.functionality_id = f.functionality_id AND rf.role_id = '$e_role_id' "
                             . "WHERE f.functionality_name IN ($names)" );
    $lost = array();                                    // Aliases missing from $ids
    while( $result_set && ( $row = mysqli_fetch_assoc( $result_set ) ) ){
        if( !in_array( (string) $row[ 'functionality_id' ], $ids, true ) ){
            $lost[] = $row[ 'alias' ];
        }
    }
    return $lost;
}

/**
 * @description Replaces the functionalities of a role in one transaction (all or nothing)
 * @param {string} role_id - Role (digits, already validated)
 * @param {array} ids - Functionality IDs the role will have (validated, unique)
 * @returns {array|false} [ added count, removed count ], or false when the database refused the change
 */
function scodezyRoleReplaceFunctionalities( $role_id, $ids ){
    connect();
    global $con;
    $e_role_id = escape_string( $role_id );

    $before = array();                                  // Current grants, id => true
    $result_set = mysqli_query( $con, "SELECT functionality_id FROM roles_functionalities WHERE role_id='$e_role_id'" );
    while( $result_set && ( $row = mysqli_fetch_assoc( $result_set ) ) ){
        $before[ (string) $row[ 'functionality_id' ] ] = true;
    }

    mysqli_begin_transaction( $con );
    $ok = (bool) mysqli_query( $con, "DELETE FROM roles_functionalities WHERE role_id='$e_role_id'" );
    if( $ok && count( $ids ) > 0 ){
        $values = array();                              // ( role, functionality ) pairs
        foreach( $ids as $id ){
            $values[] = "( '$e_role_id', '" . escape_string( $id ) . "' )";
        }
        $ok = (bool) mysqli_query( $con, "INSERT INTO roles_functionalities ( role_id, functionality_id ) VALUES " . implode( ',', $values ) );
    }
    if( !$ok ){
        mysqli_rollback( $con );
        return false;
    }
    mysqli_commit( $con );

    $added = 0;                                         // In $ids, not granted before
    foreach( $ids as $id ){
        if( !isset( $before[ $id ] ) ){ $added++; }
    }
    $removed = count( $before ) - ( count( $ids ) - $added );
    return array( $added, $removed );
}

/**
 * Role editor data in one call (1.0.107)
 *
 * Parameters:
 * role_id: optional - the role whose grants are returned in "granted" (empty for a new role)
 *
 * Returns data: plugins [ {plugin_id, plugin_name, plugin_alias} ], functionalities [ {functionality_id,
 * functionality_name, alias, functionality_description, functionality_type, is_page, plugin_id, feature_group,
 * audience} ], granted [ functionality_id ], roles [ {role_id, role_name, count} ] (for "Copy from role"),
 * role (the role row, when role_id is given), editor_functionalities (names a signed-in role must keep),
 * caller_role_id
 */
function scodezy_get_role_permissions(){
    checkAuthorizationForFunction( __FUNCTION__ );

    $role_id = request( 'role_id' );                    // Optional
    $role    = NULL;                                    // Role row
    $granted = array();                                 // Granted functionality IDs
    if( ( $role_id !== NULL ) && ( $role_id !== '' ) ){
        validate( $role_id, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), "Role ID is invalid !" );
        $e_role_id  = escape_string( $role_id );
        $result_set = selectQuery( "SELECT role_id, role_name, role_slug FROM roles WHERE role_id='$e_role_id'" );
        if( !$result_set || mysqli_num_rows( $result_set ) === 0 ){
            echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Role does not exist !" );
            return;
        }
        $role       = mysqli_fetch_assoc( $result_set );
        $result_set = selectQuery( "SELECT functionality_id FROM roles_functionalities WHERE role_id='$e_role_id'" );
        while( $result_set && ( $row = mysqli_fetch_assoc( $result_set ) ) ){
            $granted[] = (string) $row[ 'functionality_id' ];
        }
    }

    $plugins    = array();
    $result_set = selectQuery( "SELECT plugin_id, plugin_name, plugin_alias FROM plugins ORDER BY plugin_alias" );
    while( $result_set && ( $row = mysqli_fetch_assoc( $result_set ) ) ){
        $plugins[] = $row;
    }

    $functionalities = array();
    $result_set = selectQuery( "SELECT functionality_id, functionality_name, alias, functionality_description, functionality_type, is_page, "
                             . "plugin_id, feature_group, audience FROM functionalities ORDER BY plugin_id, feature_group, is_page DESC, alias" );
    while( $result_set && ( $row = mysqli_fetch_assoc( $result_set ) ) ){
        $functionalities[] = $row;
    }

    $roles      = array();
    $result_set = selectQuery( "SELECT R.role_id, R.role_name, COUNT(RF.functionality_id) AS count FROM roles R "
                             . "LEFT JOIN roles_functionalities RF ON RF.role_id = R.role_id GROUP BY R.role_id ORDER BY R.role_name" );
    while( $result_set && ( $row = mysqli_fetch_assoc( $result_set ) ) ){
        $roles[] = $row;
    }

    $data = array(
        "info" => "Role permissions have been retrieved",
        "data" => array(
            "role"                   => $role,
            "granted"                => $granted,
            "plugins"                => $plugins,
            "functionalities"        => $functionalities,
            "roles"                  => $roles,
            "editor_functionalities" => explode( ',', SCODEZY_ROLE_EDITOR_FUNCTIONALITIES ),
            "caller_role_id"         => scodezyCallerRoleId()
        )
    );
    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $data );
}

function scodezy_create_role(){
    checkAuthorizationForFunction( __FUNCTION__ );

    $role_name         = request( 'role_name' );
    $role_slug         = request( 'role_slug' );

    validateEmptyString( $role_name, __FUNCTION__, "Role name is required" );
    validateEmptyString( $role_slug, __FUNCTION__, "Role slug is required" );

    validate( $role_name, __FUNCTION__, getValidationRegex( "VLDTN_ROLE_NAME" ), "Only lowercase, uppercase alphabets, digits 0 to 9 and white spaces are allowed" );
    validate( $role_slug, __FUNCTION__, getValidationRegex( "VLDTN_ROLE_SLUG" ), "Only lowercase alphabets, digits 0 to 9 and (underscore) _ are allowed" );

    $functionalityIDs = scodezyRoleRequestFunctionalityIds( __FUNCTION__ );     // Validated, unique, existing (1.0.107)

    // Escape strings for storing in the DB
    $e_role_name            = escape_string( $role_name );
    $e_role_slug            = escape_string( $role_slug );
    
    // role_name has to be unique throughout the system
    $sql = "SELECT role_name FROM roles WHERE role_name='$e_role_name'";
    $result_set = selectQuery( $sql );
    if( mysqli_num_rows( $result_set ) > 0 ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "A role already exist with the given role_name. Please change the role_name and try again" );
        return;
    }
    
    // role_slug has to be unique throughout the system
    $sql = "SELECT role_slug FROM roles WHERE role_slug='$e_role_slug'";
    $result_set = selectQuery( $sql );
    if( mysqli_num_rows( $result_set ) > 0 ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "A role already exist with the given role_slug. Please change the role_slug and try again" );
        return;
    }
    
    // Insert the role information into roles table
    $sql = "INSERT INTO roles( `role_name`, `role_slug` ) VALUES( '$e_role_name', '$e_role_slug' )";
    $rows = insertQuery( $sql );
    if( $rows > 0 ){
        $role_id = getAIID();
        $count   = count( $functionalityIDs );
        if( ( $count > 0 ) && ( scodezyRoleReplaceFunctionalities( $role_id, $functionalityIDs ) === false ) ){
            // The role exists without functionalities: say so, so they can be added from Edit Role (1.0.107)
            echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "The role was created but its functionalities could not be saved. Please open Edit Role and save them again" );
            return;
        }
        $role = array(
            "role_id" => $role_id,
            "role_name" => $role_name,
            "role_slug" => $role_slug,
            "count" => $count
        );
        $data = array(
            "info" => "Role has been created with $count functionalit" . ( $count === 1 ? 'y' : 'ies' ),
            "data" => $role
        );
        echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $data );
        return;
    }
    echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Failed to create the Role" );
}

function scodezy_update_role(){
    checkAuthorizationForFunction( __FUNCTION__ );
    
    //print_r( $_REQUEST );
    $role_id           = request( 'role_id' );
    $role_name         = request( 'role_name' );
    $role_slug         = request( 'role_slug' );

    validateEmptyString( $role_id, __FUNCTION__, "Please select a role to be edited" );
    validateEmptyString( $role_name, __FUNCTION__, "Role name is required" );
    validateEmptyString( $role_slug, __FUNCTION__, "Role slug is required" );

    validate( $role_id, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), "Selected role is invalid" );
    validate( $role_name, __FUNCTION__, getValidationRegex( "VLDTN_ROLE_NAME" ), "Only lowercase, uppercase alphabets, digits 0 to 9 and white spaces are allowed" );
    validate( $role_slug, __FUNCTION__, getValidationRegex( "VLDTN_ROLE_SLUG" ), "Only lowercase alphabets, digits 0 to 9 and (underscore) _ are allowed" );

    $functionalityIDs = scodezyRoleRequestFunctionalityIds( __FUNCTION__ );     // Validated, unique, existing (1.0.107)

    // Signed in with this role: it must keep the Role Management functionalities, or the save would lock them out
    $lost = scodezyRoleLockoutLosses( $role_id, $functionalityIDs );
    if( count( $lost ) > 0 ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "You are signed in with this role. It must keep: " . implode( ', ', $lost ) . ", or you would lose access to Role Management" );
        return;
    }

    // Escape strings for storing in the DB
    $e_role_id              = escape_string( $role_id );
    $e_role_name            = escape_string( $role_name );
    $e_role_slug            = escape_string( $role_slug );
    
    // role_name has to be unique throughout the system
    // also, the given role_name can only belong to the given role_id for moving forward with the code
    $sql = "SELECT role_name FROM roles WHERE role_name='$e_role_name' AND role_id<>'$e_role_id'";
    $result_set = selectQuery( $sql );
    if( mysqli_num_rows( $result_set ) > 0 ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "A role already exist with the given Role Name. Please change the role name and try again" );
        return;
    }
    
    // role_slug has to be unique throughout the system
    // also, the given role_slug can only belong to the given role_id for moving forward with the code
    $sql = "SELECT role_slug FROM roles WHERE role_slug='$e_role_slug' AND role_id<>'$e_role_id'";
    $result_set = selectQuery( $sql );
    if( mysqli_num_rows( $result_set ) > 0 ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "A role slug exist with the given Role Slug. Please change the role slug and try again" );
        return;
    }
    
    // UPDATE the role information into roles table
    $sql = "UPDATE roles SET `role_name`='$e_role_name', "
            . "`role_slug`='$e_role_slug' WHERE role_id='$e_role_id'";
    $rows = updateQuery( $sql );
    if( $rows > 0 ){
        $count  = count( $functionalityIDs );
        $change = scodezyRoleReplaceFunctionalities( $role_id, $functionalityIDs );     // [ added, removed ], all or nothing (1.0.107)
        if( $change === false ){
            echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "The role's functionalities could not be saved. Nothing was changed, please try again" );
            return;
        }
        $role = array(
            "role_id" => $role_id,
            "role_name" => $role_name,
            "role_slug" => $role_slug,
            "count" => $count,
            "added" => $change[ 0 ],
            "removed" => $change[ 1 ]
        );
        $data = array(
            "info" => "Role has been updated: $change[0] added, $change[1] removed, $count in total",
            "data" => $role
        );
        echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $data );
        return;
    }
    echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Failed to update the Role" );
}

function scodezy_delete_role(){
    checkAuthorizationForFunction( __FUNCTION__ );
    
    $role_id = request( 'role_id' );
    
    // Do validation for Role ID
    validateEmptyDigitString( $role_id, __FUNCTION__, "Role ID is required !" );
    validate( $role_id, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), "Role ID is invalid !" );
    
    $e_role_id = escape_string( $role_id );

    // The role you are signed in with cannot be deleted: you would lose access at once (1.0.107)
    if( (string) $role_id === scodezyCallerRoleId() ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "You are signed in with this role, so it cannot be deleted" );
        return;
    }

    $sql = "SELECT * FROM roles WHERE role_id='$e_role_id'";
    $result_set = selectQuery( $sql );
    if( ($result_set == NULL)
            || ( mysqli_num_rows($result_set) == 0 )){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Role ID is invalid !" );
        return;
    }
    $role = mysqli_fetch_assoc( $result_set );
    
    $sql = "DELETE FROM roles WHERE role_id='$e_role_id'";
    //echo $sql;
    //return;
    $rows = deleteQuery( $sql );
    if( $rows > 0 ){
        // Delete the assignments from the roles_functionalities table
        $sql = "DELETE FROM roles_functionalities WHERE role_id='$e_role_id'";
        deleteQuery( $sql );
        
        $data = array(
            "info" => "Role has been deleted",
            "data" => $role
        );
        echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $data );
        return;
    }
    echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Failed to delete the role" );
}

?>