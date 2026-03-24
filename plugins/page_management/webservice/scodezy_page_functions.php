<?php

function scodezy_get_pages(){
    checkAuthorizationForFunction( __FUNCTION__ );
    
    $plugin_id = @$_REQUEST[ 'plugin_id' ];
    
    if( !isset( $_REQUEST[ 'plugin_id' ] ) 
            || ($plugin_id == NULL) 
            || ($plugin_id == "")){
        $plugin_id = "-1";  // -1 means retrieve all the pages irrespective of the plugin to which they belong
    }
    
    // Do validation for Plugin ID
    validateEmptyDigitString( $plugin_id, __FUNCTION__, "Plugin ID is required !" );
    if( $plugin_id != "-1" )
        validate( $plugin_id, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), "Plugin ID is invalid !" );
    
    $e_plugin_id = escape_string( $plugin_id );
    
    if( $e_plugin_id == "-1" ){
        $sql = "SELECT page_id, page_name, parent_id, hierarchy, icon, visible, page_title FROM pages ORDER BY plugin_id";        
    }
    else{
        $sql = "SELECT page_id, page_name, parent_id, hierarchy, icon, visible, page_title FROM pages WHERE plugin_id='$e_plugin_id'";
    }
    
    $result_set = selectQuery( $sql );
    if( ($result_set == NULL)
            || ( mysqli_num_rows($result_set) == 0 )){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Pages have not been created yet for the selected plugin !" );
        return;
    }
    
    $pages = array();
    while( ($val = mysqli_fetch_assoc( $result_set )) != NULL ){
        $pages[] = $val;
    }
    
    $data = array(
        "info" => "Pages have been retrieved",
        "data" => $pages
    );
    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $data );
    
}

function scodezy_get_parent_pages(){
    checkAuthorizationForFunction( __FUNCTION__ );
    
    $plugin_id = request( 'plugin_id' );
    
    if( ($plugin_id == NULL) 
            || ($plugin_id == "")){
        $plugin_id = "-1";  // -1 means retrieve all the pages irrespective of the plugin to which they belong
    }
    
    // Do validation for Plugin ID
    validateEmptyDigitString( $plugin_id, __FUNCTION__, "Plugin ID is required !" );
    if( $plugin_id != "-1" )
        validate( $plugin_id, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), "Plugin ID is invalid !" );
    
    $e_plugin_id = escape_string( $plugin_id );
    
    // We need only parents, so only hierarchy 1 and 2
    if( $e_plugin_id == "-1" ){
        $sql = "SELECT page_id, page_name, parent_id, hierarchy, icon, visible, page_title, page_sequence FROM pages WHERE hierarchy IN (1,2) ORDER BY plugin_id, page_sequence";        
    }
    else{
        $sql = "SELECT page_id, page_name, parent_id, hierarchy, icon, visible, page_title FROM pages WHERE plugin_id='$e_plugin_id' AND hierarchy IN (1,2) ORDER BY plugin_id, page_sequence";
    }
    
    $result_set = selectQuery( $sql );
    if( ( $result_set == NULL )
            || ( mysqli_num_rows($result_set) == 0 )){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Pages have not been created yet for the selected plugin !" );
        return;
    }
    
    $pages = array();
    while( ($val = mysqli_fetch_assoc( $result_set )) != NULL ){
        $pages[] = $val;
    }
    
    $data = array(
        "info" => "Parent pages have been retrieved",
        "data" => $pages
    );
    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $data );
    
}

function scodezy_get_page(){
    checkAuthorizationForFunction( __FUNCTION__ );
    
    $page_id = request( 'page_id' );
    
    // Do validation for Plugin ID
    validateEmptyDigitString( $page_id, __FUNCTION__, "Page ID is required !" );
    validate( $page_id, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), "Page ID is invalid !" );
    
    $e_page_id = escape_string( $page_id );
    
    $sql = "SELECT * FROM pages WHERE page_id='$e_page_id'";
    $result_set = selectQuery( $sql );
    if( ($result_set == NULL)
            || ( mysqli_num_rows($result_set) == 0 )){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Page ID is invalid !" );
        return;
    }
    
    
    $page = mysqli_fetch_assoc( $result_set );
    $data = array(
        "info" => "Page information has been retrieved",
        "data" => $page
    );
    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $data );
}

function scodezy_get_child_pages(){
    checkAuthorizationForFunction( __FUNCTION__ );
    
    $parent_id = request( 'parent_id' );
    
    if( ($parent_id != NULL) 
            && ($parent_id != "-1") ){
        // Do validation for Page ID
        validateEmptyDigitString( $parent_id, __FUNCTION__, "Parent page is required !" );
        validate( $parent_id, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), "Parent page is invalid !" );
    }
    
    $e_parent_id = escape_string( $parent_id );
    
    if( $parent_id != "-1" )
        $sql = "SELECT page_id, page_name, parent_id, hierarchy, icon, visible, page_title, page_sequence FROM pages WHERE parent_id='$e_parent_id' ORDER BY page_sequence";
    else 
        $sql = "SELECT page_id, page_name, parent_id, hierarchy, icon, visible, page_title, page_sequence FROM pages WHERE hierarchy='1' ORDER BY page_sequence";
    $result_set = selectQuery( $sql );
    if( ( $result_set == NULL )
            || ( mysqli_num_rows($result_set) == 0 )){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "No child pages exist for this parent !" );
        return;
    }
    
    $pages = array();
    while( ($val = mysqli_fetch_assoc( $result_set )) != NULL ){
        $pages[] = $val;
    }
    
    $data = array(
        "info" => "Child pages have been retrieved",
        "data" => $pages
    );
    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $data );
}

function scodezy_update_page_visible_status(){
    checkAuthorizationForFunction( __FUNCTION__ );
    
    //echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Unknown error occurred !" );
    //return;
    //print_r( $_REQUEST );
    $status     = @$_REQUEST[ 'status' ];
    $page_id    = @$_REQUEST[ 'page_id' ];
    
    validateEmptyDigitString( $status, __FUNCTION__, "New visible status for the page is required !" );
    validate( $status, __FUNCTION__, getValidationRegex( "VLDTN_SINGLE_BINARY" ), "Page status is invalid !" );
    validateEmptyDigitString( $page_id, __FUNCTION__, "Page ID is required !" );
    validate( $page_id, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), "Page ID is invalid !" );
    
    $e_page_id  = escape_string( $page_id );
    $e_status   = escape_string( $status );
    
    $sql  = "UPDATE pages SET visible='$e_status' WHERE page_id='$e_page_id'";
    $rows = selectQuery( $sql );
    if( $rows > 0 ){
        echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, "Page visible status has been updated !" );
        return;
    }
    
    echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Unknown error occurred !" );
}

function scodezy_create_page(){
    checkAuthorizationForFunction( __FUNCTION__ );
    //print_r( $_REQUEST );
    /*
    $page_name          = @$_REQUEST[ 'page_name' ];
    $page_title         = @$_REQUEST[ 'page_title' ];
    $page_icon          = @$_REQUEST[ 'page_icon' ];
    $page_description   = @$_REQUEST[ 'page_description' ];
    $is_visible         = @$_REQUEST[ 'is_visible' ];
    $plugin_id          = @$_REQUEST[ 'plugin_id' ];
    $functionality_id   = @$_REQUEST[ 'functionality_id' ];
    $hierarchy          = @$_REQUEST[ 'hierarchy' ];
    $parent_page_id     = @$_REQUEST[ 'parent_page_id' ];
    */
    $page_name          = request( 'page_name' );
    $page_title         = request( 'page_title' );
    $page_icon          = request( 'page_icon' );
    $page_description   = request( 'page_description' );
    $is_visible         = request( 'is_visible' );
    $plugin_id          = request( 'plugin_id' );
    $functionality_id   = request( 'functionality_id' );
    $hierarchy          = request( 'hierarchy' );
    $parent_page_id     = request( 'parent_page_id' );
    
    
    validateEmptyString( $page_name, __FUNCTION__, "Page name is required" );
    validateEmptyString( $page_title, __FUNCTION__, "Page title is required" );
    validateEmptyDigitString( $is_visible, __FUNCTION__, "Please check if the page is visible ?" );
    validateEmptyDigitString( $plugin_id, __FUNCTION__, "Please select a plugin" );
    validateEmptyDigitString( $functionality_id, __FUNCTION__, "Please select a functionality" );
    validateEmptyDigitString( $hierarchy, __FUNCTION__, "Please select an hierarchy" );
    
    validate( $page_name, __FUNCTION__, getValidationRegex( "VLDTN_PAGE_NAME" ), "Page name can only contain lowercase letters, numbers and an underscore" );
    validate( $page_title, __FUNCTION__, getValidationRegex( "VLDTN_PAGE_TITLE" ), "Some special characters are not allowed for the page title" );
    validate( $page_icon, __FUNCTION__, getValidationRegex( "VLDTN_ICON" ), "Compliant with only fontawesome, lineicons, bootstrap icon names" );
    validate( $page_description, __FUNCTION__, getValidationRegex( "VLDTN_PAGE_DESCRIPTION" ), "Some special characters are not allowed for the page description" );
    validate( $is_visible, __FUNCTION__, getValidationRegex( "VLDTN_SINGLE_BINARY" ), "Visible status of the page is invalid" );
    validate( $plugin_id, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), "Plugin is invalid" );
    validate( $functionality_id, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), "Functionality is invalid" );
    validate( $hierarchy, __FUNCTION__, getValidationRegex( "VLDTN_PAGE_HIERARCHY" ), "Page hierarchy is invalid" );
    if( $parent_page_id != "-1" ){
        validate( $parent_page_id, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), "Parent page is invalid" );
    }
    
    // Escape strings for storing in the DB
    $e_page_name            = escape_string( $page_name );
    $e_page_title           = escape_string( $page_title );
    $e_page_icon            = escape_string( $page_icon );
    $e_page_description     = escape_string( $page_description );
    $e_is_visible           = escape_string( $is_visible );
    $e_plugin_id            = escape_string( $plugin_id );
    $e_functionality_id     = escape_string( $functionality_id );
    $e_hierarchy            = escape_string( $hierarchy );
    $e_parent_page_id       = escape_string( $parent_page_id );
    
    // page_name has to be unique throughout the system
    $sql = "SELECT page_name FROM pages WHERE page_name='$e_page_name'";
    $result_set = selectQuery( $sql );
    if( mysqli_num_rows( $result_set ) > 0 ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "A page already exist with the given Page Name. Please change the page name and try again" );
        return;
    }
    
    // Plugin ID should exist in the system
    $sql = "SELECT plugin_id FROM plugins WHERE plugin_id='$e_plugin_id'";
    $result_set = selectQuery( $sql );
    if( mysqli_num_rows( $result_set ) == 0 ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Selected plugin does not exist" );
        return;
    }
    
    // Functionality ID should exist in the system
    $sql = "SELECT functionality_id FROM functionalities WHERE functionality_id='$e_functionality_id'";
    $result_set = selectQuery( $sql );
    if( mysqli_num_rows( $result_set ) == 0 ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Selected functionality does not exist" );
        return;
    }
    
    // Parent Page ID should exist in the system, if parent_page_id not equals -1
    if( $e_parent_page_id != "-1" ){ 
        $sql = "SELECT page_id FROM pages WHERE page_id='$e_parent_page_id'";
        $result_set = selectQuery( $sql );
        if( mysqli_num_rows( $result_set ) == 0 ){
            echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Selected parent page does not exist" );
            return;
        }
    }
    
    // Insert the value into the table
    $sql = "INSERT INTO pages( `page_name`, `parent_id`, `hierarchy`, `page_sequence`, `icon`, `visible`, `page_title`, `title`, `functionality_id`, `description`, `plugin_id` ) "
            . "VALUES( '$page_name', '$parent_page_id', '$hierarchy', '1', '$page_icon', '$is_visible', '$page_title', '$page_title', '$functionality_id', '$e_page_description', '$plugin_id' )";
    $rows = insertQuery( $sql );
    if( $rows > 0 ){
        $page_id = getAIID();
        $sql = "SELECT * FROM pages WHERE page_id='$page_id'";
        $result_set = selectQuery( $sql );
        
        $page = mysqli_fetch_assoc( $result_set );
        $data = array(
            "info" => "Page has been created successfully",
            "data" => $page
        );
        echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $data );
        return;
    }
    echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Unknown error occurred !" );
}

function scodezy_delete_page(){
    checkAuthorizationForFunction( __FUNCTION__ );
    
    //print_r( $_REQUEST );
    //return;
    $page_id = request( 'page_id' );
    
    // Do validation for Plugin ID
    validateEmptyDigitString( $page_id, __FUNCTION__, "Page ID is required !" );
    validate( $page_id, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), "Page ID is invalid !" );
    
    $e_page_id = escape_string( $page_id );
    
    $sql = "SELECT * FROM pages WHERE page_id='$e_page_id'";
    $result_set = selectQuery( $sql );
    if( ($result_set == NULL)
            || ( mysqli_num_rows($result_set) == 0 )){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Page ID is invalid !" );
        return;
    }
    $page = mysqli_fetch_assoc( $result_set );
    
    $sql = "DELETE FROM pages WHERE page_id='$e_page_id'";
    //echo $sql;
    //return;
    $rows = deleteQuery( $sql );
    if( $rows > 0 ){
        // If this page is a parent of other pages, then all those pages parent needs to be set to ZERO 0
        $sql = "UPDATE pages SET parent_id='0' WHERE parent_id='$e_page_id'";
        updateQuery( $sql );
        
        $data = array(
            "info" => "Page has been deleted",
            "data" => $page
        );
        echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $data );
        return;
    }
    echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Failed to delete the page" );
}

function scodezy_delete_pages(){
    checkAuthorizationForFunction( __FUNCTION__ );
    
    $page_ids = request( 'page_ids' );
    
    if( $page_ids === NULL ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Please select the pages to be deleted !" );
        return;
    }
    
    // $page_ids needs to be a CSV string
    $pageIDs = explode( ",", $page_ids );
    
    // Do validation for Page IDs
    foreach ( $pageIDs as $value ) {
        validateEmptyDigitString( $value, __FUNCTION__, "Page ID is required !" );
        validate( $value, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), "Page ID is invalid !" );
    }
    
    $e_page_ids = escape_string( $page_ids );
    
    $sql = "SELECT * FROM pages WHERE page_id IN ($e_page_ids)";
    //echo $sql;
    //return;
    $result_set = selectQuery( $sql );
    if( ($result_set == NULL)
            || ( mysqli_num_rows($result_set) == 0 )){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Page ID is invalid !" );
        return;
    }
    $fetchedPages = array();
    while( ( $val = mysqli_fetch_assoc( $result_set ) ) != NULL ){
        $fetchedPages[] = $val;
    }
    
    $sql = "DELETE FROM pages WHERE page_id IN ($e_page_ids)";
    //echo $sql;
    //return;
    $rows = deleteQuery( $sql );
    if( $rows > 0 ){
        // If this page is a parent of other pages, then all those pages parent needs to be set to ZERO 0
        $sql = "UPDATE pages SET parent_id='0' WHERE parent_id IN ($e_page_ids)";
        updateQuery( $sql );
        
        $data = array(
            "info" => "Selected pages have been deleted",
            "data" => $fetchedPages
        );
        echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $data );
        return;
    }
    echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Failed to delete the selected pages" );
}

function scodezy_update_page(){
    checkAuthorizationForFunction( __FUNCTION__ );
    
    // print_r( $_REQUEST );
    /*
    $page_name          = @$_REQUEST[ 'page_name' ];
    $page_title         = @$_REQUEST[ 'page_title' ];
    $page_icon          = @$_REQUEST[ 'page_icon' ];
    $page_description   = @$_REQUEST[ 'page_description' ];
    $is_visible         = @$_REQUEST[ 'is_visible' ];
    $plugin_id          = @$_REQUEST[ 'plugin_id' ];
    $functionality_id   = @$_REQUEST[ 'functionality_id' ];
    $hierarchy          = @$_REQUEST[ 'hierarchy' ];
    $parent_page_id     = @$_REQUEST[ 'parent_page_id' ];
    */
    $page_id            = request( 'page_id' );
    $page_name          = request( 'page_name' );
    $page_title         = request( 'page_title' );
    $page_icon          = request( 'page_icon' );
    $page_description   = request( 'page_description' );
    $is_visible         = request( 'is_visible' );
    $plugin_id          = request( 'plugin_id' );
    $functionality_id   = request( 'functionality_id' );
    $hierarchy          = request( 'hierarchy' );
    $parent_page_id     = request( 'parent_page_id' );
    
    
    validateEmptyString( $page_id, __FUNCTION__, "Please select a valid page" );
    validateEmptyString( $page_name, __FUNCTION__, "Page name is required" );
    validateEmptyString( $page_title, __FUNCTION__, "Page title is required" );
    validateEmptyDigitString( $is_visible, __FUNCTION__, "Please check if the page is visible ?" );
    validateEmptyDigitString( $plugin_id, __FUNCTION__, "Please select a plugin" );
    validateEmptyDigitString( $functionality_id, __FUNCTION__, "Please select a functionality" );
    validateEmptyDigitString( $hierarchy, __FUNCTION__, "Please select an hierarchy" );
    
    validate( $page_id, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), "Selected page is invalid" );
    validate( $page_name, __FUNCTION__, getValidationRegex( "VLDTN_PAGE_NAME" ), "Page name can only contain lowercase letters, numbers and an underscore" );
    validate( $page_title, __FUNCTION__, getValidationRegex( "VLDTN_PAGE_TITLE" ), "Some special characters are not allowed for the page title" );
    validate( $page_icon, __FUNCTION__, getValidationRegex( "VLDTN_ICON" ), "Compliant with only fontawesome, lineicons, bootstrap icon names" );
    validate( $page_description, __FUNCTION__, getValidationRegex( "VLDTN_PAGE_DESCRIPTION" ), "Some special characters are not allowed for the page description" );
    validate( $is_visible, __FUNCTION__, getValidationRegex( "VLDTN_SINGLE_BINARY" ), "Visible status of the page is invalid" );
    validate( $plugin_id, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), "Plugin is invalid" );
    validate( $functionality_id, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), "Functionality is invalid" );
    validate( $hierarchy, __FUNCTION__, getValidationRegex( "VLDTN_PAGE_HIERARCHY" ), "Page hierarchy is invalid" );
    if( $parent_page_id != "-1" ){
        validate( $parent_page_id, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), "Parent page is invalid" );
    }
    
    // Escape strings for storing in the DB
    $e_page_id              = escape_string( $page_id );
    $e_page_name            = escape_string( $page_name );
    $e_page_title           = escape_string( $page_title );
    $e_page_icon            = escape_string( $page_icon );
    $e_page_description     = escape_string( $page_description );
    $e_is_visible           = escape_string( $is_visible );
    $e_plugin_id            = escape_string( $plugin_id );
    $e_functionality_id     = escape_string( $functionality_id );
    $e_hierarchy            = escape_string( $hierarchy );
    $e_parent_page_id       = escape_string( $parent_page_id );
    
    // page_name has to be unique throughout the system
    // also, the given page_name can only belong to the given page id for moving forward with the code
    $sql = "SELECT page_name FROM pages WHERE page_name='$e_page_name' AND page_id<>'$e_page_id'";
    $result_set = selectQuery( $sql );
    if( mysqli_num_rows( $result_set ) > 0 ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "A page already exist with the given Page Name. Please change the page name and try again" );
        return;
    }
    
    // Plugin ID should exist in the system
    $sql = "SELECT plugin_id FROM plugins WHERE plugin_id='$e_plugin_id'";
    $result_set = selectQuery( $sql );
    if( mysqli_num_rows( $result_set ) == 0 ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Selected plugin does not exist" );
        return;
    }
    
    // Functionality ID should exist in the system
    $sql = "SELECT functionality_id FROM functionalities WHERE functionality_id='$e_functionality_id'";
    $result_set = selectQuery( $sql );
    if( mysqli_num_rows( $result_set ) == 0 ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Selected functionality does not exist" );
        return;
    }
    
    // Parent Page ID should exist in the system, if parent_page_id not equals -1
    if( $e_parent_page_id != "0" ){ 
        $sql = "SELECT page_id FROM pages WHERE page_id='$e_parent_page_id'";
        $result_set = selectQuery( $sql );
        if( mysqli_num_rows( $result_set ) == 0 ){
            echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Selected parent page does not exist" );
            return;
        }
    }
    
    // Update the value into the table
    $sql = "UPDATE pages SET `page_name`='$e_page_name', "
                . "`parent_id`='$e_parent_page_id', "
                . "`hierarchy`='$e_hierarchy', "
                . "`icon`='$e_page_icon', "
                . "`visible`='$e_is_visible', "
                . "`page_title`='$e_page_title', "
                . "`title`='$e_page_title', "
                . "`functionality_id`='$e_functionality_id', "
                . "`description`='$e_page_description', "
                . "`plugin_id`='$e_plugin_id' "
                . "WHERE page_id='$e_page_id'";
    $rows = updateQuery( $sql );
    if( $rows > 0 ){
        $page_id = getAIID();
        $sql = "SELECT * FROM pages WHERE page_id='$e_page_id'";
        $result_set = selectQuery( $sql );
        
        $page = mysqli_fetch_assoc( $result_set );
        $data = array(
            "info" => "Page information has been updated",
            "data" => $page
        );
        echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $data );
        return;
    }
    echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Unknown error occurred !" );
}

function scodezy_update_page_sequence(){
    checkAuthorizationForFunction( __FUNCTION__ );
    
    $page_ids   = request( 'page_ids' );      // A CSV array of page_ids
    $parent_id  = request( 'parent_page_id' );      
    
    // If parent page id is missing or not specified, it means that the Top Hierarchy pages are being sequenced
    if( ($parent_id != NULL) 
            && ($parent_id != "-1") ){
        validateEmptyDigitString( $parent_id, __FUNCTION__, "Parent Page is required !" );
        validate( $parent_id, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ),"Parent Page is invalid !" );        
    }
    
    if( $page_ids == NULL ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Pages are needed to be arranged in sequence" );
        return;
    }
    
    $pageIDs = explode( ",", $page_ids );
    foreach ( $pageIDs as $page_id ) {
        validateEmptyDigitString( $page_id, __FUNCTION__, "Page is required !" );
        validate( $page_id, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ),"Page is invalid !" );
    }
    
    //$e_page_ids = escape_string( $page_ids );
    
    $sequence = 1;
    foreach ( $pageIDs as $page_id ) {
        $sql = "UPDATE pages SET page_sequence='$sequence' WHERE page_id='$page_id'";
        updateQuery( $sql );
        $sequence++;
    }
    
    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, "Page sequence has been updated" );
    
}

function scodezy_import_pages(){
    checkAuthorizationForFunction( __FUNCTION__ );
    
    //print_r( $_REQUEST );
    $pages_file        = @$_FILES[ 'pages_file' ];
    
    if( ($pages_file === NULL) || ($pages_file === "") ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Please select a pages json file and try again" );
        return;
    }
    
    // $name       = preg_replace( '/[^\x20-\x7E]/', '', $plugin_file[ 'name' ] );     // Remove all non-ASCII characters from the file name
    $name       = get_valid_scodezy_filename( $pages_file[ 'name' ] );     // Remove all non-ASCII characters from the file name
    $type       = $pages_file[ 'type' ]; 
    $tmp_name   = $pages_file[ 'tmp_name' ];
    $error      = $pages_file[ 'error' ];
    
    if( $type !== "application/json" ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Please select a valid scodezy pages json file and try again" );
        return;
    }
    
    if( $error > 0 ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Selected file has errors" );
        return;
    }
    
    // Make sure the temporary directory for file upload is available and writable
    if( !file_exists( DAT_PATH ) ){
        mkdir( DAT_PATH );
    }
    $tmp_dir_path = DAT_PATH . FILE_SEPARATOR . DIRNAME_TMP_DATA;
    if( !file_exists( $tmp_dir_path ) ){
        mkdir( $tmp_dir_path );
    }
    
    // Create the temporary file 
    $currentTimestamp = currentTimeMilliseconds();
    $tmpFileName = $currentTimestamp . "__" . $name;
    $tmpFilePath = $tmp_dir_path . FILE_SEPARATOR . $tmpFileName;
    if( !move_uploaded_file( $tmp_name, $tmpFilePath ) ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Failed to upload the pages json file" );
        return;
    }
    //----------------------
    //$pages_json_file_path = DAT_PATH . "/" . DIRNAME_EXPORTS . "/1726083693116__pages.json";
    //echo $pages_json_file_path;
    //$fileHandle = fopen( $pages_json_file_path, "r" );
    $fileHandle = fopen( $tmpFilePath, "r" );
    
    $fileData = "";
    while( ($s = fgets($fileHandle)) !== FALSE ){
        $fileData .= $s;
    }
    //echo $fileData;
    
    // Delete the file once the file data is read into a variable
    @unlink( $tmpFilePath );
    
    // Check if $fileData is a valid JSON
    $pagesDataArray = json_decode( $fileData, true, JSON_UNESCAPED_UNICODE );
    if( ($pagesDataArray === FALSE) || ($pagesDataArray === NULL) ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "JSON data in the selected file is corrupt !" );
        return;
    }
    
    // Check the internal file type 
    $file_type = $pagesDataArray[ 'file_type' ];
    $pagesDataArray = $pagesDataArray[ 'data' ];
    
    if( $file_type !== "pages.json" ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "The selected file is not a pages.json file !" );
        return;
    }
    
    /**
     * 1. Loop through the $pagesDataArray and find out the plugin_id for each of the plugin_name and store the plugin_id attribute in this array, for non-existing plugin_name, plugin_id=0
     * 1.1 Split the $pagesDataArray into 3 arrays viz. $first, $second and $third according to their hierarchy 1, 2 and 3
     * 2. While splitting, use page_name as the key for the each page objects in each of the three arrays
     * 3. Insert into pages table the pages with the $first array pages, or update if the page_name already exist in the system.
     * 4. Retrieve the page ids of the inserted/updated pages and store them in the $first array
     * 5. Loop through $second and $third array to match parent_page_name from $first array and store the parent_id in $second and $third array
     * 6. Insert into pages table the pages with the $second array pages, or update if the page_name already exist in the system.
     * 7. Retrieve the page ids of the inserted/updated pages and store them in the $second array
     * 8. Loop through $third array to match parent_page_name from $second array and store the parent_id in $third array
     * 9. Insert into pages table the pages with the $third array pages, or update if the page_name already exist in the system.
     */
    
    
    $first  = array();
    $second = array();
    $third  = array();
    
    // 1. Loop through the $pagesDataArray and find out the plugin_id for each of the plugin_name and store the plugin_id attribute in this array, for non-existing plugin_name, plugin_id=0
    foreach ( $pagesDataArray as $key=>$page ) {
        // plugin_name to plugin_id
        $e_plugin_name = escape_string( $page[ 'plugin_name' ] );
        $sql = "SELECT plugin_id from plugins WHERE plugin_name='$e_plugin_name'";
        $result_set = selectQuery( $sql );
        if( mysqli_num_rows( $result_set ) == 0 ){
            $plugin_id = "0";
        }
        else{
            $val = mysqli_fetch_object( $result_set );
            $plugin_id = $val->plugin_id;
        }
        $page[ 'plugin_id' ] = $plugin_id;
        $pagesDataArray[ $key ] = $page;
        
        
        // functionality_name to functionality_id
        $e_functionality_name = escape_string( $page[ 'functionality_name' ] );
        $sql = "SELECT functionality_id from functionalities WHERE functionality_name='$e_functionality_name'";
        $result_set = selectQuery( $sql );
        if( mysqli_num_rows( $result_set ) == 0 ){
            $functionality_id = "0";
        }
        else{
            $val = mysqli_fetch_object( $result_set );
            $functionality_id = $val->functionality_id;
        }
        $page[ 'functionality_id' ] = $functionality_id;
        $pagesDataArray[ $key ] = $page;
        
        
        // 1.1 Split the $pagesDataArray into 3 arrays viz. $first, $second and $third according to their hierarchy 1, 2 and 3
        // 2. While splitting, use page_name as the key for the each page objects in each of the three arrays
        // Splitting of array based on hierarchy
        if( $page[ 'hierarchy' ] === "1" ){
            $first[ $page[ 'page_name' ] ] = $page;
        }
        else if( $page[ 'hierarchy' ] === "2" ){
            $second[ $page[ 'page_name' ] ] = $page;
        }
        else if( $page[ 'hierarchy' ] === "3" ){
            $third[ $page[ 'page_name' ] ] = $page;
        }
        
        
    }
    //print_r( $pagesDataArray );
    //print_r( $first );
    //print_r( $second );
    //print_r( $third );
    
    // 3. Insert into pages table the pages with the $first array pages, or update if the page_name already exist in the system.
    // 4. Retrieve the page ids of the inserted/updated pages and store them in the $first array
    foreach ( $first as $page_name => $page ) {
        $e_page_name = escape_string( $page_name );        
        $e_hierarchy = escape_string( $page[ 'hierarchy' ] );
        $e_sequence = escape_string( $page[ 'page_sequence' ] );
        $e_icon = escape_string( $page[ 'icon' ] );
        $e_visible = escape_string( $page[ 'visible' ] );
        $e_page_title = escape_string( $page[ 'page_title' ] );
        $e_title = escape_string( $page[ 'title' ] );
        $e_description = escape_string( $page[ 'description' ] );
        $e_tags = escape_string( $page[ 'tags' ] );
        $e_image = escape_string( $page[ 'image' ] );
        $e_content = escape_string( $page[ 'content' ] );
        $e_functionality_id = escape_string( $page[ 'functionality_id' ] );
        $e_plugin_id = escape_string( $page[ 'plugin_id' ] );
        //$e_parent_id = escape_string( $page[ 'parent_id' ] );
        
        $sql = "SELECT page_id FROM pages WHERE page_name='$e_page_name'";
        $result_set = selectQuery( $sql );
        if( mysqli_num_rows( $result_set ) == 0 ){
            $sql = "INSERT INTO pages( `page_name`, `parent_id`, `hierarchy`, `page_sequence`, `icon`, `visible`, `page_title`, `title`, `description`, `tags`, `image`, `content`, `functionality_id`, `plugin_id` ) "
            . "VALUES( '$e_page_name', '0', '$e_hierarchy', '$e_sequence', '$e_icon', '$e_visible', '$e_page_title', '$e_title', '$e_description', '$e_tags', '$e_image', '$e_content', '$e_functionality_id', '$e_plugin_id' )";
            $rows = insertQuery( $sql );
            if( $rows > 0 ){
                $page_id = getAIID();
                $first[ $page_name ][ 'page_id' ] = $page_id; 
            }
        }
        else{
            $val = mysqli_fetch_object( $result_set );
            $page_id = $val->page_id;
            // Update the value into the table
            $sql = "UPDATE pages SET "
                        . "`parent_id`='0', "
                        . "`hierarchy`='$e_hierarchy', "
                        . "`icon`='$e_icon', "
                        . "`visible`='$e_visible', "
                        . "`page_title`='$e_page_title', "
                        . "`title`='$e_page_title', "
                        . "`functionality_id`='$e_functionality_id', "
                        . "`plugin_id`='$e_plugin_id' "
                        . "WHERE page_name='$e_page_name'";
            $rows = updateQuery( $sql );
            if( $rows > 0 ){
                $first[ $page_name ][ 'page_id' ] = $page_id;
            }
        }
    }
    //print_r( $first );
    
    
    // 5. Loop through $second and $third array to match parent_page_name from $first array and store the parent_id in $second and $third array.
    foreach ( $second as $page_name => $page ) {
        if( array_key_exists( $page[ 'parent_page_name' ], $first ) ){
            $parent_page = $first[ $page[ 'parent_page_name' ] ];
            $page[ 'parent_id' ] = $parent_page[ 'page_id' ]; 
            $second[ $page_name ] = $page;
        }
    }
    //print_r( $second );
    
    
    // 6. Insert into pages table the pages with the $second array pages, or update if the page_name already exist in the system.
    // 7. Retrieve the page ids of the inserted/updated pages and store them in the $second array
    foreach ( $second as $page_name => $page ) {
        $e_page_name = escape_string( $page_name );
        $e_hierarchy = escape_string( $page[ 'hierarchy' ] );
        $e_sequence = escape_string( $page[ 'page_sequence' ] );
        $e_icon = escape_string( $page[ 'icon' ] );
        $e_visible = escape_string( $page[ 'visible' ] );
        $e_page_title = escape_string( $page[ 'page_title' ] );
        $e_title = escape_string( $page[ 'title' ] );
        $e_description = escape_string( $page[ 'description' ] );
        $e_tags = escape_string( $page[ 'tags' ] );
        $e_image = escape_string( $page[ 'image' ] );
        $e_content = escape_string( $page[ 'content' ] );
        $e_functionality_id = escape_string( $page[ 'functionality_id' ] );
        $e_plugin_id = escape_string( $page[ 'plugin_id' ] );
        $e_parent_id = escape_string( $page[ 'parent_id' ] );
        
        $sql = "SELECT page_id FROM pages WHERE page_name='$e_page_name'";
        $result_set = selectQuery( $sql );
        if( mysqli_num_rows( $result_set ) == 0 ){
            $sql = "INSERT INTO pages( `page_name`, `parent_id`, `hierarchy`, `page_sequence`, `icon`, `visible`, `page_title`, `title`, `description`, `tags`, `image`, `content`, `functionality_id`, `plugin_id` ) "
            . "VALUES( '$e_page_name', '$e_parent_id', '$e_hierarchy', '$e_sequence', '$e_icon', '$e_visible', '$e_page_title', '$e_title', '$e_description', '$e_tags', '$e_image', '$e_content', '$e_functionality_id', '$e_plugin_id' )";
            $rows = insertQuery( $sql );
            if( $rows > 0 ){
                $page_id = getAIID();
                $second[ $page_name ][ 'page_id' ] = $page_id; 
            }
        }
        else{
            $val = mysqli_fetch_object( $result_set );
            $page_id = $val->page_id;
            // Update the value into the table
            $sql = "UPDATE pages SET "
                        . "`parent_id`='$e_parent_id', "
                        . "`hierarchy`='$e_hierarchy', "
                        . "`icon`='$e_icon', "
                        . "`visible`='$e_visible', "
                        . "`page_title`='$e_page_title', "
                        . "`title`='$e_page_title', "
                        . "`functionality_id`='$e_functionality_id', "
                        . "`plugin_id`='$e_plugin_id' "
                        . "WHERE page_name='$e_page_name'";
            $rows = updateQuery( $sql );
            if( $rows > 0 ){
                $second[ $page_name ][ 'page_id' ] = $page_id;
            }
        }
    }
    //print_r( $second );
    
    
    // 8. Loop through $third array to match parent_page_name from $second array and store the parent_id in $third array
    foreach ( $third as $page_name => $page ) {
        if( array_key_exists( $page[ 'parent_page_name' ], $second ) ){
            $parent_page = $second[ $page[ 'parent_page_name' ] ];
            $page[ 'parent_id' ] = $parent_page[ 'page_id' ]; 
            $third[ $page_name ] = $page;
        }
    }
    //print_r( $third );
    
    // 9. Insert into pages table the pages with the $third array pages, or update if the page_name already exist in the system.
    foreach ( $third as $page_name => $page ) {
        $e_page_name = escape_string( $page_name );
        $e_hierarchy = escape_string( $page[ 'hierarchy' ] );
        $e_sequence = escape_string( $page[ 'page_sequence' ] );
        $e_icon = escape_string( $page[ 'icon' ] );
        $e_visible = escape_string( $page[ 'visible' ] );
        $e_page_title = escape_string( $page[ 'page_title' ] );
        $e_title = escape_string( $page[ 'title' ] );
        $e_description = escape_string( $page[ 'description' ] );
        $e_tags = escape_string( $page[ 'tags' ] );
        $e_image = escape_string( $page[ 'image' ] );
        $e_content = escape_string( $page[ 'content' ] );
        $e_functionality_id = escape_string( $page[ 'functionality_id' ] );
        $e_plugin_id = escape_string( $page[ 'plugin_id' ] );
        $e_parent_id = escape_string( $page[ 'parent_id' ] );
        
        $sql = "SELECT page_id FROM pages WHERE page_name='$e_page_name'";
        $result_set = selectQuery( $sql );
        if( mysqli_num_rows( $result_set ) == 0 ){
            $sql = "INSERT INTO pages( `page_name`, `parent_id`, `hierarchy`, `page_sequence`, `icon`, `visible`, `page_title`, `title`, `description`, `tags`, `image`, `content`, `functionality_id`, `plugin_id` ) "
            . "VALUES( '$e_page_name', '$e_parent_id', '$e_hierarchy', '$e_sequence', '$e_icon', '$e_visible', '$e_page_title', '$e_title', '$e_description', '$e_tags', '$e_image', '$e_content', '$e_functionality_id', '$e_plugin_id' )";
            $rows = insertQuery( $sql );
            if( $rows > 0 ){
                $page_id = getAIID();
                $third[ $page_name ][ 'page_id' ] = $page_id; 
            }
        }
        else{
            $val = mysqli_fetch_object( $result_set );
            $page_id = $val->page_id;
            // Update the value into the table
            $sql = "UPDATE pages SET "
                        . "`parent_id`='$e_parent_id', "
                        . "`hierarchy`='$e_hierarchy', "
                        . "`icon`='$e_icon', "
                        . "`visible`='$e_visible', "
                        . "`page_title`='$e_page_title', "
                        . "`title`='$e_page_title', "
                        . "`functionality_id`='$e_functionality_id', "
                        . "`plugin_id`='$e_plugin_id' "
                        . "WHERE page_name='$e_page_name'";
            $rows = updateQuery( $sql );
            if( $rows > 0 ){
                $third[ $page_name ][ 'page_id' ] = $page_id;
            }
        }
    }
    //print_r( $third );
    //sleep(3);
    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, "Pages have been imported successfully !" );
}

function scodezy_export_pages(){
    checkAuthorizationForFunction( __FUNCTION__ );
    
    $page_ids = request( 'page_ids' );
    
    //$page_ids = "4,43,47,65";
    
    $e_page_ids = escape_string( $page_ids );
    
    $pages = array();
    $parent_pages = array();
    $functionalities = array();
    $plugins = array();
    
    // Page IDs should exist in the DB
    $sql = "SELECT page_id, parent_id, functionality_id, plugin_id FROM pages WHERE page_id IN ($e_page_ids)";
    $result_set = selectQuery( $sql );
    if( mysqli_num_rows( $result_set ) == 0 ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "None of the selected pages exist in the system" );
        return;
    }
    
    while( ( $val = mysqli_fetch_assoc( $result_set ) ) !== NULL ){
        array_push( $pages, $val[ 'page_id' ] );
        if( $val[ 'parent_id' ] !== "0" )
            array_push( $parent_pages, $val[ 'parent_id' ] );
        //array_push( $functionalities, $val[ 'functionality_id' ] );
        //array_push( $plugins, $val[ 'plugin_id' ] );
        
    }
    
    $all_pages = array_merge( $pages, $parent_pages );
    $all_pages = array_unique( $all_pages );
    $parent_pages = array_unique( $parent_pages );
    unset( $parent_pages[ 0 ] );    // because no page_id with 0 exist
    
    
    
    
    //print_r( $pages );
    //print_r( $parent_pages );
    //print_r( $all_pages );
    $allPages = implode( ",", $all_pages );
    //$sql = "SELECT p.*, f.functionality_name, plug.plugin_name from pages p, functionalities f, plugins plug WHERE (p.page_id IN ($allPages)) AND ((p.plugin_id=plug.plugin_id) AND (f.functionality_id=p.functionality_id))";
    $sql = "SELECT * from pages WHERE page_id IN ($allPages)";
    $result_set = selectQuery( $sql );
    if( mysqli_num_rows( $result_set ) == 0 ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "No records available" );
        return;
    }
    
    $pages = array();
    while( ( $val = mysqli_fetch_assoc( $result_set ) ) !== NULL ){
        //$functionalities[ $val[ 'functionality_id' ] ] = $val[ 'functionality_name' ];
        //$plugins[ $val[ 'plugin_id' ] ] = $val[ 'plugin_name' ];
        
        //$val[ 'functionality_name' ] = (isset($functionalities[ $val[ 'functionality_id' ] ]))?$functionalities[ $val[ 'functionality_id' ] ]:"";
        //$val[ 'plugin_name' ] = (isset($plugins[ $val[ 'plugin_id' ] ]))?$plugins[ $val[ 'plugin_id' ] ]:"";
        array_push( $functionalities, $val[ 'functionality_id' ] );
        array_push( $plugins, $val[ 'plugin_id' ] );
        $pages[] = $val;
    }
    
    $parentPages = implode( ",", $parent_pages );
    $parent_page_names = array();
    if( count( $parent_pages ) !== 0 ){
        $sql = "SELECT * from pages WHERE page_id IN ($parentPages)";
    
        $result_set = selectQuery( $sql );
        if( mysqli_num_rows( $result_set ) == 0 ){
            echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "No records available" );
            return;
        }

        
        while( ( $val = mysqli_fetch_assoc( $result_set ) ) !== NULL ){
            $parent_page_names[ $val[ 'page_id' ] ] = $val[ 'page_name' ];
        }
    }
    
    $functionalityIDs = implode( ",", $functionalities );
    $sql = "SELECT * from functionalities WHERE functionality_id IN ($functionalityIDs)";
    $result_set = selectQuery( $sql );
    
    $functionalities = array();
    while( ( $val = mysqli_fetch_assoc( $result_set ) ) !== NULL ){
        $functionalities[ $val[ 'functionality_id' ] ] = $val[ 'functionality_name' ];
    }
    
    $pluginIDs = implode( ",", $plugins );
    $sql = "SELECT * from plugins WHERE plugin_id IN ($pluginIDs)";
    $result_set = selectQuery( $sql );
    
    $plugins = array();
    while( ( $val = mysqli_fetch_assoc( $result_set ) ) !== NULL ){
        $plugins[ $val[ 'plugin_id' ] ] = $val[ 'plugin_name' ];
    }
    
    for( $i = 0 ; $i < count( $pages ) ; $i++ ){
        $pages[ $i ][ 'functionality_name' ] = $functionalities[ $pages[ $i ][ 'functionality_id' ] ];
        $pages[ $i ][ 'plugin_name' ] = $plugins[ $pages[ $i ][ 'plugin_id' ] ];
        $parent_page_name = isset($parent_page_names[ $pages[ $i ][ 'parent_id' ] ])?$parent_page_names[ $pages[ $i ][ 'parent_id' ] ]:'';
        $pages[ $i ][ 'parent_page_name' ] = $parent_page_name;
        unset( $pages[ $i ][ 'functionality_id' ] );
        unset( $pages[ $i ][ 'plugin_id' ] );
        unset( $pages[ $i ][ 'page_id' ] );
        unset( $pages[ $i ][ 'parent_id' ] );
    }
    /*
    foreach ( $pages as $i => $page ) {
        $pages[ $i ][ 'functionality_name' ] = $functionalities[ $pages[ $i ][ 'functionality_id' ] ];
        $pages[ $i ][ 'plugin_name' ] = $plugins[ $pages[ $i ][ 'plugin_id' ] ];
        $parent_page_name = isset($parent_page_names[ $pages[ $i ][ 'parent_id' ] ])?$parent_page_names[ $pages[ $i ][ 'parent_id' ] ]:'';
        $pages[ $i ][ 'parent_page_name' ] = $parent_page_name;
        unset( $pages[ $i ][ 'functionality_id' ] );
        unset( $pages[ $i ][ 'plugin_id' ] );
        unset( $pages[ $i ][ 'page_id' ] );
        //unset( $pages[ $i ][ 'parent_id' ] );
    }
    */
    //print_r( $parent_page_names );
    //print_r( $functionalities );
    //print_r( $plugins );
    //print_r( $pages );
    
    $temp = $pages;
    $pages = array();
    $pages[ 'file_type' ] = "pages.json";
    $pages[ 'data' ] = $temp;
    $pagesTableDataJSON = json_encode( $pages, JSON_UNESCAPED_UNICODE );
    
    
    // Make sure the exports directory for pages.json file is available and writable
    if( !file_exists( DAT_PATH ) ){
        mkdir( DAT_PATH );
    }
    $tmp_dir_path = DAT_PATH . FILE_SEPARATOR . DIRNAME_EXPORTS;
    if( !file_exists( $tmp_dir_path ) ){
        mkdir( $tmp_dir_path );
    }
    
    // Create the pages.json file
    $currentTimestamp = currentTimeMilliseconds();
    $tmpFileName = $currentTimestamp . "__pages.json";
    $tmpFilePath = $tmp_dir_path . FILE_SEPARATOR . $tmpFileName;
    $fileTableData = fopen( $tmpFilePath, 'w+' );
    if( $fileTableData === false ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Failed to create the pages.json file. Please contact the administrator" );        
        return;
    }
    if( fwrite( $fileTableData, $pagesTableDataJSON ) === false ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Failed to write data to the pages.json file. Please contact the administrator" );        
        return;
    }
    
    // Generate a private link for the .json file
    $jsonFileDownloadLink = getSiteRootPath( DIRNAME_DATA ) . FILE_SEPARATOR . DIRNAME_EXPORTS . FILE_SEPARATOR . $tmpFileName;
    $privateLink = generatePrivateLink( $jsonFileDownloadLink, $tmpFileName, "file", false, "application/json" );
    
    // Store this link as a content inside contents table
    createContent( $privateLink, "Pages JSON Download Link", '', '', 'json_private_link', '', 'published' );
    
    $data = array(
        "info" => "Pages data has been exported. If your download does not begin automatically, please check the Contents section for the download link of the exported pages. <br />Download: <a href=\"$privateLink\" target='_blank'>Here</a>",
        "data" => $privateLink
    );
    
    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $data );
}
?>