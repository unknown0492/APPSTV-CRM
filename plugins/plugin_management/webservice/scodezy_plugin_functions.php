<?php 

$pluginDirectories = array( "css", "data", "img", "js", "includes", "pages", "webservice" );

function scodezy_get_all_plugin_information(){
    checkAuthorizationForFunction(__FUNCTION__ );
    
    $sql = "SELECT * FROM plugins ORDER BY plugin_id";
    $result_set = selectQuery( $sql );
    if( ($result_set == NULL) 
            || (mysqli_num_rows( $result_set ) == 0) ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "No plugins exist !" );
        return;
    }
    
    $plugins = array();
    while( ($val = mysqli_fetch_assoc($result_set)) != NULL ){
        $plugins[] = $val;
    }
    
    $data = array(
        "info" => "Plugin information has been retrieved !",
        "data" => $plugins
    );
    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $data );
}

function scodezy_get_plugin(){
    checkAuthorizationForFunction(__FUNCTION__ );
    
    $plugin_id = request( 'plugin_id' );
    
    // Do validation for plugin ID
    validateEmptyDigitString( $plugin_id, __FUNCTION__, "Plugin ID is required !" );
    validate( $plugin_id, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), "Plugin ID is invalid !" );
    
    $e_plugin_id = escape_string( $plugin_id );
    
    $sql = "SELECT * FROM plugins WHERE plugin_id='$e_plugin_id'";
    $result_set = selectQuery( $sql );
    if( ($result_set == NULL) 
            || (mysqli_num_rows( $result_set ) == 0) ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "No such plugin !" );
        return;
    }
    
    $plugin = array();
    while( ($val = mysqli_fetch_assoc($result_set)) != NULL ){
        $plugin = $val;
    }
    
    $data = array(
        "info" => "Plugin information has been retrieved !",
        "data" => $plugin
    );
    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $data );
}

function scodezy_create_plugin(){
    checkAuthorizationForFunction(__FUNCTION__ );
   // print_r( $_REQUEST );
    
    $plugin_name        = request( 'plugin_name' );
    $plugin_alias       = request( 'plugin_alias' );
    $plugin_version     = request( 'plugin_version' );
    
    validateEmptyString( $plugin_name, __FUNCTION__, "Plugin name is required" );
    validateEmptyString( $plugin_alias, __FUNCTION__, "Plugin Alias is required" );
    validateEmptyString( $plugin_version, __FUNCTION__, "Plugin Version is required" );
    
    validate( $plugin_name, __FUNCTION__, getValidationRegex( "VLDTN_PLUGIN_NAME" ), getValidationErrMsg( "VLDTN_PLUGIN_NAME" ) . " for plugin name" );
    validate( $plugin_alias, __FUNCTION__, getValidationRegex( "VLDTN_PLUGIN_ALIAS" ), getValidationErrMsg( "VLDTN_PLUGIN_ALIAS" ) . " for plugin alias" );
    validate( $plugin_version, __FUNCTION__, getValidationRegex( "VLDTN_PLUGIN_VERSION" ), getValidationErrMsg( "VLDTN_PLUGIN_VERSION" ) . " for plugin version" );
    
    // Escape strings for storing in the DB
    $e_plugin_name            = escape_string( $plugin_name );
    $e_plugin_alias           = escape_string( $plugin_alias );
    $e_plugin_version         = escape_string( $plugin_version );
    
    // plugin_name has to be unique throughout the system
    $sql = "SELECT plugin_name FROM plugins WHERE plugin_name='$e_plugin_name'";
    $result_set = selectQuery( $sql );
    if( mysqli_num_rows( $result_set ) > 0 ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "A plugin already exist with the given plugin_name. Please change the plugin_name and try again" );
        return;
    }
    
    // Check if the plugin with this name exist as a directory, it should not exist
    $plugin_relative_directory_path = PLU_PATH . FILE_SEPARATOR . $e_plugin_name;
    //echo $plugin_relative_directory_path;
    if( is_dir( $plugin_relative_directory_path ) ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "A plugin already exist with the given plugin_name. Please change the plugin_name and try again" );
        return;
    }
    
    // Create a Directory with the given plugin name under plugins
    global $pluginDirectories;
    if( !mkdir( $plugin_relative_directory_path ) ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Failed to create the plugin directory. Please check if the `plugins` directory is writable" );
        return;
    }
    // Create sub-directories
    foreach ( $pluginDirectories as $directory ) {
        mkdir( $plugin_relative_directory_path . FILE_SEPARATOR . $directory );
    }
    
    
    // Insert the plugin information into plugins table
    $sql = "INSERT INTO plugins( `plugin_name`, `plugin_alias`, `version` ) VALUES( '$e_plugin_name', '$e_plugin_alias', '$e_plugin_version' )";
    $rows = insertQuery( $sql );
    if( $rows > 0 ){
        $plugin_id = getAIID();
        $plugin = array(
            "plugin_id" => $plugin_id,
            "plugin_name" => $e_plugin_name,
            "plugin_alias" => $e_plugin_alias,
            "version" => $e_plugin_version
        );
        $data = array(
            "info" => "Plugin has been created",
            "data" => $plugin
        );
        echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $data );
        return;
    }
    echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Failed to create the Plugin" );
}

function scodezy_delete_plugin(){
    checkAuthorizationForFunction(__FUNCTION__ );
    
    $plugin_id = request( 'plugin_id' );
    
    // Do validation for plugin ID
    validateEmptyDigitString( $plugin_id, __FUNCTION__, "Plugin ID is required !" );
    validate( $plugin_id, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), "Plugin ID is invalid !" );
    
    $e_plugin_id = escape_string( $plugin_id );
    
    $sql = "SELECT * FROM plugins WHERE plugin_id='$e_plugin_id'";
    $result_set = selectQuery( $sql );
    if( ($result_set == NULL)
            || ( mysqli_num_rows($result_set) == 0 )){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Plugin ID is invalid !" );
        return;
    }
    $plugin = mysqli_fetch_assoc( $result_set );
    
    if( $plugin[ 'is_system_plugin' ] === "1" ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "This is a system plugin and cannot be deleted" );
        return;
    }
    
    // Delete the plugin directory if exists
    $plugin_relative_directory_path = PLU_PATH . FILE_SEPARATOR . $plugin[ 'plugin_name' ];
    //echo $plugin_relative_directory_path;
    if( file_exists( $plugin_relative_directory_path ) && is_dir( $plugin_relative_directory_path ) ){
        if( !deleteDirectoryRecursive( $plugin_relative_directory_path ) ){
            echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Some of the directories does not have writable permission. Please ensure the directories inside the selected plugin are writable before deleting them" );
            return;
        }
    }
    
    $sql = "DELETE FROM plugins WHERE plugin_id='$e_plugin_id'";
    //echo $sql;
    //return;
    $rows = deleteQuery( $sql );
    if( $rows > 0 ){
        $data = array(
            "info" => "Plugin has been deleted",
            "data" => $plugin
        );
        echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $data );
        return;
    }
    echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Failed to delete the plugin" );
}

function scodezy_update_plugin(){
    checkAuthorizationForFunction(__FUNCTION__ );
    
    //print_r( $_REQUEST );
    $plugin_id          = request( 'plugin_id' );
    $plugin_name        = request( 'plugin_name' );
    $plugin_alias       = request( 'plugin_alias' );
    $plugin_version     = request( 'plugin_version' );
    
    validateEmptyDigitString( $plugin_id, __FUNCTION__, "Plugin ID is required" );
    validateEmptyString( $plugin_name, __FUNCTION__, "Plugin name is required" );
    validateEmptyString( $plugin_alias, __FUNCTION__, "Plugin Alias is required" );
    validateEmptyString( $plugin_version, __FUNCTION__, "Plugin Version is required" );
    
    validate( $plugin_id, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), "Selected plugin is invalid" );
    validate( $plugin_name, __FUNCTION__, getValidationRegex( "VLDTN_PLUGIN_NAME" ), getValidationErrMsg( "VLDTN_PLUGIN_NAME" ) . " for plugin name" );
    validate( $plugin_alias, __FUNCTION__, getValidationRegex( "VLDTN_PLUGIN_ALIAS" ), getValidationErrMsg( "VLDTN_PLUGIN_ALIAS" ) . " for plugin alias" );
    validate( $plugin_version, __FUNCTION__, getValidationRegex( "VLDTN_PLUGIN_VERSION" ), getValidationErrMsg( "VLDTN_PLUGIN_VERSION" ) . " for plugin version" );
    
    // Escape strings for storing in the DB
    $e_plugin_id              = escape_string( $plugin_id );
    $e_plugin_name            = escape_string( $plugin_name );
    $e_plugin_alias           = escape_string( $plugin_alias );
    $e_plugin_version         = escape_string( $plugin_version );
    
    // plugin_name has to be unique throughout the system
    // also, the given plugin_name can only belong to the given plugin_id for moving forward with the code
    $sql = "SELECT plugin_name FROM plugins WHERE plugin_name='$e_plugin_name' AND plugin_id<>'$e_plugin_id'";
    $result_set = selectQuery( $sql );
    if( mysqli_num_rows( $result_set ) > 0 ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "A plugin already exist with the given Plugin Name. Please try using another name" );
        return;
    }
    
    // Retrieve existing plugin details
    $sql = "SELECT plugin_name FROM plugins WHERE plugin_id='$e_plugin_id'";
    $result_set = selectQuery( $sql );
    if( mysqli_num_rows( $result_set ) == 0 ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "No such plugin" );
        return;
    }    
    $val = mysqli_fetch_object( $result_set );
    $existing_plugin_name = $val->plugin_name;
    
    // If the updated plugin name is different, then rename the plugin directory
    if( $existing_plugin_name !== $e_plugin_name ){
        // Rename the plugin folder
        // The plugin with the existing name should exist
        $existing_plugin_relative_directory_path = PLU_PATH . FILE_SEPARATOR . $existing_plugin_name;
        //echo $existing_plugin_relative_directory_path;
        if( !file_exists( $existing_plugin_relative_directory_path ) || !is_dir( $existing_plugin_relative_directory_path ) ){
            echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Plugin does not exist with the existing plugin_name. Please report to the administrator" );
            return;
        }
    
        // Rename the existing plugin folder to the new name
        $plugin_relative_directory_path = PLU_PATH . FILE_SEPARATOR . $e_plugin_name;
        // New plugin name directory should not exist
        if( file_exists( $plugin_relative_directory_path ) ){
            echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "There is a directory that already exist with the updated plugin name. Either manually delete this directory under /plugins or report it to the administrator" );
            return;
        }
        
        rename( $existing_plugin_relative_directory_path, $plugin_relative_directory_path );
    }
    
    // UPDATE the plugin information into plugins table
    $sql = "UPDATE plugins SET `plugin_name`='$e_plugin_name', "
            . "`plugin_alias`='$e_plugin_alias', "
            . "`version`='$e_plugin_version' "
            . " WHERE plugin_id='$e_plugin_id'";
    $rows = updateQuery( $sql );
    if( $rows > 0 ){
        $plugin = array(
            "plugin_id" => $plugin_id,
            "plugin_name" => $e_plugin_name,
            "plugin_alias" => $e_plugin_alias,
            "version" => $e_plugin_version
        );
        $data = array(
            "info" => "Plugin has been updated",
            "data" => $plugin
        );
        echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $data );
        return;
    }
    echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Failed to update the plugin" );
}

function scodezy_export_plugin(){
    checkAuthorizationForFunction(__FUNCTION__ );
    
    checkAuthorizationForFunction( __FUNCTION__ );
    
    $plugin_id          = request( 'plugin_id' );
    
    validateEmptyDigitString( $plugin_id, __FUNCTION__, "Plugin ID is required" );
    
    validate( $plugin_id, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), "Selected plugin is invalid" );
    
    // Escape strings for storing in the DB
    $e_plugin_id              = escape_string( $plugin_id );
    
    // Retrieve existing plugin details
    $sql = "SELECT * FROM plugins WHERE plugin_id='$e_plugin_id'";
    $result_set = selectQuery( $sql );
    if( mysqli_num_rows( $result_set ) == 0 ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "No such plugin" );
        return;
    }    
    $val = mysqli_fetch_assoc( $result_set );
    $plugin = $val;
    $plugin_name = $val[ 'plugin_name' ];
    
    // Plugin directory should physically exist on the disk
    $pluginDirPath = PLU_PATH . FILE_SEPARATOR . $plugin_name;
    if( !file_exists( $pluginDirPath ) ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Plugin `$plugin_name` does not exist in the disk. Please contact the administrator" );        
        return;
    }
    
    $allTablesData = array();
    $allTablesData = $plugin;
    unset( $allTablesData[ 'plugin_id' ] );
    $allTablesDataJSON = json_encode( $allTablesData, JSON_UNESCAPED_UNICODE );
    
    // Create and store this .json DB file inside the corresponding plugin directory
    $tableDataFileName = "db.json";
    $tableDataFilePath = $pluginDirPath . FILE_SEPARATOR . $tableDataFileName;
    $fileTableData = fopen( $tableDataFilePath, 'w+' );
    if( $fileTableData === false ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Failed to create the db.json file. Please contact the administrator" );        
        return;
    }
    if( fwrite( $fileTableData, $allTablesDataJSON ) === false ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Failed to write data to the db.json file. Please contact the administrator" );        
        return;
    }
    
    /**
     * 1. Ensure that the path where the plugin zip file will be created and stored exist and is writable
     * 2. The name of the plugin zip file generated, must not be taken by any other file in that directory yet
     * 3. Generate the ZIP file
     * 4. Generate a private link for the zip file
     * 5. Create a content to store this link in the DB for future retrieval of the download link as some plugins are huge and takes time to export
     * 
     */
    
    // 1. Ensure that the path where the plugin zip file will be created and stored exist and is writable
    //echo DAT_PATH;
    //var_dump( is_writable( DAT_PATH ) );
    /*
    // commented because is_writable is always returning FALSE on windows
    if( !is_writable( DAT_PATH ) ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "The system's data directory does not have write permission. Please contact the administrator" );
        return;
    }
     * 
     */
    // Path where the plugin is to be created should exist, if not exist, then create it
    $pluginExportDirPath = DAT_PATH . FILE_SEPARATOR . DIRNAME_EXPORTS;
    if( !file_exists( $pluginExportDirPath ) ){
        if( !mkdir( $pluginExportDirPath ) ){
            echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Failed to create the directory `exports`. Please contact the administrator" );
            @unlink( $tableDataFilePath );
            return;
        }
    }
    $currentTimestamp  = currentTimeMilliseconds();
    $pluginZipFileName = $plugin_name . "__" . $currentTimestamp . ".zip";
    $pluginZipFilePath = $pluginExportDirPath . FILE_SEPARATOR . $pluginZipFileName;
    createZipFromDirectory( $pluginDirPath, $pluginZipFilePath );
    
    // Verify if the zip file has been successfully created, by checking its existence and size greater than 0kb
    if( !file_exists( $pluginZipFilePath ) ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Plugin zip file has not been created. Please contact the administrator" );
        @unlink( $tableDataFilePath );
        return;
    }
    
    // Delete the db.json file
    @unlink( $tableDataFilePath );
    
    //echo filesize( $pluginZipFilePath );
    /*
    // Commented because filesize() returning false always
    if( ($val = filesize( $pluginZipFilePath ) <= 22) || ($val === false) ){   // 22 bytes is the size of empty zip file
        var_dump( $val );
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Plugin zip file has errors. Please contact the administrator" );
        //@unlink( $pluginZipFilePath );
        return;
    }*/
    
    // 4. Generate a private link for the zip file
    $pluginFileDownloadLink = getSiteRootPath( DIRNAME_DATA ) . FILE_SEPARATOR . DIRNAME_EXPORTS . FILE_SEPARATOR . $pluginZipFileName;
    $privateLink = generatePrivateLink( $pluginFileDownloadLink, $pluginZipFileName, "file", false, 'application/zip' );
    
    // 5. Store this link as a content inside contents table
    createContent( $privateLink, "Plugin " . $plugin[ 'plugin_alias' ] . " Download Link", '', '', 'plugin_download_private_link', 'application/zip', 'published' );
    
    $data = array(
        "info" => "Plugin has been exported. If your download does not begin automatically, please check the Contents section for the download link of the exported plugin. <br />Download: <a href=\"$privateLink\" target='_blank'>Here</a>",
        "data" => $privateLink
    );
    
    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $data );
}

// Not backing up the Database anymore
function scodezy_export_plugin_discarded(){
    checkAuthorizationForFunction(__FUNCTION__ );
    
    $plugin_id          = request( 'plugin_id' );
    
    validateEmptyDigitString( $plugin_id, __FUNCTION__, "Plugin ID is required" );
    
    validate( $plugin_id, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), "Selected plugin is invalid" );
    
    // Escape strings for storing in the DB
    $e_plugin_id              = escape_string( $plugin_id );
    
    // Retrieve existing plugin details
    $sql = "SELECT * FROM plugins WHERE plugin_id='$e_plugin_id'";
    $result_set = selectQuery( $sql );
    if( mysqli_num_rows( $result_set ) == 0 ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "No such plugin" );
        return;
    }    
    $val = mysqli_fetch_assoc( $result_set );
    $plugin = $val;
    $plugin_name = $val[ 'plugin_name' ];
    
    // Plugin directory should physically exist on the disk
    $pluginDirPath = PLU_PATH . FILE_SEPARATOR . $plugin_name;
    if( !file_exists( $pluginDirPath ) ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Plugin `$plugin_name` does not exist in the disk. Please contact the administrator" );        
        return;
    }
    
    // Create backup of SQL data into a .sql file
    // Backup from plugins table
    $pluginsTableData = array();//$val;
    $pluginsTableData[ 'table_name' ] = 'plugins';
    $pluginsTableData[ 'table_data' ] = $val;
    
    // Backup from functionalities table
    $functionalitiesTableData = array();
    $functionalitiesTableData[ 'table_name' ] = "functionalities";
    $functionalitiesTableData[ 'table_data' ] = array();
    $sql = "SELECT * FROM functionalities WHERE plugin_id='$e_plugin_id'";
    $result_set = selectQuery( $sql );
    while( ($val = mysqli_fetch_assoc( $result_set ) ) !== NULL ){
        $functionalitiesTableData[ 'table_data' ][] = $val;
    }
    
    
    // Backup from pages table
    $pagesTableData = array();
    $pagesTableData[ 'table_name' ] = "pages";
    $pagesTableData[ 'table_data' ] = array();
    $sql = "SELECT * FROM pages WHERE plugin_id='$e_plugin_id'";
    $result_set = selectQuery( $sql );
    $f = fopen( "logs.txt", "a+" );
    while( ($val = mysqli_fetch_assoc( $result_set ) ) !== NULL ){
        $ogVal = $val;
        if( $val[ 'parent_id' ] !== "0" ){
            $arr = array();
            do{
                $parent_id = $val[ 'parent_id' ];
                fwrite( $f, "parent id: " . $parent_id ."\n" );
                $sql1 = "SELECT * FROM pages WHERE page_id='$parent_id'";
                $result_set1 = selectQuery( $sql1 );
                if( mysqli_num_rows( $result_set1 ) > 0 ){
                    $val = mysqli_fetch_assoc( $result_set1 );
                    if( $val[ 'functionality_id' ] !== "0" ){
                        $functionality_id = $val[ 'functionality_id' ];
                        $sql1 = "SELECT * FROM functionalities WHERE functionality_id='$functionality_id'";
                        $result_set1 = selectQuery( $sql1 );
                        if( mysqli_num_rows( $result_set1 ) > 0 ){
                            $val1 = mysqli_fetch_assoc( $result_set1 );
                            $functionality = $val1;
                            $val[ 'functionality' ] = $functionality;
                        }
                    }
                    if( !isset( $arr[ 'parent_page' ] ) ){
                        $arr = $val;
                        $arr[ 'parent_page' ] = '';
                    }
                    else{
                        $arr[ 'parent_page' ] = $val;
                    }
                    
                    //$parent_page = $val;
                    //$val[ 'parent_page' ] = $parent_page;
                }
            }while( $parent_id !== "0" );
            
            $ogVal[ 'parent_page' ] = $arr;
            $val = $ogVal;
            
        }
        if( $val[ 'functionality_id' ] !== "0" ){
            $functionality_id = $val[ 'functionality_id' ];
            $sql1 = "SELECT * FROM functionalities WHERE functionality_id='$functionality_id'";
            $result_set1 = selectQuery( $sql1 );
            if( mysqli_num_rows( $result_set1 ) > 0 ){
                $val1 = mysqli_fetch_assoc( $result_set1 );
                $functionality = $val1;
                $val[ 'functionality' ] = $functionality;
            }
        }
        /*
        if( $val[ 'plugin_id' ] !== "0" ){
            $plugin_id = $val[ 'plugin_id' ];
            $sql1 = "SELECT * FROM plugins WHERE plugin_id='$plugin_id'";
            $result_set1 = selectQuery( $sql1 );
            if( mysqli_num_rows( $result_set1 ) > 0 ){
                $val1 = mysqli_fetch_assoc( $result_set1 );
                $plugin = $val1;
                $val[ 'plugin' ] = $plugin;
            }
        }
         * 
         */
        $pagesTableData[ 'table_data' ][] = $val;
    }
    
    
    // Combine all data into one array
    $allTablesData = array();
    $allTablesData[ 'plugin_name' ] = $plugin_name;
    $allTablesData[ 'plugin_data' ] = array();
    array_push( $allTablesData[ 'plugin_data' ], $pluginsTableData );
    array_push( $allTablesData[ 'plugin_data' ], $functionalitiesTableData );
    array_push( $allTablesData[ 'plugin_data' ], $pagesTableData );    
    $allTablesDataJSON = json_encode( $allTablesData, JSON_UNESCAPED_UNICODE );
    
    // Create and store this .json DB file inside the corresponding plugin directory
    $tableDataFileName = "db.json";
    $tableDataFilePath = $pluginDirPath . FILE_SEPARATOR . $tableDataFileName;
    $fileTableData = fopen( $tableDataFilePath, 'w+' );
    if( $fileTableData === false ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Failed to create the db.json file. Please contact the administrator" );        
        return;
    }
    if( fwrite( $fileTableData, $allTablesDataJSON ) === false ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Failed to write data to the db.json file. Please contact the administrator" );        
        return;
    }
    
    /**
     * 1. Ensure that the path where the plugin zip file will be created and stored exist and is writable
     * 2. The name of the plugin zip file generated, must not be taken by any other file in that directory yet
     * 3. Generate the ZIP file
     * 4. Generate a private link for the zip file
     * 5. Create a content to store this link in the DB for future retrieval of the download link as some plugins are huge and takes time to export
     * 
     */
    
    // 1. Ensure that the path where the plugin zip file will be created and stored exist and is writable
    //echo DAT_PATH;
    //var_dump( is_writable( DAT_PATH ) );
    /*
    // commented because is_writable is always returning FALSE on windows
    if( !is_writable( DAT_PATH ) ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "The system's data directory does not have write permission. Please contact the administrator" );
        return;
    }
     * 
     */
    // Path where the plugin is to be created should exist, if not exist, then create it
    $pluginExportDirPath = DAT_PATH . FILE_SEPARATOR . "plugin_exports";
    if( !file_exists( $pluginExportDirPath ) ){
        if( !mkdir( $pluginExportDirPath ) ){
            echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Failed to create the directory plugin_exports. Please contact the administrator" );
            return;
        }
    }
    $currentTimestamp  = currentTimeMilliseconds();
    $pluginZipFileName = $plugin_name . "__" . $currentTimestamp . ".zip";
    $pluginZipFilePath = $pluginExportDirPath . FILE_SEPARATOR . $pluginZipFileName;
    createZipFromDirectory( $pluginDirPath, $pluginZipFilePath );
    
    // Verify if the zip file has been successfully created, be checking its existence and size greater than 0kb
    if( !file_exists( $pluginZipFilePath ) ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Plugin zip file has not been created. Please contact the administrator" );
        return;
    }
    
    // Delete the db.json file
    @unlink( $tableDataFilePath );
    
    //echo filesize( $pluginZipFilePath );
    /*
    // Commented because filesize() returning false always
    if( ($val = filesize( $pluginZipFilePath ) <= 22) || ($val === false) ){   // 22 bytes is the size of empty zip file
        var_dump( $val );
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Plugin zip file has errors. Please contact the administrator" );
        //@unlink( $pluginZipFilePath );
        return;
    }*/
    
    // 4. Generate a private link for the zip file
    $pluginFileDownloadLink = getSiteRootPath( DIRNAME_DATA ) . FILE_SEPARATOR . "plugin_exports" . FILE_SEPARATOR . $pluginZipFileName;
    $privateLink = generatePrivateLink( $pluginFileDownloadLink, $pluginZipFileName, "file", false );
    
    // 5. Store this link as a content inside contents table
    createContent( $privateLink, "Plugin " . $plugin[ 'plugin_alias' ] . " Download Link", '', '', 'plugin_download_private_link', '', 'published' );
    
    $data = array(
        "info" => "Plugin has been exported. If your download does not begin automatically, please check the Contents section for the download link of the exported plugin. <br />Download: <a href=\"$privateLink\" target='_blank'>Here</a>",
        "data" => $privateLink
    );
    
    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $data );
}

function scodezy_import_plugin_discarded(){
    checkAuthorizationForFunction(__FUNCTION__ );
    
    //print_r( $_REQUEST );
    print_r( $_FILES );
    $plugin_file        = @$_FILES[ 'plugin_file' ];
    
    if( ($plugin_file === NULL) || ($plugin_file === "") ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Please select a plugin file and try again" );
        return;
    }
    
    // $name       = preg_replace( '/[^\x20-\x7E]/', '', $plugin_file[ 'name' ] );     // Remove all non-ASCII characters from the file name
    $name       = get_valid_scodezy_filename( $plugin_file[ 'name' ] );     // Remove all non-ASCII characters from the file name
    $type       = $plugin_file[ 'type' ]; 
    $tmp_name   = $plugin_file[ 'tmp_name' ];
    $error      = $plugin_file[ 'error' ];
    
    if( $type !== "application/x-zip-compressed" ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Please select a valid scodezy plugin file and try again" );
        return;
    }
    
    if( $error > 0 ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Selected file has errors" );
        return;
    }
    
    // Make sure the temporary directory for plugin upload is available and writable
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
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Failed to upload the plugin file" );
        return;
    }
    
    // Unzip the plugin file into tmp directory
    $zip = new ZipArchive;
    $res = $zip->open( $tmpFilePath );
    if ( $res === FALSE ) {
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Failed to open the plugin file" );
        @unlink( $tmpFilePath );
        return;
    }
    
    $tmpDbJsonDirName  = $currentTimestamp . "__db.json";
    $tmpDbJsonDirPath  = $tmp_dir_path . FILE_SEPARATOR . $tmpDbJsonDirName;
    $res = $zip->extractTo( $tmpDbJsonDirPath, "db.json" );
    if ( $res === FALSE ) {
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "db.json file is missing in the archive" );
        @unlink( $tmpFilePath );
        return;
    }
    
    // Open and read the db.json file and check if the contents of the file are in proper format
    $dbJsonFileHandle = fopen( $tmpDbJsonDirPath, 'r' );
    if( $dbJsonFileHandle === false ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Failed to open the db.json file" );
        @unlink( $tmpFilePath );
        @unlink( $tmpDbJsonDirPath );        
        return;
    }
    
    $dbJsonData = "";
    while( ($s = fgets( $dbJsonFileHandle )) !== NULL ){
        $dbJsonData .= $s;
    }
    $dbJsonData = trim( $dbJsonData );
    
    // Delete the db.json
    @unlink( $tmpDbJsonDirPath );
    
    if( $dbJsonData === "" ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "db.json file is empty. The selected plugin file is invalid" );
        @unlink( $tmpFilePath );
        return;
    }
    
    $dbJsonArray = json_decode( $dbJsonData, TRUE, JSON_UNESCAPED_UNICODE );
    if( $dbJsonArray === NULL ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "db.json file is in incorrect format. The selected plugin file is invalid" );
        @unlink( $tmpFilePath );
        return;
    }
    
    if( !isset( $dbJsonArray[ 'plugin_name' ] ) ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Plugin name is missing in the db.json file" );
        @unlink( $tmpFilePath );
        return;
    }
    $plugin_name   = $dbJsonArray[ 'plugin_name' ];
    $e_plugin_name = escape_string( $plugin_name );
    
    if( !isset( $dbJsonArray[ 'plugin_data' ] ) ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Plugin data is missing in the db.json file" );
        return;
    }
    $plugin_data = $dbJsonArray[ 'plugin_data' ];
    
    // Check if the plugin_name already exists in the plugins table
    $sql = "Select * FROM plugins WHERE plugin_name='$plugin_name'";
    $result_set = selectQuery( $sql );
    
    $plugin_alias       = (isset($plugin_data[ 'plugin_alias' ]))?$plugin_data[ 'plugin_alias' ]:"";
    $version            = (isset($plugin_data[ 'version' ]))?$plugin_data[ 'version' ]:"1.0.0";
    $is_system_plugin   = (isset($plugin_data[ 'is_system_plugin' ]))?$plugin_data[ 'is_system_plugin' ]:"0";

    $e_plugin_alias     = escape_string( $plugin_alias );
    $e_version          = escape_string( $version );
    $e_is_system_plugin = escape_string( $is_system_plugin );
    if( mysqli_num_rows( $result_set ) > 0 ){
        /*
        // We cannot seek confirmation from the user because we are generating temporary file names, for another call, we will not be able to read these temporary files again
    
        $val = mysqli_fetch_object( $result_set );        
        echo createJSONMessage( GENERAL_CONFIRM_MESSAGE, __FUNCTION__, "A plugin with the name `$plugin_name` and version `{$val->version}` already exist. All the plugin data will be over written by the data of the pplugin being imported and this action is non-reversible. Please confirm if you want to proceed ?" );
        return;
         */
        $val = mysqli_fetch_object( $result_set );
        $plugin_id = $val->plugin_id;
        // Run update query on plugins table
        $sql = "UPDATE plugins SET plugin_alias='$e_plugin_alias', version='$e_version', is_system_plugin='$e_is_system_plugin' WHERE plugin_name='$e_plugin_name'";
        updateQuery( $sql );
    }
    else{
        $sql = "INSERT INTO plugins( `plugin_name`, `plugin_alias`, `version`, `is_system_plugin` ) "
                . "VALUES( '$e_plugin_name', '$e_plugin_alias', '$e_version', '$e_is_system_plugin' )";
        insertQuery( $sql );
        
        $plugin_id = getAIID();
    }
    
    
    // Read other table data from the db.json file
    // Table -> functionalities
    if( isset( $plugin_data[ 'functionalities' ] ) ){
        $table_name = (isset($plugin_data[ 'table_name' ]))?$plugin_data[ 'table_name' ]:"functionalities";
        if( isset( $plugin_data[ 'table_data' ] ) ){
            $table_data = $plugin_data[ 'table_data' ];
            if( count( $table_data ) > 0 ){
                foreach ( $table_data as $value ) {
                    $e_functionality_name = escape_string( $value[ 'functionality_name' ] );
                    // Check if the functionality_name is already present in the DB, if yes, then simply update its columns
                    $sql = "SELECT functionality_name FROM functionalities WHERE functionality_name='$e_functionality_name'";
                    $result_set = selectQuery( $sql );
                    $e_alias                     = (isset($value[ 'alias' ]))?$value[ 'alias' ]:"";
                    $e_functionality_description = (isset($value[ 'functionality_description' ]))?$value[ 'functionality_description' ]:"";
                    $e_functionality_type        = (isset($value[ 'functionality_type' ]))?$value[ 'functionality_type' ]:"";
                    $e_is_page                   = (isset($value[ 'is_page' ]))?$value[ 'is_page' ]:"0";
                    $e_is_a_content              = (isset($value[ 'is_a_content' ]))?$value[ 'is_a_content' ]:"1";

                    if( mysqli_num_rows( $result_set ) > 0 ){
                        $sql = "UPDATE functionalities SET "
                            . "alias='$e_alias', functionality_description='$e_functionality_description', functionality_type='$e_functionality_type', "
                            . "is_page='$e_is_page', is_a_content='$e_is_a_content', plugin_id='$plugin_id' "
                                . "WHERE functionality_name='$e_functionality_name'";
                        updateQuery( $sql );
                    }
                    else{
                        $sql = "INSERT INTO functionalities( `functionality_name`, `alias`, `functionality_description`, `functionality_type`, `is_page`, `is_a_content`, `plugin_id`) "
                                . "VALUES( '$e_functionality_name', '$e_alias', '$e_functionality_description', '$e_functionality_type', '$e_is_page', '$e_is_a_content', '$plugin_id' )";
                        insertQuery( $sql );
                    }
                }                
            }
        }        
    }
    
    // Table -> pages
    if( isset( $plugin_data[ 'pages' ] ) ){
        $table_name = (isset($plugin_data[ 'table_name' ]))?$plugin_data[ 'table_name' ]:"pages";
        if( isset( $plugin_data[ 'table_data' ] ) ){
            $table_data = $plugin_data[ 'table_data' ];
            if( count( $table_data ) > 0 ){
                foreach ( $table_data as $value ) {
                    $e_page_name = escape_string( $value[ 'page_name' ] );
                    // Check if the page_name is already present in the DB, if yes, then simply update its columns
                    $sql = "SELECT page_name FROM pages WHERE page_name='$e_page_name'";
                    $result_set = selectQuery( $sql );
                    $e_hierarchy        = (isset($value[ 'hierarchy' ]))?$value[ 'hierarchy' ]:"0";
                    $e_page_sequence    = (isset($value[ 'page_sequence' ]))?$value[ 'page_sequence' ]:"1";
                    $e_icon             = (isset($value[ 'icon' ]))?$value[ 'icon' ]:"ki-outline ki-share fs-2";
                    $e_visible          = (isset($value[ 'visible' ]))?$value[ 'visible' ]:"0";
                    $e_page_title       = (isset($value[ 'page_title' ]))?$value[ 'page_title' ]:"";
                    $e_title            = (isset($value[ 'title' ]))?$value[ 'title' ]:"";
                    $e_description      = (isset($value[ 'description' ]))?$value[ 'description' ]:"";
                    $e_tags             = (isset($value[ 'tags' ]))?$value[ 'tags' ]:"";
                    $e_image            = (isset($value[ 'image' ]))?$value[ 'image' ]:"";
                    $e_content          = (isset($value[ 'content' ]))?$value[ 'content' ]:"";
                    $e_parent_id        = (isset($value[ 'parent_id' ]))?$value[ 'parent_id' ]:"0";
                    
                    // parent_id
                    if( ($e_parent_id !== "-1") && ($e_parent_id !== "0") ){
                        if( isset($value[ 'parent_page' ]) ){
                            $pi = $value[ 'parent_id' ];
                            $ogValue = $value;
                            $arr = array();
                            while( $pi !== "0" ){
                                $parent_page = $value[ 'parent_page' ];
                                if( isset( $parent_page[ 'parent_page' ] ) ){
                                    //$last_parent = $parent_page[ 'parent_page' ];
                                    $value = $parent_page[ 'parent_page' ];
                                    
                                    $parent_id = (isset($value[ 'parent_id' ]))?$value[ 'parent_id' ]:"0";
                                    if( $parent_id === "0" ){
                                        $e_hierarchy        = (isset($value[ 'hierarchy' ]))?$value[ 'hierarchy' ]:"0";
                                        $e_page_sequence    = (isset($value[ 'page_sequence' ]))?$value[ 'page_sequence' ]:"1";
                                        $e_icon             = (isset($value[ 'icon' ]))?$value[ 'icon' ]:"ki-outline ki-share fs-2";
                                        $e_visible          = (isset($value[ 'visible' ]))?$value[ 'visible' ]:"0";
                                        $e_page_title       = (isset($value[ 'page_title' ]))?$value[ 'page_title' ]:"";
                                        $e_title            = (isset($value[ 'title' ]))?$value[ 'title' ]:"";
                                        $e_description      = (isset($value[ 'description' ]))?$value[ 'description' ]:"";
                                        $e_tags             = (isset($value[ 'tags' ]))?$value[ 'tags' ]:"";
                                        $e_image            = (isset($value[ 'image' ]))?$value[ 'image' ]:"";
                                        $e_content          = (isset($value[ 'content' ]))?$value[ 'content' ]:"";
                                        $e_parent_id        = (isset($value[ 'parent_id' ]))?$value[ 'parent_id' ]:"0";
                                        $functionality_id   = (isset($value[ 'functionality_id' ]))?$value[ 'functionality_id' ]:"0";
                                        
                                        // functionality_id
                                        if( $functionality_id !== "0" ){
                                            // Check if this functionality_name exist
                                            $functionality      = $value[ 'functionality' ];
                                            $functionality_name = $functionality[ 'functionality_name' ];
                                            $f_alias = $functionality[ 'alias' ];
                                            $f_description = $functionality[ 'functionality_description' ];
                                            $f_type = $functionality[ 'functionality_type' ];
                                            $f_is_page = $functionality[ 'is_page' ];
                                            $f_is_a_content = $functionality[ 'is_a_content' ];
                                            $f_plugin_id = $plugin_id;
                                            
                                            $sql = "SELECT functionality_name FROM functionalities WHERE functionality_name='$functionality_name'";
                                            $rs = selectQuery( $sql );
                                            if( mysqli_num_rows( $rs ) == 0 ){
                                                $sql = "INSERT INTO functionalities( `functionality_name`, `alias`, `functionality_description`, `is_page`, `is_a_content`, `plugin_id`, `functionality_type` ) "
                                                        . "VALUES( '$functionality_name', '$f_alias', '$f_description', '$f_is_page', '$f_is_a_content', '$f_plugin_id', '$f_type' )";
                                                $rows = insertQuery( $sql );
                                                $functionality_id = getAIID();
                                            }
                                            else{
                                                $v = mysqli_fetch_object( $rs );
                                                $functionality_id = $v->functionality_id;
                                            }
                                        }
                                        
                                        // Insert this page into the pages table and retrieve its page_id
                                        $sql = "INSERT INTO pages( `page_name`, `parent_id`, `hierarchy`, `page_sequence`, `icon`, `visible`, `page_title`, `title`, `functionality_id`, `plugin_id` ) "
                                                . "VALUES( '$e_page_name', '$parent_id', '$e_hierarchy', '$e_page_sequence', '$e_icon', '$e_visible', '$e_page_title', '$e_title', '$functionality_id', '$plugin_id' )";
                                        $rows = insertQuery( $sql );
                                        $page_id = getAIID();
                                    }
                                }
                                
                                // Check if there is a page existing with the page_name
                                $page_name = $parent_page[ 'page_name' ]; 
                                $sql = "SELECT * FROM pages WHERE page_name='$page_name'";
                                $result_set1 = selectQuery( $sql );
                                if( mysqli_num_rows( $result_set1 ) == 0 ){
                                    
                                }
                            }
                            /*
                            $sql1 = "SELECT page_name FROM pages WHERE page_id='$e_parent_id'";
                            $result_set1 = selectQuery( $sql1 );
                            if( mysqli_num_rows( $result_set1 ) == 0 ){
                                
                                // Insert this page into the pages table and retrieve its page_id
                                $sql = "INSERT INTO pages( `page_name`, `parent_id`, `hierarchy`, `page_sequence`, `icon`, `visible`, `page_title`, `title`, `functionality_id`, `plugin_id` ) "
                                        . "VALUES( '$e_page_name', '$parent_page_id', '$hierarchy', '1', '$page_icon', '$is_visible', '$page_title', '$page_title', '$functionality_id', '$plugin_id' )";
                                $rows = insertQuery( $sql );
                                
                            }
                            */
                        }                        
                    }
                    // functionality_id
                    // plugin_id
                    if( mysqli_num_rows( $result_set ) > 0 ){
                        $sql = "UPDATE functionalities SET "
                            . "alias='$e_alias', functionality_description='$e_functionality_description', functionality_type='$e_functionality_type', "
                            . "is_page='$e_is_page', is_a_content='$e_is_a_content', plugin_id='$plugin_id' "
                                . "WHERE functionality_name='$e_functionality_name'";
                        updateQuery( $sql );
                    }
                    else{
                        $sql = "INSERT INTO functionalities( `functionality_name`, `alias`, `functionality_description`, `functionality_type`, `is_page`, `is_a_content`, `plugin_id`) "
                                . "VALUES( '$e_functionality_name', '$e_alias', '$e_functionality_description', '$e_functionality_type', '$e_is_page', '$e_is_a_content', '$plugin_id' )";
                        insertQuery( $sql );
                    }
                }
            }
        }        
    }
    
    
    
    //$zip->close();
}

function scodezy_import_plugin(){
    checkAuthorizationForFunction(__FUNCTION__ );
    
    //print_r( $_REQUEST );
    //print_r( $_FILES );
    $plugin_file        = @$_FILES[ 'plugin_file' ];
    
    if( ($plugin_file === NULL) || ($plugin_file === "") ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Please select a plugin file and try again" );
        return;
    }
    
    // $name       = preg_replace( '/[^\x20-\x7E]/', '', $plugin_file[ 'name' ] );     // Remove all non-ASCII characters from the file name
    $name       = get_valid_scodezy_filename( $plugin_file[ 'name' ] );     // Remove all non-ASCII characters from the file name
    $type       = $plugin_file[ 'type' ]; 
    $tmp_name   = $plugin_file[ 'tmp_name' ];
    $error      = $plugin_file[ 'error' ];
    
    if( $type !== "application/x-zip-compressed" ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Please select a valid scodezy plugin file and try again" );
        return;
    }
    
    if( $error > 0 ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Selected file has errors" );
        return;
    }
    
    // Make sure the temporary directory for plugin upload is available and writable
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
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Failed to upload the plugin file" );
        return;
    }
    
    // Unzip the plugin file into tmp directory
    $zip = new ZipArchive;
    $res = $zip->open( $tmpFilePath );
    if ( $res === FALSE ) {
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Failed to open the plugin file" );
        @unlink( $tmpFilePath );
        return;
    }
    
    $tmpDbJsonDirName  = $currentTimestamp . "__db";
    $tmpDbJsonDirPath  = $tmp_dir_path . FILE_SEPARATOR . $tmpDbJsonDirName;
    $res = $zip->extractTo( $tmpDbJsonDirPath, "db.json" );
    //$zip->close();
    if ( $res === FALSE ) {
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "db.json file is missing in the archive" );
        @unlink( $tmpFilePath );
        return;
    }
    
    // Open and read the db.json file and check if the contents of the file are in proper format
    $tmpDbJsonFilePath = $tmpDbJsonDirPath . FILE_SEPARATOR . "db.json";
    $dbJsonFileHandle = fopen( $tmpDbJsonFilePath, 'r' );
    if( $dbJsonFileHandle === false ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Failed to open the db.json file" );
        @unlink( $tmpFilePath );
        deleteDirectoryRecursive( $tmpDbJsonDirPath );        
        return;
    }
    
    $dbJsonData = "";
    while( ($s = fgets( $dbJsonFileHandle )) !== false ){
        $dbJsonData .= $s;
    }
    $dbJsonData = trim( $dbJsonData );
    
    // Delete the db.json
    fclose( $dbJsonFileHandle );
    @unlink( $tmpDbJsonFilePath );
    deleteDirectoryRecursive( $tmpDbJsonDirPath );
    
    if( $dbJsonData === "" ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "db.json file is empty. The selected plugin file is invalid" );
        @unlink( $tmpFilePath );
        return;
    }
    
    $dbJsonArray = json_decode( $dbJsonData, TRUE, JSON_UNESCAPED_UNICODE );
    if( $dbJsonArray === NULL ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "db.json file is in incorrect format. The selected plugin file is invalid" );
        @unlink( $tmpFilePath );
        return;
    }
    
    if( !isset( $dbJsonArray[ 'plugin_name' ] ) ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Plugin name is missing in the db.json file" );
        @unlink( $tmpFilePath );
        return;
    }
    $plugin_name   = $dbJsonArray[ 'plugin_name' ];
    $e_plugin_name = escape_string( $plugin_name );
    
    $plugin_alias       = (isset($dbJsonArray[ 'plugin_alias' ]))?$dbJsonArray[ 'plugin_alias' ]:"";
    $version            = (isset($dbJsonArray[ 'version' ]))?$dbJsonArray[ 'version' ]:"1.0.0";
    $is_system_plugin   = (isset($dbJsonArray[ 'is_system_plugin' ]))?$dbJsonArray[ 'is_system_plugin' ]:"0";

    $e_plugin_alias     = escape_string( $plugin_alias );
    $e_version          = escape_string( $version );
    $e_is_system_plugin = escape_string( $is_system_plugin );
    
    // Check if the plugin_name already exists in the plugins table
    $sql = "Select * FROM plugins WHERE plugin_name='$plugin_name'";
    $result_set = selectQuery( $sql );
    if( mysqli_num_rows( $result_set ) > 0 ){
        /*
        // We cannot seek confirmation from the user because we are generating temporary file names, for another call, we will not be able to read these temporary files again
    
        $val = mysqli_fetch_object( $result_set );        
        echo createJSONMessage( GENERAL_CONFIRM_MESSAGE, __FUNCTION__, "A plugin with the name `$plugin_name` and version `{$val->version}` already exist. All the plugin data will be over written by the data of the pplugin being imported and this action is non-reversible. Please confirm if you want to proceed ?" );
        return;
         */
        $val = mysqli_fetch_object( $result_set );
        $plugin_id = $val->plugin_id;
        // Run update query on plugins table
        $sql = "UPDATE plugins SET plugin_alias='$e_plugin_alias', version='$e_version', is_system_plugin='$e_is_system_plugin' WHERE plugin_name='$e_plugin_name'";
        updateQuery( $sql );
    }
    else{
        $sql = "INSERT INTO plugins( `plugin_name`, `plugin_alias`, `version`, `is_system_plugin` ) "
                . "VALUES( '$e_plugin_name', '$e_plugin_alias', '$e_version', '$e_is_system_plugin' )";
        insertQuery( $sql );
        
        $plugin_id = getAIID();
    }
    
    // Extract the zip to the plugins directory
    $pluginDirPath = PLU_PATH . FILE_SEPARATOR . $plugin_name;
    if( !file_exists( $pluginDirPath ) ){
        if( !mkdir( $pluginDirPath ) ){
            echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Failed to create `$plugin_name` directory on the disk. Process failed" );     
            @unlink( $tmpFilePath );
            return;            
        }
    }
    
    $res = $zip->extractTo( $pluginDirPath );
    if ( $res === FALSE ) {
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Failed to extract the plugin files" );  
        @unlink( $tmpFilePath );
        return;
    }
    
    // Delete the db.json from extracted location
    $extractedDbJsonFilePath = $pluginDirPath . FILE_SEPARATOR . "db.json";
    if( file_exists( $extractedDbJsonFilePath ) ){
        @unlink( $extractedDbJsonFilePath );
    }
    
    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, "Plugin exported successfully" );
    
    $zip->close();
   
    
    
}

?>