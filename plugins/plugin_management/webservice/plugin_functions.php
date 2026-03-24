<?php

	function delete_plugin(){
		checkPrivilegeForFunction( __FUNCTION__ );
		
		$plugin_name = $_REQUEST[ 'plugin_name' ];
		$base_path = $_SERVER[ 'DOCUMENT_ROOT' ] . WEBSITE_SUB_DOMAIN_DIRECTORY;
		// echo $plugin_name;
		// echo $_SERVER[ 'DOCUMENT_ROOT' ];
		// echo "../." . PLUGIN_CONFIG_PATH . $plugin_name . ".php";
		// include( "../../plugins/plugin-config/$plugin_name.php" );
		include( $base_path . "plugins/plugin-config/" . $plugin_name . ".php" );
		
		// 1. Read the Plugin Config File
		if( ! file_exists( $base_path . "plugins/plugin-config/" . $plugin_name . ".php" ) ){
			echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Plugin config file has been misplaced ! Failed to delete the plugin" );
			return;
		}
		
		// 2. Iterate through the arrays and run delete queries
		// echo dirname( __DIR__ );
		//print_r( $config_privileges );
		for( $i = 0 ; $i < count( $config_privileges ) ; $i++ ){
			// Get the Privilge_group_name
			$privilege_group_name = $config_privileges[ $i ][ 'privilege_group_name' ];
			$sql = "DELETE FROM privilege_group WHERE privilege_group_name = '$privilege_group_name'";
			// Delete the Group
			$rows = deleteQuery( $sql );
			//echo "Delete : $privilege_group_name \n";
			
			$privileges = $config_privileges[ $i ][ 'privilege_group_privileges' ];
			
			for( $j = 0 ; $j < count( $privileges ) ; $j++ ){
				// Delete the privileges and functionalities for this group
				$privilege_name = $privileges[ $j ][ 'privilege_name' ];
				$sql = "DELETE FROM privileges WHERE privilege_name = '$privilege_name'";
				$rows = deleteQuery( $sql );
				//echo "Delete : $privilege_name \n";
				
				$functionality_name = $privileges[ $j ][ 'functionality_name' ];
				$sql = "DELETE FROM functionalities WHERE functionality_name = '$functionality_name'";
				$rows = deleteQuery( $sql );
				//echo "Delete : $functionality_name \n";
			}
		}
		
		// 3. Delete the Pages from the page array
		// print_r( $config_pages );
		for( $i = 0 ; $i < count( $config_pages ) ; $i++ ){
			$page_group_name = $config_pages[ $i ][ 'page_group_name' ];
			$sql = "DELETE FROM page_group WHERE page_group_name = '$page_group_name'";
			$rows = deleteQuery( $sql );
			//echo "DELETED : $page_group_name \n";
			
			$pages = $config_pages[ $i ][ 'page_group_pages' ];
			for( $j = 0 ; $j < count( $pages ) ; $j++ ){
				$page_name = $pages[ $j ][ 'page_name' ];
				$sql = "DELETE FROM pages WHERE page_name = '$page_name'";
				$rows = deleteQuery( $sql );
				//echo "DELETED : $page_name \n";
			}
		}
		
		// 4. Delete the Plugin Files
		$plugin_name = $options[ 'PLUGIN_NAME' ];
		if( file_exists( $base_path . "plugins/$plugin_name" ) ){
			// Delete the folder
			deleteDirectoryRecursive( $base_path . "plugins/$plugin_name/" );
		}
		
		// 5. Delete the webservice files
		if( file_exists( $base_path . "webservice/functions/$plugin_name" ) ){
			// Remove the webservice include code from sub-functions.php
			$files = scandir( $base_path . "webservice/functions/$plugin_name/" );
			array_shift( $files );array_shift( $files );
			
			removeFilesFromWebservice( $files, $base_path );
			
			// Delete the folder
			deleteDirectoryRecursive( $base_path . "webservice/functions/$plugin_name/" );
		}
		
		// 6. Delete the Plugin config file
		unlink( $base_path . "plugins/plugin-config/$plugin_name.php" );
		
		
		echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, "Plugin Deleted !" );
	}
	
	function removeFilesFromWebservice( $files, $base_path ){
		$file = fopen( $base_path . "webservice/functions/sub-functions.php", "r" );
		// iterate each line of code into an array
		$arr = array();
		$i = 0;
		while( ( $s = fgets( $file ) ) != NULL ){
			$arr[ $i++ ] = $s;
		}
		
		fclose( $file );
		// print_r( $files );
		// print_r( $arr );
		
		$new_arr = array();
		$c = 0;
		for( $j = 0; $j < count( $files ) ; $j++ ){
			$new_arr = array();
			for( $k = 0; $k < count( $arr ) ; $k++ ){
				if( strpos( $arr[ $k ], $files[ $j ] ) != FALSE ){
					//echo "Skip this line ".$arr[ $k ]."\n";
					continue;
				}
				$new_arr[ $c++ ] = $arr[ $k ];
			}
			$c = 0;
			$arr = array();
			$arr = array_merge( $arr, $new_arr );
		}
		
		// print_r( $arr );
		// print_r( $new_arr );
		
		$fi = fopen( $base_path . "webservice/functions/sub-functions.php", "w" );
		for( $i = 0 ; $i < count( $arr ) ; $i++ ){
			fprintf( $fi, $arr[ $i ] );
		}
		
		fclose( $fi );
		//for( $j = 0; $j < count( $arr ) ; $j++ ){
		//$arr = array_diff( $arr, $files );
		//}
		//if( in_array( $needle, $arr ) )
		
		//print_r( $arr );
	}

	function create_plugin(){
		checkPrivilegeForFunction( __FUNCTION__ );

		$plugin_name			= $_REQUEST[ 'plugin_name' ];
		$plugin_display_name	= $_REQUEST[ 'plugin_display_name' ];

		// Do the validation here
		validateEmptyString( $plugin_name, __FUNCTION__, 'Please Enter Plugin Name' );
		validate( $plugin_name, __FUNCTION__, "/^[a-z0-9\$]*$/", 'Invalid Plugin Name.Only small letters and Numbers allowed' );
		validateEmptyString( $plugin_display_name, __FUNCTION__, 'Please Enter Plugin Display Name' );
		validate( $plugin_display_name, __FUNCTION__, "/^[a-zA-Z0-9\$]*$/", 'Invalid Plugin Display Name.Only letters and Numbers allowed' );

		$sql = " SELECT * FROM plugins WHERE plugin_name='$plugin_name' ";
		$result_set = selectQuery( $sql );
		if( mysqli_num_rows( $result_set ) > 0 ){
			echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Plugin already exists." );
			return;
		}
		else{
			$sql="INSERT INTO plugins(`plugin_name`,`plugin_display_name`) values('$plugin_name', '$plugin_display_name') ";
			$rows = insertQuery( $sql );
			if( $rows > 0 ){
				echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, "Plugin " . $plugin_name . " successfully created" );
				return;
			}
			else{
				echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Something went wrong.please try again" );
				return;
			}
		}
	}
	
	function edit_plugin_details(){
		checkPrivilegeForFunction( __FUNCTION__ );

		$plugin_name			= $_REQUEST[ 'edit_plugin_name' ];
		$plugin_display_name	= $_REQUEST[ 'edit_plugin_display_name' ];

		// Do the validation here
		validateEmptyString( $plugin_name, __FUNCTION__, 'Please Enter Plugin Name' );
		validate( $plugin_name, __FUNCTION__, "/^[a-z0-9\$]*$/", 'Invalid Plugin Name.Only small letters and Numbers allowed' );
		validateEmptyString( $plugin_display_name, __FUNCTION__, 'Please Enter Plugin Display Name' );
		validate( $plugin_display_name, __FUNCTION__, "/^[a-zA-Z0-9\$]*$/", 'Invalid Plugin Display Name.Only letters and Numbers allowed' );

		$sql = " SELECT plugin_name FROM plugins WHERE plugin_name='$plugin_name' ";
		$result_set = selectQuery( $sql );
		if( mysqli_num_rows( $result_set ) > 0 ){
			echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Plugin already exists." );
			return;
		}
		else{
			$sql="UPDATE plugins SET plugin_display_name = '$plugin_display_name' WHERE plugin_name='$plugin_name' ";
			$rows = insertQuery( $sql );
			if( $rows > 0 ){
				echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, "Plugin " . $plugin_name . " successfully updated" );
				return;
			}
			else{
				echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Something went wrong.please try again" );
				return;
			}
		}
	}

	function get_plugin_data(){
		checkPrivilegeForFunction( __FUNCTION__ );
		$plugin_name = $_REQUEST['plugin_name'];
		$sql="SELECT * FROM plugins WHERE plugin_name = '$plugin_name' ";
		$result_set = selectQuery( $sql );
		if( mysqli_num_rows( $result_set ) > 0 ){
			$values = mysqli_fetch_assoc( $result_set );
			echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $values );
			return;
		}
		else{
			echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "No such Plugin Present." );
			return;
		}
	}
	
	function delete_plugin_name(){
		//checkPrivilegeForFunction( __FUNCTION__ );
		$plugin_name = $_REQUEST['plugin_name'];
		$sql="SELECT * FROM plugins WHERE plugin_name = '$plugin_name' ";
		$result_set = selectQuery( $sql );
		if( mysqli_num_rows( $result_set ) > 0 ){
			$sql = "DELETE FROM plugins WHERE plugin_name = '$plugin_name'";
			$rows = deleteQuery( $sql );
			if( $rows > 0 ){
				echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, "Plugin Successfully Deleted" );
				return;
			}
			else{
				echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Something went Wrong." );
				return;
			}
		}
		else{
			echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "No such Plugin Present." );
			return;
		}
	}	

?>