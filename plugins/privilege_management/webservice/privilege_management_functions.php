<?php
	
	function create_privilege_group(){
		checkPrivilegeForFunction( __FUNCTION__ );
		
		$privilege_group_sequence 	= $_REQUEST[ 'privilege_group_sequence' ];
		$privilege_group_name 		= $_REQUEST[ 'privilege_group_name' ];
		
		validate( $privilege_group_sequence, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), "Privilege Group Sequence " . getValidationErrMsg( "VLDTN_DIGITS" ) );
		
		validateEmptyString( $privilege_group_name, __FUNCTION__, "Privilege Group Name cannot be empty" );
		validate( $privilege_group_name, __FUNCTION__, getValidationRegex( "VLDTN_PRIVILEGE_GROUP_NAME" ), getValidationErrMsg( "VLDTN_PRIVILEGE_GROUP_NAME" ) );
		
		
		$sql = "SELECT privilege_group_name FROM privilege_group WHERE privilege_group_name = '$privilege_group_name'";
		$result_set = selectQuery( $sql );
		if( mysqli_num_rows( $result_set ) > 0 ){
			echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Privilege Group with the name $privilege_group_name already exist. Please try again using another name" );
			return;
		}
		
		$sql  = "INSERT into privilege_group( `privilege_group_name`, `privilege_group_sequence` ) VALUES( '$privilege_group_name', $privilege_group_sequence )";
		$rows = insertQuery( $sql );
		if( $rows > 0 ){
			echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, "Privilege Group created successfully" );
			return;
		}
		echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, "Error occurred while trying to create privilege group" );
		return;
	}
	
	function create_privilege(){
		checkPrivilegeForFunction( __FUNCTION__ );
	
		$privilege_group_id 		= $_REQUEST[ 'privilege_group_id' ];
		$privilege_name 			= $_REQUEST[ 'privilege_name' ];	
		$functionality_name 		= $_REQUEST[ 'functionality_name' ];
		$privilege_description		= $_REQUEST[ 'privilege_description' ];
		$functionality_description 	= $_REQUEST[ 'functionality_description' ];
		$is_page				 	= $_REQUEST[ 'is_page' ];
		$plugin_id					= $_REQUEST[ 'plugin_id' ];
		
		
		validateEmptyDigitString( $privilege_group_id, __FUNCTION__, "Privilege Group is a required field !" );
		validate( $privilege_group_id, __FUNCTION__, getValidationRegex( 'VLDTN_DIGITS' ), 'Privilege Group ID ' . getValidationRegex( 'VLDTN_DIGITS' ) );
		
		validateEmptyDigitString( $is_page, __FUNCTION__, "Is a page is a required field !" );
		validate( $is_page, __FUNCTION__, getValidationRegex( 'VLDTN_DIGITS' ), "Is a page " . getValidationRegex( 'VLDTN_DIGITS' ) );
		
		validateEmptyDigitString( $plugin_id, __FUNCTION__, "Plugin Name is a required field !" );
		validate( $plugin_id, __FUNCTION__, getValidationRegex( 'VLDTN_DIGITS' ), "Plugin Name " . getValidationRegex( 'VLDTN_DIGITS' ) );
		
		validateEmptyString( $privilege_name, __FUNCTION__, "Privilege Name is a required field !" );
		validate( $privilege_name, __FUNCTION__, getValidationRegex( 'VLDTN_PRIVILEGE_NAME' ), getValidationRegex( 'VLDTN_PRIVILEGE_NAME' ) );
		
		validateEmptyString( $privilege_description, __FUNCTION__, "Privilege Description is a required field !" );
		validate( $privilege_description, __FUNCTION__, getValidationRegex( 'VLDTN_PRIVILEGE_DESCRIPTION' ), getValidationRegex( 'VLDTN_PRIVILEGE_DESCRIPTION' ) );
		
		validateEmptyString( $functionality_name, __FUNCTION__, "Functionality Name is a required field !" );
		validate( $functionality_name, __FUNCTION__, getValidationRegex( 'VLDTN_FUNCTIONALITY_NAME' ), getValidationRegex( 'VLDTN_FUNCTIONALITY_NAME' ) );
		
		validateEmptyString( $functionality_description, __FUNCTION__, "Functionality Description is a required field !" );
		validate( $functionality_description, __FUNCTION__, getValidationRegex( 'VLDTN_FUNCTIONALITY_DESCRIPTION' ), getValidationRegex( 'VLDTN_FUNCTIONALITY_DESCRIPTION' ) );
		
		
		// Check if the privilege_name already exist
		$sql = "SELECT * FROM privileges WHERE privilege_name = '$privilege_name'";
		$result_set = selectQuery( $sql );
		if( mysqli_num_rows( $result_set ) > 0 ){
			echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "A privilege with the entered name already exist. Please try another name !" );
			return;
		}
		
		// Check if the functionality_name already exist
		$sql = "SELECT * FROM functionalities WHERE functionality_name = '$functionality_name'";
		$result_set = selectQuery( $sql );
		if( mysqli_num_rows( $result_set ) > 0 ){
			echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "A functionality with the entered name already exist. Please try another name !" );
			return;
		}
		
		$sql  = "INSERT into privileges( `privilege_name`, `privilege_description`) VALUES( '$privilege_name', '$privilege_description')";
		$rows = insertQuery( $sql );
		if( $rows > 0 ){
			// get privilege_id
			$sql 			= "SELECT privilege_id FROM privileges WHERE privilege_name = '$privilege_name'";
			$result_set 	= selectQuery( $sql );
			$val 			= mysqli_fetch_object( $result_set );
			$privilege_id 	= $val->privilege_id;
			
			$sql  = "INSERT into functionalities( `functionality_name`, `functionality_description`, `is_page`  ) VALUES( '$functionality_name', '$functionality_description', '$is_page'  )";
			$rows = insertQuery( $sql );
			if( $rows > 0 ){
				$sql 				= "SELECT functionality_id FROM functionalities WHERE functionality_name = '$functionality_name'";
				$result_set 		= selectQuery( $sql );
				$val 				= mysqli_fetch_object( $result_set );
				$functionality_id 	= $val->functionality_id;
					
				$sql = "INSERT into privileges_functionalities( `privilege_id`, `functionality_id`, `plugin_id` ) VALUES( $privilege_id, $functionality_id, $plugin_id )";
				$rows = insertQuery( $sql );
				if( $rows > 0 ){
					$sql = "INSERT into privileges_groups( `privilege_group_id`, `privilege_id` ) VALUES( $privilege_group_id, $privilege_id )";
					$rows = insertQuery( $sql );
					if( $rows > 0 ){
						echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, "Privilege and Functionality created and mapped successfully" );
						return;
					}
					$sql = "DELETE FROM privileges_functionalities WHERE privilege_id = '$privilege_id' AND functionality_id = '$functionality_id'";
					$rows = deleteQuery( $sql );
				}
				
				$sql = "DELETE FROM functionalities WHERE functionality_id = '$functionality_id'";
				$rows = deleteQuery( $sql ); 
			}
			$sql = "DELETE FROM privileges WHERE privilege_id = '$privilege_id'";
			$rows = deleteQuery( $sql ); 
		}
		echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Failed to create privilege and funcionality !" );
		return;
	}
	
	function get_privilege_information(){
		checkPrivilegeForFunction( __FUNCTION__ );
		
		// print_r( $_REQUEST );
		
		$privilege_id 		= $_REQUEST[ 'privilege_id' ];
		
		validateEmptyDigitString( $privilege_id, __FUNCTION__, "Privilege ID is a required field !" );
		validate( $privilege_id, __FUNCTION__, getValidationRegex( 'VLDTN_DIGITS' ), 'Privilege ID ' . getValidationRegex( 'VLDTN_DIGITS' ) );
		
		$sql = "SELECT pg.privilege_group_name, pgs.privilege_group_id, p.privilege_id, p.privilege_name, p.privilege_description, f.functionality_id, f.functionality_name, f.functionality_description, f.is_page, pf.plugin_id, pl.plugin_name 
				FROM privilege_group pg, privileges_groups pgs, privileges p, functionalities f, privileges_functionalities pf, plugins pl
				WHERE (p.privilege_id = $privilege_id )
				AND (pgs.privilege_id = $privilege_id )
				AND (pgs.privilege_group_id = pg.privilege_group_id)
				AND (p.privilege_id = pf.privilege_id)
				AND (f.functionality_id = pf.functionality_id)
				AND (pf.plugin_id = pl.plugin_id)";
		//echo $sql;
		$result_set = selectQuery( $sql );
		if( mysqli_num_rows( $result_set ) == 0 ){
			echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Couldn't retrieve Privilege information" );
			return;
		}
		
		$val = array( mysqli_fetch_assoc( $result_set ) );
		echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, json_encode( $val ) );
	}
	
	function get_privileges_from_selected_group(){
		checkPrivilegeForFunction( __FUNCTION__ );
		
		$privilege_group_id = $_REQUEST[ 'privilege_group_id' ];
		
		validateEmptyDigitString( $privilege_group_id, __FUNCTION__, "Please select a Privilege Group ID" );
		validate( $privilege_group_id, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), "Privilege Group ID " . getValidationErrMsg( "VLDTN_DIGITS" ) );
		
		$sql = "select DISTINCT f.functionality_name, f.functionality_description, f.is_page, p.privilege_name, p.privilege_description, p.privilege_id, f.functionality_id, pf.plugin_id, pl.plugin_name from functionalities f, privileges p, privileges_functionalities pf, privileges_groups pg, plugins pl where pg.privilege_group_id=$privilege_group_id AND p.privilege_id = pf.privilege_id AND f.functionality_id = pf.functionality_id and pg.privilege_id = p.privilege_id and pf.plugin_id=pl.plugin_id";
		$result_set = selectQuery( $sql );
		
		if( mysqli_num_rows( $result_set ) == 0 ){
			echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "No privileges yet under this Group !" );
			return;
		}
		
		$arr = array();
		while( ( $val = mysqli_fetch_assoc( $result_set )) != null ){
			$arr[] = $val;
		}
		
		echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, json_encode( $arr ) );
	}
	
	function delete_privilege(){
		checkPrivilegeForFunction( __FUNCTION__ );
		
		$privilege_id = $_REQUEST[ 'privilege_id' ];
		$mr = new MyRegex();
		
		validateEmptyDigitString( $privilege_id, __FUNCTION__, "Privilege ID not provided !" );
		validate( $privilege_id, __FUNCTION__, $mr->getValidationRegex( "VLDTN_DIGITS" ), $mr->getValidationErrMsg( "VLDTN_DIGITS" ) );
		
		$sql = "SELECT functionality_id from privileges_functionalities WHERE privilege_id = $privilege_id";
		$result_set = selectQuery( $sql );
		$val = mysqli_fetch_object( $result_set );
		$functionality_id = $val->functionality_id;
		
		$sql = "DELETE FROM privileges_functionalities WHERE privilege_id = $privilege_id";
		$rows = deleteQuery( $sql );
		
		$sql = "DELETE FROM privileges WHERE privilege_id = $privilege_id";
		$rows = deleteQuery( $sql );
		
		$sql = "DELETE FROM functionalities WHERE functionality_id = $functionality_id";
		$rows = deleteQuery( $sql );
		
		$sql = "DELETE FROM privileges_groups WHERE privilege_id = $privilege_id";
		$rows = deleteQuery( $sql );
		
		if( $rows > 0 ){
			echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, "Privilege and Functionality deleted successfully" );
			return;
		}
		echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Error occurred !" );
		return;
	}
	
	function update_privilege_group(){
		checkPrivilegeForFunction( __FUNCTION__ );
		
		$privilege_group_id 		= $_REQUEST[ 'privilege_group_main_id' ];
		$privilege_group_name 		= $_REQUEST[ 'privilege_group_name' ];
		$privilege_group_sequence 	= $_REQUEST[ 'privilege_group_sequence' ];
		
		// Do validation here
		
		
		$sql = "SELECT privilege_group_id FROM privilege_group WHERE privilege_group_id = $privilege_group_id";
		$result_set = selectQuery( $sql );
		if( mysqli_num_rows( $result_set ) <= 0 ){
			echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Invalid Privilege Group ID" );
			return;
		}
		
		$sql  = "UPDATE privilege_group SET privilege_group_name = '$privilege_group_name', privilege_group_sequence = $privilege_group_sequence WHERE privilege_group_id = $privilege_group_id";
		$rows = updateQuery( $sql );
		if( $rows > 0 ){
			echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, "Privilege Group information updated successfully" );
			return;
		}
		echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Error occurred !" );
		return;
	}
	
	function update_privilege(){
		checkPrivilegeForFunction( __FUNCTION__ );
		
		$plugin_id				= $_REQUEST[ 'plugin_id' ];
		$privilege_id			= $_REQUEST[ 'privilege_id' ];
		$privilege_group_id		= $_REQUEST[ 'privilege_group_id' ];
		$privilege_name			= $_REQUEST[ 'privilege_name' ];
		$privilege_description	= $_REQUEST[ 'privilege_description' ];
		
		$functionality_id			= $_REQUEST[ 'functionality_id' ];
		$functionality_name			= $_REQUEST[ 'functionality_name' ];
		$functionality_description	= $_REQUEST[ 'functionality_description' ];
		$is_page				 	= $_REQUEST[ 'is_page' ];
		
		// Do the validation here
		validateEmptyDigitString( $plugin_id, __FUNCTION__, 'Plugin ID is invalid !' );
		validateEmptyDigitString( $is_page, __FUNCTION__, 'Is a Page selection is invalid !' );
		validateEmptyDigitString( $privilege_group_id, __FUNCTION__, 'Privilege Group ID is invalid !' );
		validateEmptyDigitString( $functionality_id, __FUNCTION__, 'Functionality ID is invalid !' );
		validateEmptyDigitString( $privilege_id, __FUNCTION__, 'Privilege ID is invalid !' );
		
		validate( $plugin_id, __FUNCTION__, getValidationRegex( 'VLDTN_DIGITS' ), "Plugin ID " . getValidationErrMsg( 'VLDTN_DIGITS' ) );
		validate( $is_page, __FUNCTION__, getValidationRegex( 'VLDTN_DIGITS' ), "Is Page " . getValidationErrMsg( 'VLDTN_DIGITS' ) );
		validate( $privilege_id, __FUNCTION__, getValidationRegex( 'VLDTN_DIGITS' ), "Privilege ID " . getValidationErrMsg( 'VLDTN_DIGITS' ) );
		validate( $functionality_id, __FUNCTION__, getValidationRegex( 'VLDTN_DIGITS' ), "Functionality ID " . getValidationErrMsg( 'VLDTN_DIGITS' ) );
		validate( $privilege_group_id, __FUNCTION__, getValidationRegex( 'VLDTN_DIGITS' ), "Privilege Group ID " . getValidationErrMsg( 'VLDTN_DIGITS' ) );
		validate( $privilege_name, __FUNCTION__, getValidationRegex( 'VLDTN_PRIVILEGE_NAME' ), getValidationErrMsg( 'VLDTN_PRIVILEGE_NAME' ) );
		validate( $privilege_description, __FUNCTION__, getValidationRegex( 'VLDTN_PRIVILEGE_DESCRIPTION' ), getValidationErrMsg( 'VLDTN_PRIVILEGE_DESCRIPTION' ) );
		validate( $functionality_name, __FUNCTION__, getValidationRegex( 'VLDTN_FUNCTIONALITY_NAME' ), getValidationErrMsg( 'VLDTN_FUNCTIONALITY_NAME' ) );
		validate( $functionality_description, __FUNCTION__, getValidationRegex( 'VLDTN_FUNCTIONALITY_DESCRIPTION' ), getValidationErrMsg( 'VLDTN_FUNCTIONALITY_DESCRIPTION' ) );
		
		
		// Check if Privilege_id is valid
		$sql = "SELECT privilege_id FROM privileges WHERE privilege_id = $privilege_id";
		$result_set = selectQuery( $sql );
		if( mysqli_num_rows( $result_set ) <= 0 ){
			echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Invalid Privilege ID" );
			return;
		}
		
		// Check if functionality_id is valid
		$sql = "SELECT functionality_id FROM functionalities WHERE functionality_id = $functionality_id";
		$result_set = selectQuery( $sql );
		if( mysqli_num_rows( $result_set ) <= 0 ){
			echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Invalid Functionality ID" );
			return;
		}
		
		// Check if privilege_id and functionalit_id mapping is correct
		$sql = "SELECT * FROM privileges_functionalities WHERE ( functionality_id = $functionality_id ) AND ( privilege_id = $privilege_id )";
		$result_set = selectQuery( $sql );
		if( mysqli_num_rows( $result_set ) <= 0 ){
			echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Invalid Privilege-Functionality Mapping" );
			return;
		}
		
		$sql	= "UPDATE privileges SET privilege_name = '$privilege_name', privilege_description = '$privilege_description' WHERE privilege_id = $privilege_id";
		$rows	= updateQuery( $sql );
		if( $rows > 0 ){
			$sql	= "UPDATE functionalities SET functionality_name = '$functionality_name', functionality_description = '$functionality_description',is_page = $is_page WHERE functionality_id = $functionality_id";
			$rows	= updateQuery( $sql );
			if( $rows > 0 ){
				$sql = "SELECT privilege_group_id FROM privileges_groups WHERE privilege_id = $privilege_id";
				$result_set = selectQuery( $sql );
				$val1 = mysqli_fetch_object( $result_set );
				$privilege_group_id_old = $val1->privilege_group_id;
				
				$sql = "UPDATE privileges_groups SET privilege_group_id = $privilege_group_id WHERE privilege_id = $privilege_id";
				$rows = updateQuery( $sql );
				if( $rows > 0 ){
					// Update Plugin Info
					$sql = "UPDATE privileges_functionalities SET plugin_id=$plugin_id WHERE (privilege_id=$privilege_id) AND (functionality_id=$functionality_id)";
					$rows = updateQuery( $sql );
					if( $rows > 0 ){
						echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, "Privilege and Functionality information updated successfully" );
						return;
					}
					echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Failed to update plugin id !" );
					return;
				}
			}
			echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, "Only Privilege information updated ! Failed to update the functionality information" );
			return;
		}
		echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Error occurred !" );
		return;
	}
	
	function delete_privilege_group(){
		checkPrivilegeForFunction( __FUNCTION__ );
		
		$privilege_group_id = $_REQUEST[ 'privilege_group_id' ];
		if( $privilege_group_id == 0 ){
			echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Default Privilege Group cannot be deleted !" );
			return;
		}
		
		$sql 		= "SELECT privilege_group_id FROM privilege_group WHERE privilege_group_id = $privilege_group_id";
		$result_set = selectQuery( $sql );
		if( mysqli_num_rows( $result_set ) <= 0 ){
			echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Invalid Privilege Group ID" );
			return;
		}
		
		// Move the privilege from the group to be deleted to the Default Group (id=0)
		$sql = "UPDATE privileges_groups SET privilege_group_id = 0 WHERE privilege_group_id = $privilege_group_id";
		$rows = updateQuery( $sql );
		if( $rows > 0 ){
			$sql = "DELETE FROM privilege_group WHERE privilege_group_id = $privilege_group_id";
			$rows = deleteQuery( $sql );
			if( $rows > 0 ){
				echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, "Privilege Group deleted successfully" );
				return;
			}
			echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Deleting privilege group failed. The child privileges have been moved to the Default Privilege Group" );
			return;
		}
		
		
		echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Error occurred !" );
	}
?>