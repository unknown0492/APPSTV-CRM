<?php
	
	function create_page_group(){
		checkPrivilegeForFunction( __FUNCTION__ );
		
		$page_group_sequence 	= $_REQUEST[ 'page_group_sequence' ];
		$page_group_name 		= $_REQUEST[ 'page_group_name' ];
		$icon 					= $_REQUEST[ 'page_group_icon' ];
		
		// Do the validation Here
		
		// emptiness validations
		validateEmptyString( $page_group_name, __FUNCTION__, 'Page Group Name is required. Please enter a valid name and try again !' );
		validate( $page_group_name, __FUNCTION__, "/^[a-zA-Z0-9 ]*$/", 'Only use lowercase, uppercase alphabets, digits and white spaces !' );
		
		
		validate( $icon, __FUNCTION__, "/^[a-zA-Z_ -]*$/", 'Only Alphabets, white spaces, underscore and hyphen are allowed' );
		
		
		$page_group_sequence 	= htmlentities( $page_group_sequence );
		$page_group_name 		= htmlentities( $page_group_name );
		$icon 					= htmlentities( $icon );
		
		$sql = "SELECT page_group_name FROM page_group WHERE page_group_name = '$page_group_name'";
		$result_set = selectQuery( $sql );
		if( mysqli_num_rows( $result_set ) > 0 ){
			echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Page Group with the name $page_group_name already exist. Please try again with another name !" );
			return;
		}
		
		$sql  = "INSERT into page_group( `page_group_name`, `page_group_sequence`, `icon` ) VALUES( '$page_group_name', $page_group_sequence, '$icon' )";
		$rows = insertQuery( $sql );
		if( $rows > 0 ){
			echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, "Page Group created successfully" );
			return;
		}
		echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Error occurred while trying to create page group" );
		return;
	}
	
	function create_page(){
		checkPrivilegeForFunction( __FUNCTION__ );
	
		/* print_r( $_REQUEST );
		return; */
		
		$page_group_id 		= $_REQUEST[ 'page_group_id' ];
		$page_name 			= $_REQUEST[ 'page_name' ];
		$page_sequence 		= $_REQUEST[ 'page_sequence' ];
		$icon 				= $_REQUEST[ 'page_icon' ];
		$visible			= $_REQUEST[ 'is_visible' ];
		$page_title 		= $_REQUEST[ 'page_title' ];
		$functionality_id	= $_REQUEST[ 'functionality_id' ];
		$description		= $_REQUEST[ 'description' ];
		$plugin_id			= $_REQUEST[ 'plugin_id' ];
		$tags				= $_REQUEST[ 'tags' ];
		$image				= $_REQUEST[ 'image' ];
		$content			= $_REQUEST[ 'content' ];
		
		
		// Do the validation Here
		
		// checking for emptiness
		validateEmptyDigitString( $page_group_id, __FUNCTION__, 'Page Group ID is missing. Refresh the page and try again !' );
		validateEmptyString( $page_name, __FUNCTION__, 'Page Name is required !' );
		validateEmptyDigitString( $page_sequence, __FUNCTION__, 'Page Sequence is required !' );
		validateEmptyDigitString( $functionality_id, __FUNCTION__, 'Functionality is required !' );
		validateEmptyDigitString( $plugin_id, __FUNCTION__, 'Plugin is required !' );
		
		// Checking for Validity
		validate( $page_group_id, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), "Page Group ID " . getValidationErrMsg( "VLDTN_DIGITS" ) );
		validate( $page_name, __FUNCTION__, getValidationRegex( "VLDTN_PAGE_NAME" ), getValidationErrMsg( "VLDTN_PAGE_NAME" ) );
		validate( $page_sequence, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), getValidationErrMsg( "VLDTN_DIGITS" ) );
		validate( $icon, __FUNCTION__, getValidationRegex( "VLDTN_ICON" ), getValidationErrMsg( "VLDTN_ICON" ) );
		validate( $visible, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), getValidationErrMsg( "VLDTN_DIGITS" ) );
		validate( $page_title, __FUNCTION__, getValidationRegex( "VLDTN_PAGE_TITLE" ), getValidationErrMsg( "VLDTN_PAGE_TITLE" ) );
		validate( $functionality_id, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), getValidationErrMsg( "VLDTN_DIGITS" ) );
		validate( $description, __FUNCTION__, getValidationRegex( "VLDTN_PAGE_DESCRIPTION" ), getValidationErrMsg( "VLDTN_PAGE_DESCRIPTION" ) );
		validate( $plugin_id, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), getValidationErrMsg( "VLDTN_DIGITS" ) );
		validate( $tags, __FUNCTION__, getValidationRegex( "VLDTN_PAGE_TAGS" ), getValidationErrMsg( "VLDTN_PAGE_TAGS" ) );
		//validate( $image, __FUNCTION__, getValidationRegex( "VLDTN_URL" ), getValidationRegex( "VLDTN_URL" ) );
		validate( $content, __FUNCTION__, getValidationRegex( "VLDTN_PAGE_CONTENT" ), getValidationErrMsg( "VLDTN_PAGE_CONTENT" ) );
		
		
		// Check if the page_name already exist
		$sql = "SELECT page_name FROM pages WHERE page_name = '$page_name'";
		$result_set = selectQuery( $sql );
		if( mysqli_num_rows( $result_set ) > 0 ){
			echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "A Page with the name \"$page_name\" already exist. Please try another name !" );
			return;
		}
		
		$sql  = "INSERT into pages( `page_name`, `page_sequence`, `icon`, `visible`, `page_title`, `functionality_id`, `description`, `tags`, `image`, `content`, `plugin_id`, `page_group_id` ) VALUES( '$page_name', '$page_sequence', '$icon', '$visible', '$page_title', '$functionality_id', '$description' , '$tags' , '$image' , '$content' , $plugin_id, $page_group_id )";
		$rows = insertQuery( $sql );
		if( $rows > 0 ){
			// get page_id
			$sql 			= "SELECT page_id FROM pages WHERE page_name = '$page_name'";
			$result_set 	= selectQuery( $sql );
			$val 			= mysqli_fetch_object( $result_set );
			$page_id 		= $val->page_id;
			
			// insert into pages_groups
			$sql  = "INSERT into pages_groups( `group_id`, `page_id`, `page_sequence` ) VALUES( '$page_group_id', '$page_id', $page_sequence )";
			$rows = insertQuery( $sql );
			if( $rows > 0 ){
				echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, "Page created and mapped to the Functionality successfully" );
				return;
			}
		}
		echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Failed to create page !" );
		return;
	}
	
	function delete_page(){
		checkPrivilegeForFunction( __FUNCTION__ );
		
		$page_id = @$_REQUEST[ 'page_id' ];
		
		validateEmptyDigitString( $page_id, __FUNCTION__, 'Page ID missing. Refresh the page and try again !' );
		validate( $page_id, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), "Page ID " . getValidationErrMsg( "VLDTN_DIGITS" ) );
		
		$sql = "DELETE FROM pages WHERE page_id = $page_id";
		$rows = deleteQuery( $sql );
		
		if( $rows > 0 ){
			echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, "Page deleted successfully" );
			return;
		}
		echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Error occurred !" );
		return;
	}
	
	function update_page_group(){
		checkPrivilegeForFunction( __FUNCTION__ );
		
		$page_group_id 			= $_REQUEST[ 'page_group_id' ];
		$page_group_name 		= $_REQUEST[ 'page_group_name' ];
		$page_group_sequence 	= $_REQUEST[ 'page_group_sequence' ];
		$icon 					= $_REQUEST[ 'page_group_icon' ];
		
		// Do the validation Here
		// emptiness validations
		if ( empty( $page_group_name ) ){
			echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Page Group Name is required. Please enter a valid name and try again !" );
			return;
		}
		else{
			if( !preg_match( "/^[a-zA-Z0-9 ]*$/", $page_group_name ) ){
				echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Only use lowercase, uppercase alphabets, digits and white spaces !" );
				return;
			}
		}
		
		if( !preg_match( "/^[a-zA-Z_ -]*$/", $icon ) ){
			echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Only Alphabets, white spaces, underscore and hyphen are allowed" );
			return;
		}
		
		/* echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, "validated" );
		return; */
		
		$page_group_sequence 	= htmlentities( $_REQUEST[ 'page_group_sequence' ] );
		$page_group_name 		= htmlentities( $_REQUEST[ 'page_group_name' ] );
		$icon 					= htmlentities( $_REQUEST[ 'page_group_icon' ] );
		/* echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, "validated" );
		return; */
		
		$sql = "SELECT page_group_id FROM page_group WHERE page_group_id = $page_group_id";
		$result_set = selectQuery( $sql );
		if( mysqli_num_rows( $result_set ) <= 0 ){
			echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Invalid Page Group ID" );
			return;
		}
		
		$sql  = "UPDATE page_group SET page_group_name = '$page_group_name', page_group_sequence = $page_group_sequence, icon = '$icon' WHERE page_group_id = $page_group_id";
		$rows = updateQuery( $sql );
		if( $rows > 0 ){
			echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, "Page Group information updated successfully" );
			return;
		}
		echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Error occurred !" );
		return;
	}
	
	function update_page(){
		checkPrivilegeForFunction( __FUNCTION__ );
		
		//print_r( $_REQUEST );
		//return;
		
		$page_group_id 		= $_REQUEST[ 'page_group_id' ];
		$page_id 			= $_REQUEST[ 'page_id' ];
		$page_name 			= $_REQUEST[ 'page_name' ];
		$page_sequence 		= $_REQUEST[ 'page_sequence' ];
		$icon 				= $_REQUEST[ 'page_icon' ];
		$visible			= $_REQUEST[ 'is_visible' ];
		$page_title 		= $_REQUEST[ 'page_title' ];
		$functionality_id	= $_REQUEST[ 'functionality_id' ];
		$description		= $_REQUEST[ 'description' ];
		$plugin_id			= $_REQUEST[ 'plugin_id' ];
		$tags				= $_REQUEST[ 'tags' ];
		$image				= $_REQUEST[ 'image' ];
		$content			= $_REQUEST[ 'content' ];
		
		// checking for emptiness
		validateEmptyDigitString( $page_id, __FUNCTION__, 'Page ID missing. Refresh the page and try again !' );
		validateEmptyDigitString( $page_group_id, __FUNCTION__, 'Please select a Page Group !' );
		validateEmptyString( $page_name, __FUNCTION__, 'Page Name is required !' );
		validateEmptyDigitString( $page_sequence, __FUNCTION__, 'Page Sequence is required !' );
		validateEmptyDigitString( $functionality_id, __FUNCTION__, 'Functionality is required !' );
		validateEmptyDigitString( $plugin_id, __FUNCTION__, 'Plugin is required !' );
		
		// Checking for Validity
		validate( $page_id, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), "Page ID " . getValidationErrMsg( "VLDTN_DIGITS" ) );
		validate( $page_group_id, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), "Page Group ID " . getValidationErrMsg( "VLDTN_DIGITS" ) );
		validate( $page_name, __FUNCTION__, getValidationRegex( "VLDTN_PAGE_NAME" ), getValidationErrMsg( "VLDTN_PAGE_NAME" ) );
		validate( $page_sequence, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), "Page Sequence ". getValidationErrMsg( "VLDTN_DIGITS" ) );
		validate( $icon, __FUNCTION__, getValidationRegex( "VLDTN_ICON" ), getValidationErrMsg( "VLDTN_ICON" ) );
		validate( $visible, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), getValidationErrMsg( "VLDTN_DIGITS" ) );
		validate( $page_title, __FUNCTION__, getValidationRegex( "VLDTN_PAGE_TITLE" ), getValidationErrMsg( "VLDTN_PAGE_TITLE" ) );
		validate( $functionality_id, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), getValidationErrMsg( "VLDTN_DIGITS" ) );
		validate( $description, __FUNCTION__, getValidationRegex( "VLDTN_PAGE_DESCRIPTION" ), getValidationErrMsg( "VLDTN_PAGE_DESCRIPTION" ) );
		validate( $plugin_id, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), getValidationErrMsg( "VLDTN_DIGITS" ) );
		validate( $tags, __FUNCTION__, getValidationRegex( "VLDTN_PAGE_TAGS" ), getValidationErrMsg( "VLDTN_PAGE_TAGS" ) );
		//validate( $image, __FUNCTION__, getValidationRegex( "VLDTN_URL" ), getValidationRegex( "VLDTN_URL" ) );
		validate( $content, __FUNCTION__, getValidationRegex( "VLDTN_PAGE_CONTENT" ), getValidationErrMsg( "VLDTN_PAGE_CONTENT" ) );
		
		
		
		// Check if Page_id is valid
		$sql = "SELECT page_id FROM pages WHERE page_id = $page_id";
		$result_set = selectQuery( $sql );
		if( mysqli_num_rows( $result_set ) <= 0 ){
			echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "No such page exist in the system !" );
			return;
		}
		
		$sql	= "UPDATE pages SET page_group_id = $page_group_id, page_name = '$page_name', page_sequence='$page_sequence', icon='$icon', visible='$visible', page_title='$page_title', functionality_id='$functionality_id', description = '$description', tags='$tags', image='$image', content='$content', plugin_id = '$plugin_id' WHERE page_id = $page_id";
		$rows	= updateQuery( $sql );
		//echo $sql;
		if( $rows > 0 ){
			// Move the page_id to new page_group_id if it has changed
			$sql = "DELETE FROM pages_groups WHERE page_id = $page_id";
			deleteQuery( $sql );
			
			$sql  = "INSERT INTO pages_groups( `group_id`, `page_id`, `page_sequence` ) VALUES( $page_group_id, $page_id, $page_sequence )";
			$rows = insertQuery( $sql );
			if( $rows > 0 ){
				echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, "Page information updated successfully !" );
				return;
			}
			echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Failed to update page group !" );
			return;
		}
		echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Error occurred !" );
		return;
	}

	
	function delete_page_group(){
		checkPrivilegeForFunction( __FUNCTION__ );
	
		$page_group_id = @$_REQUEST[ 'page_group_id' ];
		
		if( $page_group_id == 0 ){
			echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Default Page Group cannot be deleted !" );
			return;
		}
		
		if( trim( $page_group_id ) == "" ){
			echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Invalid Page Group ID !" );
			return;
		}
	
		$sql 		= "SELECT page_group_id FROM page_group WHERE page_group_id = $page_group_id";
		$result_set = selectQuery( $sql );
		if( mysqli_num_rows( $result_set ) <= 0 ){
			echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Invalid Page Group ID" );
			return;
		}
	
		// Move the page from the group to be deleted to the Default Group (id=0)
		$sql = "UPDATE pages_groups SET group_id = 0 WHERE group_id = $page_group_id";
		$rows = updateQuery( $sql );
		if( $rows > 0 ){
			$sql = "DELETE FROM page_group WHERE page_group_id = $page_group_id";
			$rows = deleteQuery( $sql );
			if( $rows > 0 ){
				echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, "Page Group deleted successfully" );
				return;
			}
			echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Deleting page group failed. The child pages have been moved to the Default Page Group" );
			return;
		}
	
	
		echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Error occurred !" );
	}
	
	
	function get_pages_from_selected_group(){
		checkPrivilegeForFunction( __FUNCTION__ );
	
		$page_group_id = $_REQUEST[ 'page_group_id' ];
	
		validateEmptyDigitString( $page_group_id, __FUNCTION__, "Please select a Privilege Group ID" );
		validate( $page_group_id, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), "Privilege Group ID " . getValidationErrMsg( "VLDTN_DIGITS" ) );
	
		$sql = "select distinct p.page_id, p.page_name, p.page_sequence, p.icon, p.visible, f.functionality_name, f.functionality_id FROM pages p, functionalities f WHERE (p.page_group_id=$page_group_id) AND (p.functionality_id=f.functionality_id)";
		//echo $sql;
		//return;
		$result_set = selectQuery( $sql );
	
		if( mysqli_num_rows( $result_set ) == 0 ){
			echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "No pages yet under this Group !" );
			return;
		}
	
		$arr = array();
		while( ( $val = mysqli_fetch_assoc( $result_set )) != null ){
			$arr[] = $val;
		}
	
		echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, json_encode( $arr ) );
	}

	function get_page_info(){
		checkPrivilegeForFunction( __FUNCTION__ );
		
		$page_id = @$_REQUEST[ 'page_id' ];
		
		validateEmptyDigitString( $page_id, __FUNCTION__, "Page ID is missing !" );
		validate( $page_id, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), getValidationErrMsg( "VLDTN_DIGITS" ) );
		
		$sql = "select p.page_id, p.page_name, p.page_sequence, p.icon, p.visible, p.page_title, f.functionality_id, p.page_group_id, p.description, p.tags, p.image, 
				p.content, p.plugin_id, f.is_page FROM pages p, functionalities f, plugins pl WHERE (p.page_id=$page_id) AND (p.functionality_id=f.functionality_id) AND (pl.plugin_id=p.plugin_id)";
		// echo $sql;
		$result_set = selectQuery( $sql );
		if( mysqli_num_rows( $result_set ) == 0 ){
			echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "No page definition found !" );
			return;
		}
		
		$arr = array();
		while( ( $val = mysqli_fetch_assoc( $result_set )) != null ){
			$arr[] = $val;
		}
		
		echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, json_encode( $arr ) );
		
	}	
?>