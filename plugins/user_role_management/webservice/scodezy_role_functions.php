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

function scodezy_create_role(){
    checkAuthorizationForFunction( __FUNCTION__ );
    
    
   // print_r( $_REQUEST );
    
    $role_name         = request( 'role_name' );
    $role_slug         = request( 'role_slug' );
    $functionalities   = request( 'functionalities' );
    
    validateEmptyString( $role_name, __FUNCTION__, "Role name is required" );
    validateEmptyString( $role_slug, __FUNCTION__, "Role slug is required" );
    
    validate( $role_name, __FUNCTION__, getValidationRegex( "VLDTN_ROLE_NAME" ), "Only lowercase, uppercase alphabets, digits 0 to 9 and white spaces are allowed" );
    validate( $role_slug, __FUNCTION__, getValidationRegex( "VLDTN_ROLE_SLUG" ), "Only lowercase alphabets, digits 0 to 9 and (underscore) _ are allowed" );
    
    $functionalityIDs = [];
    if( ($functionalities !== NULL) && ($functionalities !== "") ){
        $functionalityIDs = explode( ",", $functionalities );
        foreach ( $functionalityIDs as $functionality_id ) {
            validateEmptyDigitString( $functionality_id, __FUNCTION__, "Selected functionality is invalid !" );
            validate( $functionality_id, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), "Chosen functionality is invalid !" );
        }
    }
    
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
        $count = 0;
        if( ($count = count( $functionalityIDs )) > 0 ){
            $sql = "INSERT INTO roles_functionalities( `role_id`, `functionality_id` ) VALUES";
            foreach ( $functionalityIDs as $functionality_id ) {
                $sql .= "( '$role_id', '$functionality_id' ),";
            }
            $sql = rtrim( $sql, "," );
            insertQuery( $sql );
        }
        $role = array(
            "role_id" => $role_id,
            "role_name" => $e_role_name,
            "role_slug" => $e_role_slug,
            "count" => $count
        );
        $data = array(
            "info" => "Role has been created",
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
    $functionalities   = request( 'functionalities' );
    
    validateEmptyString( $role_id, __FUNCTION__, "Please select a role to be edited" );
    validateEmptyString( $role_name, __FUNCTION__, "Role name is required" );
    validateEmptyString( $role_slug, __FUNCTION__, "Role slug is required" );
    
    validate( $role_id, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), "Selected role is invalid" );
    validate( $role_name, __FUNCTION__, getValidationRegex( "VLDTN_ROLE_NAME" ), "Only lowercase, uppercase alphabets, digits 0 to 9 and white spaces are allowed" );
    validate( $role_slug, __FUNCTION__, getValidationRegex( "VLDTN_ROLE_SLUG" ), "Only lowercase alphabets, digits 0 to 9 and (underscore) _ are allowed" );
    
    $functionalityIDs = [];
    if( ($functionalities !== NULL) && ($functionalities !== "") ){
        $functionalityIDs = explode( ",", $functionalities );
        foreach ( $functionalityIDs as $functionality_id ) {
            validateEmptyDigitString( $functionality_id, __FUNCTION__, "Selected functionality is invalid !" );
            validate( $functionality_id, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), "Chosen functionality is invalid !" );
        }
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
        $count = 0;
        $sql = "DELETE from roles_functionalities WHERE role_id='$e_role_id'";
        deleteQuery( $sql );
        if( ($count = count( $functionalityIDs )) > 0 ){
            $sql = "INSERT INTO roles_functionalities( `role_id`, `functionality_id` ) VALUES";
            foreach ( $functionalityIDs as $functionality_id ) {
                $sql .= "( '$role_id', '$functionality_id' ),";
            }
            $sql = rtrim( $sql, "," );
            insertQuery( $sql );
        }
        $role = array(
            "role_id" => $role_id,
            "role_name" => $e_role_name,
            "role_slug" => $e_role_slug,
            "count" => $count
        );
        $data = array(
            "info" => "Role has been updated",
            "data" => $role
        );
        echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $data );
        return;
    }
    echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Failed to create the Role" );
}

function scodezy_delete_role(){
    checkAuthorizationForFunction( __FUNCTION__ );
    
    $role_id = request( 'role_id' );
    
    // Do validation for Role ID
    validateEmptyDigitString( $role_id, __FUNCTION__, "Role ID is required !" );
    validate( $role_id, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), "Role ID is invalid !" );
    
    $e_role_id = escape_string( $role_id );
    
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