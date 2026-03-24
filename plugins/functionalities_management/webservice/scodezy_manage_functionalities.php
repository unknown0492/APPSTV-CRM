<?php

$functionality_types = array(
    "CREATE", 'READ', 'UPDATE', 'DELETE', 'ACCESS'
);

function scodezy_get_functionalities(){
    //checkPrivilegeForFunction( __FUNCTION__ );
    
    $plugin_id = @$_REQUEST[ 'plugin_id' ];
    
    if( !isset( $_REQUEST[ 'plugin_id' ] ) 
            || ($plugin_id == NULL) 
            || ($plugin_id == "")){
        $plugin_id = "-1";  // -1 means retrieve all the functionalities irrespective of the plugin to which they belong
    }
    
    // Do validation for Plugin ID
    validateEmptyDigitString( $plugin_id, __FUNCTION__, "Plugin ID is required !" );
    if( $plugin_id != "-1" )
        validate( $plugin_id, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), "Plugin ID is invalid !" );
    
    $e_plugin_id = escape_string( $plugin_id );
    
    if( $e_plugin_id == "-1" ){
        $sql = "SELECT * FROM functionalities ORDER BY plugin_id";        
    }
    else{
        $sql = "SELECT * FROM functionalities WHERE plugin_id='$e_plugin_id'";
    }
    
    $result_set = selectQuery( $sql );
    if( ($result_set == NULL)
            || ( mysqli_num_rows($result_set) == 0 )){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Functionalities have not been created yet for the selected plugin !" );
        return;
    }
    
    $functionalities = array();
    while( ($val = mysqli_fetch_assoc( $result_set )) != NULL ){
        $functionalities[] = $val;
    }
    
    $data = array(
        "info" => "Functionalities have been retrieved",
        "data" => $functionalities
    );
    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $data );        
}

function scodezy_get_functionality(){
    //checkPrivilegeForFunction( __FUNCTION__ );
    
    $functionality_id = request( 'functionality_id' );
    
    // Do validation for Functionality ID
    validateEmptyDigitString( $functionality_id, __FUNCTION__, "Functionality ID is required !" );
    validate( $functionality_id, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), "Functionality ID is invalid !" );
    
    $e_functionality_id = escape_string( $functionality_id );
    
    $sql = "SELECT * FROM functionalities WHERE functionality_id='$e_functionality_id'";
    $result_set = selectQuery( $sql );
    if( ($result_set == NULL)
            || ( mysqli_num_rows($result_set) == 0 )){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Functionality ID is invalid !" );
        return;
    }
    
    $functionality = mysqli_fetch_assoc( $result_set );
    $data = array(
        "info" => "Functionality information has been retrieved",
        "data" => $functionality
    );
    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $data );
}

function scodezy_get_page_functionalities(){
    //checkPrivilegeForFunction( __FUNCTION__ );
    
    $plugin_id = @$_REQUEST[ 'plugin_id' ];
    
    if( !isset( $_REQUEST[ 'plugin_id' ] ) 
            || ($plugin_id == NULL) 
            || ($plugin_id == "")){
        $plugin_id = "-1";  // -1 means retrieve all the functionalities irrespective of the plugin to which they belong
    }
    
    // Do validation for Plugin ID
    validateEmptyDigitString( $plugin_id, __FUNCTION__, "Plugin ID is required !" );
    if( $plugin_id != "-1" )
        validate( $plugin_id, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), "Plugin ID is invalid !" );
    
    $e_plugin_id = escape_string( $plugin_id );
    
    if( $e_plugin_id == "-1" ){
        $sql = "SELECT * FROM functionalities WHERE is_page='1' ORDER BY plugin_id";        
    }
    else{
        $sql = "SELECT * FROM functionalities WHERE (is_page='1') AND (plugin_id='$e_plugin_id')";
    }
    
    $result_set = selectQuery( $sql );
    if( ($result_set == NULL)
            || ( mysqli_num_rows($result_set) == 0 )){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Functionalities have not been created yet for the selected plugin !" );
        return;
    }
    
    $functionalities = array();
    while( ($val = mysqli_fetch_assoc( $result_set )) != NULL ){
        $functionalities[] = $val;
    }
    
    $data = array(
        "info" => "Functionalities have been retrieved",
        "data" => $functionalities
    );
    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $data );        
}

function scodezy_delete_functionality(){
    //checkPrivilegeForFunction( __FUNCTION__ );
    
    $functionality_id = request( 'functionality_id' );
    
    // Do validation for Plugin ID
    validateEmptyDigitString( $functionality_id, __FUNCTION__, "Page ID is required !" );
    validate( $functionality_id, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), "Page ID is invalid !" );
    
    $e_functionality_id = escape_string( $functionality_id );
    
    $sql = "SELECT * FROM functionalities WHERE functionality_id='$e_functionality_id'";
    $result_set = selectQuery( $sql );
    if( ($result_set == NULL)
            || ( mysqli_num_rows($result_set) == 0 )){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Functionality ID is invalid !" );
        return;
    }
    $functionality = mysqli_fetch_assoc( $result_set );
    
    $sql = "DELETE FROM functionalities WHERE functionality_id='$e_functionality_id'";
    //echo $sql;
    //return;
    $rows = deleteQuery( $sql );
    if( $rows > 0 ){
        $data = array(
            "info" => "Functionality has been deleted",
            "data" => $functionality
        );
        echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $data );
        return;
    }
    echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Failed to delete the functionality" );
}

function scodezy_delete_functionalities(){
    //checkPrivilegeForFunction( __FUNCTION__ );
    
    $functionality_ids = request( 'functionality_ids' );
    //print_r( $_REQUEST );
    //return;
    
    if( $functionality_ids === NULL ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Please select the functionalities to be deleted !" );
        return;
    }
    
    // $functionality_ids needs to be a CSV string
    $functionalityIDs = explode( ",", $functionality_ids );
    
    // Do validation for Page IDs
    foreach ( $functionalityIDs as $value ) {
        validateEmptyDigitString( $value, __FUNCTION__, "Functionality ID is required !" );
        validate( $value, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), "Functionality ID is invalid !" );
    }
    
    $e_functionality_ids = escape_string( $functionality_ids );
    
    $sql = "SELECT * FROM functionalities WHERE functionality_id IN ($e_functionality_ids)";
    //echo $sql;
    //return;
    $result_set = selectQuery( $sql );
    if( ($result_set == NULL)
            || ( mysqli_num_rows($result_set) == 0 )){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Functionality ID is invalid !" );
        return;
    }
    $fetchedFunctionalities = array();
    while( ( $val = mysqli_fetch_assoc( $result_set ) ) != NULL ){
        $fetchedFunctionalities[] = $val;
    }
    
    $sql = "DELETE FROM functionalities WHERE functionality_id IN ($e_functionality_ids)";
    //echo $sql;
    //return;
    $rows = deleteQuery( $sql );
    if( $rows > 0 ){
        $data = array(
            "info" => "Selected functionalities have been deleted",
            "data" => $fetchedFunctionalities
        );
        echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $data );
        return;
    }
    echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Failed to delete the selected functionalities" );
}

function scodezy_create_functionality(){
    //checkPrivilegeForFunction( __FUNCTION__ );
    
    // print_r( $_REQUEST );
    // return;
    global $functionality_types;
    
    $functionality_name          = request( 'functionality_name' );
    $alias                       = request( 'alias' );
    $functionality_description   = request( 'functionality_description' );
    $is_page                     = request( 'is_page' );
    $is_a_content                = request( 'is_a_content' );
    $plugin_id                   = request( 'plugin_id' );
    $functionality_type          = request( 'functionality_type' );
    
    validateEmptyString( $functionality_name, __FUNCTION__, "Functionality name is required" );
    validateEmptyString( $alias, __FUNCTION__, "Alias is required" );
    validateEmptyDigitString( $is_page, __FUNCTION__, "Please specify if the functionality is for the page ?" );
    validateEmptyDigitString( $is_a_content, __FUNCTION__, "Please specify if the functionality is for a content ?" );
    validateEmptyDigitString( $plugin_id, __FUNCTION__, "Please select a plugin" );
    validateEmptyDigitString( $functionality_type, __FUNCTION__, "Please select a type for the functionality" );
    
    validate( $functionality_name, __FUNCTION__, getValidationRegex( "VLDTN_FUNCTIONALITY_NAME" ), "Functionality name can only contain lowercase letters, numbers and an underscore" );
    validate( $alias, __FUNCTION__, getValidationRegex( "VLDTN_FUNCTIONALITY_DESCRIPTION" ), "Some special characters are not allowed for the Alias" );
    validate( $functionality_description, __FUNCTION__, getValidationRegex( "VLDTN_FUNCTIONALITY_DESCRIPTION" ), "Some special characters are not allowed for the functionality description" );
    validate( $is_page, __FUNCTION__, getValidationRegex( "VLDTN_SINGLE_BINARY" ), "Is page status of the functionality is invalid" );
    validate( $is_a_content, __FUNCTION__, getValidationRegex( "VLDTN_SINGLE_BINARY" ), "Is a content status of the functionality is invalid" );
    validate( $plugin_id, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), "Plugin is invalid" );
    
    if( !in_array( $functionality_type, $functionality_types ) ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Functionality type is invalid" );
        return;
    }
    
    // Escape strings for storing in the DB
    $e_functionality_name            = escape_string( $functionality_name );
    $e_alias                         = escape_string( $alias );
    $e_functionality_description     = escape_string( $functionality_description );
    $e_is_page                       = escape_string( $is_page );
    $e_is_a_content                  = escape_string( $is_a_content );
    $e_plugin_id                     = escape_string( $plugin_id );
    $e_functionality_type            = escape_string( $functionality_type );
    
    // functionality_name has to be unique throughout the system
    $sql = "SELECT functionality_name FROM functionalities WHERE functionality_name='$e_functionality_name'";
    $result_set = selectQuery( $sql );
    if( mysqli_num_rows( $result_set ) > 0 ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "A functionality already exist with the given functionality_name. Please change the functionality_name and try again" );
        return;
    }
    
    // Plugin ID should exist in the system
    $sql = "SELECT plugin_id FROM plugins WHERE plugin_id='$e_plugin_id'";
    $result_set = selectQuery( $sql );
    if( mysqli_num_rows( $result_set ) == 0 ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Selected plugin does not exist" );
        return;
    }
    
    // Insert the value into the table
    $sql = "INSERT INTO functionalities( `functionality_name`, `alias`, `functionality_description`, `is_page`, `is_a_content`, `plugin_id`, `functionality_type` ) "
            . "VALUES( '$e_functionality_name', '$e_alias', '$e_functionality_description', '$e_is_page', '$e_is_a_content', '$e_plugin_id', '$e_functionality_type' )";
    $rows = insertQuery( $sql );
    if( $rows > 0 ){
        $functionality_id = getAIID();
        $sql = "SELECT * FROM functionalities WHERE functionality_id='$functionality_id'";
        $result_set = selectQuery( $sql );
        
        $functionality = mysqli_fetch_assoc( $result_set );
        $data = array(
            "info" => "Functionality has been created successfully",
            "data" => $functionality
        );
        echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $data );
        return;
    }
    echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Unknown error occurred !" );
}

function scodezy_update_functionality(){
    //checkPrivilegeForFunction( __FUNCTION__ );
    
    global $functionality_types;
    //print_r( $_REQUEST );
    //return;
    
    $functionality_id            = request( 'functionality_id' );
    $functionality_name          = request( 'functionality_name' );
    $alias                       = request( 'alias' );
    $functionality_description   = request( 'functionality_description' );
    $is_page                     = request( 'is_page' );
    $is_a_content                = request( 'is_a_content' );
    $plugin_id                   = request( 'plugin_id' );
    $functionality_type          = request( 'functionality_type' );
    
    validateEmptyString( $functionality_id, __FUNCTION__, "Please select a functionality to be updated" );
    validateEmptyString( $functionality_name, __FUNCTION__, "Functionality name is required" );
    validateEmptyString( $alias, __FUNCTION__, "Alias is required" );
    validateEmptyDigitString( $is_page, __FUNCTION__, "Please specify if the functionality is for the page ?" );
    validateEmptyDigitString( $is_a_content, __FUNCTION__, "Please specify if the functionality is for a content ?" );
    validateEmptyDigitString( $plugin_id, __FUNCTION__, "Please select a plugin" );
    validateEmptyDigitString( $functionality_type, __FUNCTION__, "Please select a type for the functionality" );
    
    validate( $functionality_id, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), "Functionality is invalid" );
    validate( $functionality_name, __FUNCTION__, getValidationRegex( "VLDTN_FUNCTIONALITY_NAME" ), "Functionality name can only contain lowercase letters, numbers and an underscore" );
    validate( $alias, __FUNCTION__, getValidationRegex( "VLDTN_FUNCTIONALITY_DESCRIPTION" ), "Some special characters are not allowed for the Alias" );
    validate( $functionality_description, __FUNCTION__, getValidationRegex( "VLDTN_FUNCTIONALITY_DESCRIPTION" ), "Some special characters are not allowed for the functionality description" );
    validate( $is_page, __FUNCTION__, getValidationRegex( "VLDTN_SINGLE_BINARY" ), "Is page status of the functionality is invalid" );
    validate( $is_a_content, __FUNCTION__, getValidationRegex( "VLDTN_SINGLE_BINARY" ), "Is a content status of the functionality is invalid" );
    validate( $plugin_id, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), "Plugin is invalid" );
    
    if( !in_array( $functionality_type, $functionality_types ) ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Functionality type is invalid" );
        return;
    }
    
    // Escape strings for storing in the DB
    $e_functionality_id              = escape_string( $functionality_id );
    $e_functionality_name            = escape_string( $functionality_name );
    $e_alias                         = escape_string( $alias );
    $e_functionality_description     = escape_string( $functionality_description );
    $e_is_page                       = escape_string( $is_page );
    $e_is_a_content                  = escape_string( $is_a_content );
    $e_plugin_id                     = escape_string( $plugin_id );
    $e_functionality_type            = escape_string( $functionality_type );
    
    // functionality_name has to be unique throughout the system
    // also, the given functionality_name can only belong to the given page id for moving forward with the code
    $sql = "SELECT functionality_name FROM functionalities WHERE functionality_name='$e_functionality_name' AND functionality_id<>'$e_functionality_id'";
    $result_set = selectQuery( $sql );
    if( mysqli_num_rows( $result_set ) > 0 ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "A functionality already exist with the given functionality name. Please change the functionality name and try again" );
        return;
    }
    
    // Plugin ID should exist in the system
    $sql = "SELECT plugin_id FROM plugins WHERE plugin_id='$e_plugin_id'";
    $result_set = selectQuery( $sql );
    if( mysqli_num_rows( $result_set ) == 0 ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Selected plugin does not exist" );
        return;
    }
    
    // Update the value into the table
    $sql = "UPDATE functionalities SET "
                . "`functionality_name`='$e_functionality_name', "
                . "`alias`='$e_alias', "
                . "`functionality_description`='$e_functionality_description', "
                . "`is_page`='$e_is_page', "
                . "`is_a_content`='$e_is_a_content', "
                . "`plugin_id`='$e_plugin_id', "
                . "`functionality_type`='$e_functionality_type' "
                . "WHERE functionality_id='$e_functionality_id'";
    $rows = updateQuery( $sql );
    if( $rows > 0 ){
        $sql = "SELECT * FROM functionalities WHERE functionality_id='$e_functionality_id'";
        $result_set = selectQuery( $sql );
        
        $functionality = mysqli_fetch_assoc( $result_set );
        $data = array(
            "info" => "Functionality has been updated",
            "data" => $functionality
        );
        echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $data );
        return;
    }
    echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Unknown error occurred !" );
    
}

function scodezy_import_functionalities(){
    //checkPrivilegeForFunction( __FUNCTION__ );
    
    //print_r( $_REQUEST );
    
    $functionalities_file        = @$_FILES[ 'functionalities_file' ];
    
    if( ($functionalities_file === NULL) || ($functionalities_file === "") ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Please select a functionalities json file and try again" );
        return;
    }
    
    // $name       = preg_replace( '/[^\x20-\x7E]/', '', $plugin_file[ 'name' ] );     // Remove all non-ASCII characters from the file name
    $name       = get_valid_scodezy_filename( $functionalities_file[ 'name' ] );     // Remove all non-ASCII characters from the file name
    $type       = $functionalities_file[ 'type' ]; 
    $tmp_name   = $functionalities_file[ 'tmp_name' ];
    $error      = $functionalities_file[ 'error' ];
    
    if( $type !== "application/json" ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Please select a valid scodezy functionalities json file and try again" );
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
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Failed to upload the functionalities json file" );
        return;
    }
    
    //----------------------
    //$tmpFilePath = DAT_PATH . "/" . DIRNAME_EXPORTS . "/1726142914677__functionalities.json";
    //echo $pages_json_file_path;
    //$fileHandle = fopen( $pages_json_file_path, "r" );
    $fileHandle = fopen( $tmpFilePath, "r" );
    if( $fileHandle === FALSE ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Failed to open the json file !" );
        @unlink( $tmpFilePath );
        return;
    }
    
    $fileData = "";
    while( ($s = fgets($fileHandle)) !== FALSE ){
        $fileData .= $s;
    }
    //echo $fileData;
    
    
    // Delete the file once the file data is read into a variable
    @unlink( $tmpFilePath );
    
    
    // Check if $fileData is a valid JSON
    $functionalitiesDataArray = json_decode( $fileData, true, JSON_UNESCAPED_UNICODE );
    if( ($functionalitiesDataArray === FALSE) || ($functionalitiesDataArray === NULL) ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "JSON data in the selected file is corrupt !" );
        return;
    }
    
    // Check the internal file type 
    $file_type = $functionalitiesDataArray[ 'file_type' ];
    $functionalitiesDataArray = $functionalitiesDataArray[ 'data' ];
    
    if( $file_type !== "functionalities.json" ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "The selected file is not a functionalities.json file !" );
        return;
    }
    
    
    
    /**
     * 1. Loop through the $functionalitiesDataArray and form a new array $plugin_names containing plugin_names for each of the functionality from $functionalitiesDataArray
     * 2. Loop through the $plugin_names array and gather the plugin_id for each plugin_name. For missing plugin_name, the plugin_id=0
     * 3. Loop through the $functionalitiesDataArray and map the plugin_name with plugin_id from $plugin_names array and create another attribute plugin_id for each of the functionality in $functionalitiesDataArray
     * 4. Loop through the $functionalitiesDataArray and create the functionalities by running insert query or update query if the functionality_name already exist
     */
    
    
    // 1. Loop through the $functionalitiesDataArray and form a new array $plugin_names containing plugin_names for each of the functionality from $functionalitiesDataArray
    if( count( $functionalitiesDataArray ) == 0 ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "There are no functionalities in the json file !" );
        return;
    }
    
    //$plugin_names = array();
    $pluginNames = "";
    foreach ( $functionalitiesDataArray as $key => $functionality ) {
        if( $functionality[ 'plugin_name' ] !== "" ){
            //array_push( $plugin_names, $functionality[ 'plugin_name' ] );
            $pluginNames .= "'" . $functionality[ 'plugin_name' ] . "',";
        }
    }
    $pluginNames = rtrim( $pluginNames, "," );
    
    // 2. Loop through the $plugin_names array and gather the plugin_id for each plugin_name. For missing plugin_name, the plugin_id=0
    $sql = "SELECT plugin_id, plugin_name FROM plugins WHERE plugin_name IN ($pluginNames)";
    $result_set = selectQuery( $sql );
    $plugins = array();
    while( ($val = mysqli_fetch_assoc( $result_set ) ) !== NULL ){
        $plugins[ $val[ 'plugin_name' ] ] = $val[ 'plugin_id' ];
    }
    
    //print_r( $plugins );
    
    // 3. Loop through the $functionalitiesDataArray and map the plugin_name with plugin_id from $plugin_names array and create another attribute plugin_id for each of the functionality in $functionalitiesDataArray
    foreach ( $functionalitiesDataArray as $key => $functionality ) {
        $functionality[ 'plugin_id' ] = isset($plugins[ $functionality[ 'plugin_name' ] ])?$plugins[ $functionality[ 'plugin_name' ] ]:"0";
        unset( $functionality[ 'plugin_name' ] );
        $functionalitiesDataArray[ $key ] = $functionality;
    }
    //print_r( $functionalitiesDataArray );
    
    
    // 4. Loop through the $functionalitiesDataArray and create the functionalities by running insert query or update query if the functionality_name already exist
    $functionalities_imported_count = 0;
    $functionalities_updated_count = 0;
    foreach ( $functionalitiesDataArray as $key => $functionality ) {
        $e_functionality_name           = escape_string( $functionality[ 'functionality_name' ] );
        $e_alias                        = escape_string( $functionality[ 'alias' ] );
        $e_functionality_description    = escape_string( $functionality[ 'functionality_description' ] );
        $e_functionality_type           = escape_string( $functionality[ 'functionality_type' ] );
        $e_is_page                      = escape_string( $functionality[ 'is_page' ] );
        $e_is_a_content                 = escape_string( $functionality[ 'is_a_content' ] );
        $e_plugin_id                    = escape_string( $functionality[ 'plugin_id' ] );
        
        $sql = "SELECT functionality_name FROM functionalities WHERE functionality_name='$e_functionality_name'";
        $result_set = selectQuery( $sql );
        if( mysqli_num_rows( $result_set ) == 0 ){
            $sql = "INSERT INTO functionalities( `functionality_name`, `alias`, `functionality_description`, `is_page`, `is_a_content`, `plugin_id`, `functionality_type` ) "
                    . "VALUES( '$e_functionality_name', '$e_alias', '$e_functionality_description', '$e_is_page', '$e_is_a_content', '$e_plugin_id', '$e_functionality_type' )";
            $rows = insertQuery( $sql );
            if( $rows > 0 ){
                $functionalities_imported_count++;
            }
        }
        else{
            // Update the value into the table
            //$val = mysqli_fetch_object( $result_set );
            //$functionality_id = $val->functionality_id;
            $sql = "UPDATE functionalities SET "
                        . "`alias`='$e_alias', "
                        . "`functionality_description`='$e_functionality_description', "
                        . "`is_page`='$e_is_page', "
                        . "`is_a_content`='$e_is_a_content', "
                        . "`plugin_id`='$e_plugin_id', "
                        . "`functionality_type`='$e_functionality_type' "
                        . "WHERE functionality_name='$e_functionality_name'";
            $rows = updateQuery( $sql );
            if( $rows > 0 ){
                $functionalities_updated_count++;
            }
        }
    }
    
    $message = "Functionalities have been imported successfully<br />"
            . "Functionalities Imported: $functionalities_imported_count <br />"
                    . "Functionalities Updated: $functionalities_updated_count";
    
    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $message );
}

function scodezy_export_functionalities(){
    //checkPrivilegeForFunction( __FUNCTION__ );
    
    $functionality_ids = request( 'functionality_ids' );
    
    //$functionality_ids = "371,375,374,381,383";
    
    $e_functionality_ids = escape_string( $functionality_ids );
    
    /**
     * 1. Check if at least one functionality exist from the given set of functionlity ids, if not then return an error
     * 2. Store all the retrieved functionalities in an array $functionalities
     * 3. Store the plugin_id for each functionality_id and retrieve the plugin_name, in an array $plugins in the form of $plugin_id=>$plugin pairs
     * 4. Loop through $functionalities array and add another attribute plugin_name for each functionality, by mapping plugin_id on $plugins array
     * 5. Remove the functionality_id and plugin_id attributes from the $functionalities array elements
     * 6. Convert the $functionalities array to json and export it into a file
     * 
     */
    
    
    $functionalities = array();
    $plugins = array();
    
    // 1. Check if at least one functionality exist from the given set of functionlity ids, if not then return an error
    // Functionality IDs should exist in the DB
    $sql = "SELECT * FROM functionalities WHERE functionality_id IN ($e_functionality_ids)";
    $result_set = selectQuery( $sql );
    if( mysqli_num_rows( $result_set ) == 0 ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "None of the selected functionalities exist in the system" );
        return;
    }
    
    // 2. Store all the retrieved functionalities in an array $functionalities
    $plugin_ids = "";
    while( ( $val = mysqli_fetch_assoc( $result_set ) ) !== NULL ){
        array_push( $functionalities, $val );
        $plugin_ids .= $val[ 'plugin_id' ] . ",";
    }
    $plugin_ids = rtrim( $plugin_ids, "," );
    
    //print_r( $functionalities );
    
    // 3. Store the plugin_id for each functionality_id and retrieve the plugin_name, in an array $plugins in the form of $plugin_id=>$plugin pairs
    $sql = "SELECT plugin_id, plugin_name from plugins WHERE plugin_id IN ($plugin_ids)";
    $result_set = selectQuery( $sql );
    while( ( $val = mysqli_fetch_assoc( $result_set ) ) !== NULL ){
        //array_push( $functionalities, $val );
        $plugins[ $val[ 'plugin_id' ] ] = $val[ 'plugin_name' ];
    }
    
    //print_r( $plugins );
    
    
    // 4. Loop through $functionalities array and add another attribute plugin_name for each functionality, by mapping plugin_id on $plugins array
    foreach ( $functionalities as $key => $functionality ) {
        $functionality[ 'plugin_name' ] = isset($plugins[ $functionality[ 'plugin_id' ] ])?$plugins[ $functionality[ 'plugin_id' ] ]:"";
        
        // 5. Remove the functionality_id and plugin_id attributes from the $functionalities array elements
        unset( $functionality[ 'plugin_id' ] );    
        unset( $functionality[ 'functionality_id' ] );    
        
        $functionalities[ $key ] = $functionality;
    } 
    
    //print_r( $functionalities );
    
    //return;
    
    $temp = $functionalities;
    $functionalities = array();
    $functionalities[ 'file_type' ] = "functionalities.json";
    $functionalities[ 'data' ] = $temp;
    $functionalitiesTableDataJSON = json_encode( $functionalities, JSON_UNESCAPED_UNICODE );
    
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
    $tmpFileName = $currentTimestamp . "__functionalities.json";
    $tmpFilePath = $tmp_dir_path . FILE_SEPARATOR . $tmpFileName;
    $fileTableData = fopen( $tmpFilePath, 'w+' );
    if( $fileTableData === false ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Failed to create the functionalities.json file. Please contact the administrator" );        
        return;
    }
    if( fwrite( $fileTableData, $functionalitiesTableDataJSON ) === false ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Failed to write data to the functionalities.json file. Please contact the administrator" );     
        @unlink( $tmpFilePath );
        return;
    }
    
    // Generate a private link for the .json file
    $jsonFileDownloadLink = getSiteRootPath( DIRNAME_DATA ) . FILE_SEPARATOR . DIRNAME_EXPORTS . FILE_SEPARATOR . $tmpFileName;
    $privateLink = generatePrivateLink( $jsonFileDownloadLink, $tmpFileName, "file", false, "application/json" );
    
    // Store this link as a content inside contents table
    createContent( $privateLink, "Functionalities JSON Download Link", '', '', 'json_private_link', '', 'published' );
    
    $data = array(
        "info" => "Functionalities data has been exported. If your download does not begin automatically, please check the Contents section for the download link of the exported functionalities. <br />Download: <a href=\"$privateLink\" target='_blank'>Here</a>",
        "data" => $privateLink
    );
    
    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $data );
}

?>