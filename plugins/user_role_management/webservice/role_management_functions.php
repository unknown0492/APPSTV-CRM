<?php
	
	function create_role(){
		checkPrivilegeForFunction( __FUNCTION__ );
		
		$role_name	= trim( $_REQUEST[ 'role_name' ] );
		
		if( !isset( $_REQUEST[ 'privileges' ] ) ){
			echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "You have not selected any privilege for this Role. Please select few and try again !" );
			return;
		}
		$privileges = $_REQUEST[ 'privileges' ];
		
		$mr = new MyRegex();
		
		// Validate role_name and privileges in this section
		validateEmptyString( $role_name, __FUNCTION__, "Role Name cannot be empty. Please provide a Role Name !" );
		validate( $role_name, __FUNCTION__, $mr->getValidationRegex( "VLDTN_ROLE_NAME" ), $mr->getValidationErrMsg( "VLDTN_ROLE_NAME" ) );
		
		// Check if the role_name already exist
		$sql = "Select role_name FROM roles WHERE role_name = '$role_name'";
		$result_set = selectQuery( $sql );
		if( mysqli_num_rows( $result_set ) > 0 ){
			echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "This Role Name already exist, please try another name" );
			return;
		} 
		
		$sql = "INSERT into roles( `role_name` ) VALUES( '$role_name' )";
		$rows = insertQuery( $sql );
		if( $rows > 0 ){
			// Add the privileges for this role_id into roles_privileges, get the role_id first
			$sql = "Select role_id FROM roles WHERE role_name = '$role_name'";
			$result_set = selectQuery( $sql );
			$val = mysqli_fetch_object( $result_set );
			$role_id = $val->role_id;
			
			$data = "";
			foreach( $privileges as $val ){
				$data .= "(" . $role_id . "," . $val . "),";
			}
				
			$data = trim( $data, "," );
				
			// insert the new set of privileges
			$sql = "INSERT into roles_privileges( `role_id`, `privilege_id` ) VALUES$data";
			$rows = insertQuery( $sql );
			if( $rows > 0 ){
				echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, "Role created and Privileges assigned successfully" );
				return;
			}
			
		}
		echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Error occurred !" );
	}
		
	function update_role(){
		checkPrivilegeForFunction( __FUNCTION__ );
		
		$role_id	= $_REQUEST[ 'role_id' ];
		
		if( !isset( $_REQUEST[ 'privileges' ] ) ){
			echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "You have not selected any privilege for this Role. Please select few and try again !" );
			return;
		}
		
		if( onlyAdminCan( $_SESSION[ SESSION_USER_ID ], $role_id ) ){
			echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "You dont have sufficient privileges to perform this operation !" );
			return;
		}
		
		$privileges = $_REQUEST[ 'privileges' ];
		$mr = new MyRegex();
		
		// Validate role_name and privileges in this section
		validateEmptyString( $role_id, __FUNCTION__, "Role ID cannot be empty !" );
		validate( $role_id, __FUNCTION__, $mr->getValidationRegex( "VLDTN_DIGITS" ), $mr->getValidationErrMsg( "VLDTN_DIGITS" ) );
		
		/* // Only admin can update the privileges for an admin
		if( ( $role_id == roleToRoleId( SESSION_ADMIN ) ) &&  // if role to be edited is of admin
				( $_SESSION[ SESSION_AUTHORIZATION ] != roleToRoleId( SESSION_ADMIN ) ) ){  // if change karne wala is not the admin
			echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "You dont have sufficient privileges to perform this operation !" );
			return;
		} */
		
		// Check if role_id is valid
		$sql = "select role_id from roles Where role_id = $role_id";
		$result_set = selectQuery( $sql );
		if( mysqli_num_rows( $result_set ) == 0 ){
			echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "You have selected an invalid Role !" );
			return;
		}
		
		$sql = "DELETE from roles_privileges WHERE role_id = $role_id";
		$rows = deleteQuery( $sql );
		if( $rows > 0 ){
			
			/* // If privileges are not selected
			if( ( $privileges == "" ) || ( $privileges == NULL ) || $privileges == "null"  ){
				echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, "Role updated successfully with no Privileges assigned" );
				return;
			} */
			
			$data = "";
			foreach( $privileges as $val ){
				$data .= "(" . $role_id . "," . $val . "),";
			}
			
			$data = trim( $data, "," );
			
			// insert the new set of privileges
			$sql = "INSERT into roles_privileges( `role_id`, `privilege_id` ) VALUES$data";
			$rows = insertQuery( $sql );
			
			if( $rows > 0 ){
				echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, "Privileges updated successfully" );
				return;
			}
			echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, "Privileges got deleted !" );
		}
		echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Error occurred !" );
	}
	
	function delete_role(){
		checkPrivilegeForFunction( __FUNCTION__ );
		
		$role_id	= $_REQUEST[ 'role_id' ];
		
		if( onlyAdminCan( $_SESSION[ SESSION_USER_ID ], $role_id ) ){
			echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "You dont have sufficient privileges to perform this operation !" );
			return;
		}
		
		/* // Only admin can delete the privileges for an admin
		if( ( $role_id == roleToRoleId( SESSION_ADMIN ) ) &&  // if role to be edited is of admin
				( $_SESSION[ SESSION_AUTHORIZATION ] != roleToRoleId( SESSION_ADMIN ) ) ){  // if change karne wala is not the admin
			echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "You dont have sufficient privileges to perform this operation !" );
			return;
		} */
		
		// Check if role_id is valid
		$sql = "Select role_id from roles Where role_id = $role_id";
		$result_set = selectQuery( $sql );
		if( mysqli_num_rows( $result_set ) == 0 ){
			echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "You have selected an invalid Role !" );
			return;
		}
		
		$sql = "DELETE from roles WHERE role_id = $role_id";
		$rows = deleteQuery( $sql );
		if( $rows > 0 ){
			$new_role_id = roleToRoleId( SESSION_STAFF );
			$sql 		 = "UPDATE users SET role_id = $new_role_id WHERE role_id = $role_id";
			$result_set  = updateQuery( $sql );
			if( $rows > 0 ){
				echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, "Role has been deleted successfully. Users who possessed this role have now been reverted to the Default role \"Staff\"" );
				return;
			}
			echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, "Role has been deleted successfully. <br />Note: Make sure to change the role for the users who possessed the Deleted Role" );
			return;
		}
		
	}

?>