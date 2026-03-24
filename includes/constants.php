<?php
	
	define( 'GENERAL_ERROR_MESSAGE', "error" );
	define( 'GENERAL_CONFIRM_MESSAGE', "confirm" );
	define( 'GENERAL_WARNING_MESSAGE', "warning" );
	define( 'GENERAL_SUCCESS_MESSAGE', "success" );
	define( 'GENERAL_TOKEN_MESSAGE', "token_expired" );
	define( 'FILE_SEPARATOR', "/");
	
	// JSON Messages
	define( 'QUERY_FIRE_ERROR', "query-fire-error" );
	
	// Directory Names
        define( 'DIRNAME_TMP_DATA', 'tmp_data' );
        define( 'DIRNAME_EXPORTS', 'exports' );
	
        
        
	// HOOK NAMES
	define( "HOOK_AFTER_BODY_STARTS", "after_body_starts" );
	define( "HOOK_BEFORE_BODY_ENDS", "before_body_ends" );
	define( "HOOK_AFTER_FOOTER", "after_footer" );
	
	// PLUGIN CONSTANTS
	define( "PLUGIN_PATH", "./plugins/" );
	define( "PLUGIN_TEMP_PATH", "./plugins/tmp/" );
	define( "PLUGIN_CONFIG_PATH", "./plugins/plugin-config/" );
	define( "PLUGIN_WEBSERVICE_PATH", "./webservice/functions/" );
	
	// Privilege Names
	define( "PRIVILEGE_ASSIGN_ADMIN_ROLE", "assign_admin_role" );
	define( "PRIVILEGE_ASSIGN_A_ROLE", "assign_a_role" );
	define( "PRIVILEGE_CHECK_USER_ID_AVAILABILITY", "check_user_id_availability" );
	define( "PRIVILEGE_CREATE_NEW_USER", "create_new_user" );
	define( "PRIVILEGE_DELETE_A_USER", "delete_a_user" );
	define( "PRIVILEGE_DELETE_ADMIN", "delete_admin" );
	define( "PRIVILEGE_CREATE_NEW_PRIVILEGE", "create_new_privilege" );
	define( "PRIVILEGE_EDIT_PROFILE", "edit_profile" );
	
	// Role Names
	define( "ROLE_REGISTERED_USER", "registered_user" );
	define( "ROLE_ADMIN", "admin" );
	define( "ROLE_STAFF", "staff" );
	
	define( "ROLE_DEFAULT", ROLE_STAFF );
	
	
	// MYSQL Table Names
        /*
	include( './library/config.php' );
	global $table_prefix;
	
	define( 'DB_SITE_CONFIG_TABLE', $table_prefix . "site_config" );
	define( 'DB_USERS_TABLE', $table_prefix . "users" );
	define( 'DB_USER_PRIVILEGES_TABLE', $table_prefix . "user_privileges" );
	define( 'DB_HOOKS_TABLE', $table_prefix . "hooks" );
	define( 'DB_PAGES_TABLE', $table_prefix . "pages" );
	define( 'DB_PRIVILEGES_TABLE', $table_prefix . "privileges" );
	define( 'DB_ROLES_TABLE', $table_prefix . "roles" );
	
	// MYSQL Table Column Name
	define( "CLM_SITE_CONFIG_ID", "id" );
	define( "CLM_SITE_CONFIG_SITE_NAME", "site_name" );
	define( "CLM_SITE_CONFIG_DOMAIN_NAME", "domain_name" );
	define( "CLM_SITE_CONFIG_SITE_TAGLINE", "site_tagline" );
	define( "CLM_SITE_CONFIG_SITE_LOGO_IMAGE", "site_logo_image" );
	define( "CLM_SITE_CONFIG_URL_TYPE", "url_type" );
	define( "CLM_SITE_CONFIG_LINK_ENDS_WITH", "link_ends_with" );
	
	define( "CLM_USERS_USER_ID", "user_id" );
	define( "CLM_USERS_USER_ID_CHANGED", "user_id_changed" );
	define( "CLM_USERS_FNAME", "fname" );
	define( "CLM_USERS_LNAME", "lname" );
	define( "CLM_USERS_RESIDENCE", "residence" );
	define( "CLM_USERS_STREET", "street" );
	define( "CLM_USERS_COUNTRY", "country" );
	define( "CLM_USERS_STATE", "state" );
	define( "CLM_USERS_CITY", "city" );
	define( "CLM_USERS_ZIP_CODE", "pin_code" );
	define( "CLM_USERS_LANDMARK", "landmark" );
	define( "CLM_USERS_EMAIL", "email" );
	define( "CLM_USERS_PASSWORD", "password" );
	define( "CLM_USERS_SECURITY_QUESTION", "security_question" );
	define( "CLM_USERS_SECURITY_ANSWER", "security_answer" );
	define( "CLM_USERS_ABOUT_ME", "about_me" );
	define( "CLM_USERS_PROFILE_PICTURE", "profile_picture" );
	define( "CLM_USERS_REGISTERED_ON", "registered_on" );
	define( "CLM_USERS_ROLE_NAME", "role_name" );
	
	define( "CLM_HOOKS_ID", "id" );
	define( "CLM_HOOKS_HOOK_NAME", "hook_name" );
	define( "CLM_HOOKS_HOOK_DESCRIPTION", "hook_description" );
	define( "CLM_HOOKS_HOOK_CONTENT", "hook_content" );
	define( "CLM_HOOKS_HOOK_CONTENT_META", "hook_content_meta" );
	
	define( "CLM_PAGES_PAGE", "page" );
	define( "CLM_PAGES_PAGE_SEQUENCE", "page_sequence" );
	define( "CLM_PAGES_PAGE_NAME", "page_name" );
	define( "CLM_PAGES_TITLE", "title" );
	define( "CLM_PAGES_DESCRIPTION", "description" );
	define( "CLM_PAGES_TAGS", "tags" );
	define( "CLM_PAGES_CONTENT", "content" );
	define( "CLM_PAGES_IMAGE", "image" );
	define( "CLM_PAGES_VISIBLE", "visible" );
	
	define( "CLM_ROLES_ROLE_ID", "role_id" );
	define( "CLM_ROLES_ROLE_NAME", "role_name" );
	define( "CLM_ROLES_PRIVILEGE_NAME", "privilege_name" );
	
	define( "CLM_PRIVILEGES_PRIVILEGE_ID", "privilege_id" );
	define( "CLM_PRIVILEGES_PRIVILEGE_NAME", "privilege_name" );
	define( "CLM_PRIVILEGES_FUNCTIONALITY", "functionality" );
	define( "CLM_PRIVILEGES_FUNCTIONALITY_NAME", "functionality_name" );
	define( "CLM_PRIVILEGES_PRIVILEGE_DESCRIPTION", "privilege_description" );
	define( "CLM_PRIVILEGES_VISIBLE", "visible" );
	
	define( "CLM_ROLES_PRIVILEGES_ROLE_ID", "role_id" );
	define( "CLM_ROLES_PRIVILEGES_PRIVILEGE_ID", "privilege_id" );
	
	define( "CLM_USER_PRIVILEGES_ID", "id" );
	define( "CLM_USER_PRIVILEGES_USER_ID", CLM_USERS_USER_ID );
	define( "CLM_USER_PRIVILEGES_PRIVILEGE_NAME", CLM_PRIVILEGES_PRIVILEGE_NAME );
	*/
	// MYSQL QUERY
	
	//****************************************************************
	// site_config
	//****************************************************************
	/*
	$sql = "CREATE TABLE IF NOT EXISTS `". DB_SITE_CONFIG_TABLE ."` (
			`". CLM_SITE_CONFIG_ID ."` int(11) NOT NULL AUTO_INCREMENT,
			`" . CLM_SITE_CONFIG_SITE_NAME . "` text NOT NULL,
			`". CLM_SITE_CONFIG_DOMAIN_NAME ."` text NOT NULL,
			`". CLM_SITE_CONFIG_SITE_TAGLINE ."` text NOT NULL,
			`". CLM_SITE_CONFIG_SITE_LOGO_IMAGE ."` text NOT NULL,
			`". CLM_SITE_CONFIG_URL_TYPE ."` text NOT NULL,
			`". CLM_SITE_CONFIG_LINK_ENDS_WITH ."` text NOT NULL,
			PRIMARY KEY (`". CLM_SITE_CONFIG_ID ."`)
			) ENGINE=InnoDB  DEFAULT CHARSET=utf16;";	
	*/
	/* $sql = sprintf( $sql, DB_SITE_CONFIG_TABLE, CLM_SITE_CONFIG_ID, CLM_SITE_CONFIG_SITE_NAME, CLM_SITE_CONFIG_DOMAIN_NAME,
			CLM_SITE_CONFIG_SITE_TAGLINE, CLM_SITE_CONFIG_SITE_LOGO_IMAGE, CLM_SITE_CONFIG_URL_TYPE,
			CLM_SITE_CONFIG_LINK_ENDS_WITH, DB_SITE_CONFIG_TABLE );
	 */
	//define( 'QUERY_CREATE_SITE_CONFIG_TABLE', $sql );
	
	//****************************************************************
	// users
	//****************************************************************
	/*
	$sql = "CREATE TABLE IF NOT EXISTS `". DB_USERS_TABLE ."` (
			`". CLM_USERS_USER_ID ."` varchar(50) NOT NULL,
					`". CLM_USERS_USER_ID_CHANGED ."` varchar(3) NOT NULL DEFAULT 'no',
							`". CLM_USERS_FNAME ."` varchar(100) NOT NULL,
									`". CLM_USERS_LNAME ."` varchar(100) NOT NULL,
											`". CLM_USERS_RESIDENCE ."` varchar(100) NOT NULL DEFAULT 'none',
													`". CLM_USERS_STREET ."` varchar(100) NOT NULL DEFAULT 'none',
															`". CLM_USERS_COUNTRY ."` varchar(100) NOT NULL DEFAULT 'none',
																	`". CLM_USERS_STATE ."` varchar(100) NOT NULL DEFAULT 'none',
																			`". CLM_USERS_CITY ."` varchar(100) NOT NULL DEFAULT 'none',
																					`". CLM_USERS_ZIP_CODE ."` varchar(10) NOT NULL DEFAULT 'none',
																							`". CLM_USERS_LANDMARK ."` varchar(100) NOT NULL DEFAULT 'none',
																									`". CLM_USERS_EMAIL ."` varchar(100) NOT NULL,
																											`". CLM_USERS_PASSWORD ."` varchar(100) NOT NULL,
																													`". CLM_USERS_SECURITY_QUESTION ."` varchar(100) NOT NULL DEFAULT 'none',
																															`". CLM_USERS_SECURITY_ANSWER ."` varchar(100) NOT NULL DEFAULT 'none',
																																	`". CLM_USERS_ABOUT_ME ."` longtext NOT NULL,
																																			`". CLM_USERS_PROFILE_PICTURE ."` text NOT NULL,
																																					`". CLM_USERS_REGISTERED_ON ."` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
																																							`". CLM_USERS_ROLE_NAME ."` varchar(100) NOT NULL,
																																									PRIMARY KEY (`". CLM_USERS_USER_ID ."`)
																																											) ENGINE=InnoDB DEFAULT CHARSET=utf16;";
	
	define( 'QUERY_CREATE_USERS_TABLE', $sql );
	
	//****************************************************************
	// hooks
	//****************************************************************
	
	$sql = "CREATE TABLE IF NOT EXISTS `". DB_HOOKS_TABLE ."` (
			`". CLM_HOOKS_ID ."` int(11) NOT NULL AUTO_INCREMENT,
					`". CLM_HOOKS_HOOK_NAME ."` text NOT NULL,
							`". CLM_HOOKS_HOOK_DESCRIPTION ."` text NOT NULL,
									`". CLM_HOOKS_HOOK_CONTENT ."` text NOT NULL,
											`". CLM_HOOKS_HOOK_CONTENT_META ."` text NOT NULL,
													PRIMARY KEY (`". CLM_HOOKS_ID ."`)
															) ENGINE=InnoDB  DEFAULT CHARSET=utf16;";
	
	define( 'QUERY_CREATE_HOOKS_TABLE', $sql );
	
	$sql = "INSERT INTO `". DB_HOOKS_TABLE ."` (`". CLM_HOOKS_ID ."`, `". CLM_HOOKS_HOOK_NAME ."`, `". CLM_HOOKS_HOOK_DESCRIPTION ."`, `". CLM_HOOKS_HOOK_CONTENT ."`, `". CLM_HOOKS_HOOK_CONTENT_META ."`) VALUES
			( 1, 'after_body_starts', 'This is the hook just after the opening of body tag', '', ''),
			( 2, 'before_body_ends', 'This is the hook which is just before the end of </body> tag', '<!-- JQuery Scripts starts here -->        \n		<script type=\"text/javascript\" src=\"js/jquery-2.1.1.min.js\"></script>\n		<script type=\"text/javascript\" src=\"js/jquery-ui-1.10.4.min.js\"></script>\n		<script type=\"text/javascript\" src=\"js/bootstrap.min.js\"></script>\n		<script type=\"text/javascript\" src=\"js/validation.js\"></script>\n		<script type=\"text/javascript\" src=\"js/jQuery.js\"></script>\n		<!-- JQuery Scripts ends here -->\n', 'JQuery Scripts');
			";
	
	define( 'QUERY_INSERT_DEFAULT_HOOKS', $sql );
	
	//****************************************************************
	// pages
	//****************************************************************
	
	$sql = "CREATE TABLE IF NOT EXISTS `". DB_PAGES_TABLE ."` (
			`". CLM_PAGES_PAGE ."` varchar(100) NOT NULL,
					`". CLM_PAGES_PAGE_SEQUENCE ."` int(5) NOT NULL,
							`". CLM_PAGES_PAGE_NAME ."` varchar(100) NOT NULL,
									`". CLM_PAGES_TITLE ."` varchar(100) NOT NULL,
											`". CLM_PAGES_DESCRIPTION ."` text NOT NULL,
													`". CLM_PAGES_TAGS ."` text NOT NULL,
															`". CLM_PAGES_CONTENT ."` longtext CHARACTER SET utf8 NOT NULL,
																	`". CLM_PAGES_IMAGE ."` text NOT NULL,
																			`". CLM_PAGES_VISIBLE ."` int(1) NOT NULL DEFAULT '1',
																					PRIMARY KEY (`". CLM_PAGES_PAGE ."`)
																							) ENGINE=InnoDB DEFAULT CHARSET=utf16;";
	
	define( 'QUERY_CREATE_PAGES_TABLE', $sql );
	
	$sql = "INSERT INTO `". DB_PAGES_TABLE ."` ( `". CLM_PAGES_PAGE ."`, `". CLM_PAGES_PAGE_SEQUENCE ."`, `". CLM_PAGES_PAGE_NAME ."`, `". CLM_PAGES_TITLE ."`, `". CLM_PAGES_DESCRIPTION ."`, `". CLM_PAGES_TAGS ."`, `". CLM_PAGES_CONTENT ."`, `". CLM_PAGES_IMAGE ."`, `". CLM_PAGES_VISIBLE ."`) VALUES
			( 'home', '1', 'Home', 'Home', 'Call Logger Home', '', '', '', '1' ), 
			( 'login', '1', 'Login', 'Call Logger Login', 'Call Logger Login', '', '', '', '1' ), 
			";
	
	define( 'QUERY_INSERT_DEFAULT_PAGES', $sql );
	
	//****************************************************************
	// privileges
	//****************************************************************
	
	$sql = "CREATE TABLE IF NOT EXISTS `". DB_PRIVILEGES_TABLE ."` (
			`". CLM_PRIVILEGES_PRIVILEGE_NAME ."` varchar(100) NOT NULL,
					`". CLM_PRIVILEGES_FUNCTIONALITY ."` varchar(100) NOT NULL,
							`". CLM_PRIVILEGES_FUNCTIONALITY_NAME ."` varchar(100) NOT NULL,
								`". CLM_PRIVILEGES_PRIVILEGE_DESCRIPTION ."` text NOT NULL,
									`". CLM_PRIVILEGES_VISIBLE ."` int(2) DEFAULT 1,
										PRIMARY KEY (`". CLM_PRIVILEGES_PRIVILEGE_NAME ."`)
											) ENGINE=InnoDB DEFAULT CHARSET=utf16;";
	
	define( 'QUERY_CREATE_PRIVILEGES_TABLE', $sql );
	
	$sql = "INSERT INTO `". DB_PRIVILEGES_TABLE ."` (`". CLM_PRIVILEGES_PRIVILEGE_NAME ."`, `". CLM_PRIVILEGES_FUNCTIONALITY ."`, `". CLM_PRIVILEGES_FUNCTIONALITY_NAME ."`, `". CLM_PRIVILEGES_PRIVILEGE_DESCRIPTION ."`) VALUES
			('". PRIVILEGE_ASSIGN_ADMIN_ROLE ."', 'assign_admin_role', 'Assign admin role to user', 'Assign administrator role to an already created user'),
			('". PRIVILEGE_ASSIGN_A_ROLE ."', 'assign_a_role', 'Assign role to user', 'Assign a role to an already created user except the administrator role'),
			('". PRIVILEGE_CHECK_USER_ID_AVAILABILITY ."', 'check_user_id_availability', 'Check User ID Availability', 'Check if user id exists or is allotted'),
			('". PRIVILEGE_CREATE_NEW_USER ."', 'create_new_user', 'Create new user', 'Create a new user with basic user role'),
			('". PRIVILEGE_DELETE_ADMIN ."', 'delete_admin_user', 'Delete Admin User Account', 'Delete a user account having any role'),
			('". PRIVILEGE_DELETE_A_USER ."', 'delete_a_user', 'Delete User Account', 'Delete user account with any role except with administrator role'),
			('". PRIVILEGE_CREATE_NEW_PRIVILEGE ."', 'create_new_privilege', 'Create a privilege', 'Create a new privilege and assign a functionality name to it');";
	
	define( 'QUERY_INSERT_DEFAULT_PRIVILEGES', $sql );
	
	
	//****************************************************************
	// roles
	//****************************************************************
	
	$sql = "CREATE TABLE IF NOT EXISTS `". DB_ROLES_TABLE ."` (
			`". CLM_ROLES_ROLE_NAME ."` varchar(50) NOT NULL,
					`". CLM_ROLES_PRIVILEGE_NAME ."` varchar(50) NOT NULL,
							PRIMARY KEY (`". CLM_ROLES_ROLE_NAME ."`, `". CLM_ROLES_PRIVILEGE_NAME ."` ),
									FOREIGN KEY (`". CLM_ROLES_PRIVILEGE_NAME ."`) REFERENCES `". DB_PRIVILEGES_TABLE ."` (`". CLM_ROLES_PRIVILEGE_NAME ."`)
											) ENGINE=InnoDB DEFAULT CHARSET=utf16;";
	
	define( 'QUERY_CREATE_ROLES_TABLE', $sql );
	
	$sql = "Insert into ". DB_ROLES_TABLE ."( `". CLM_ROLES_ROLE_NAME ."`, `". CLM_ROLES_PRIVILEGE_NAME ."` ) VALUES
								( '". ROLE_ADMIN ."', '". PRIVILEGE_ASSIGN_A_ROLE ."' ),
								( '". ROLE_ADMIN ."', '". PRIVILEGE_ASSIGN_ADMIN_ROLE ."' ),
								( '". ROLE_ADMIN ."', '". PRIVILEGE_CHECK_USER_ID_AVAILABILITY ."' ),
								( '". ROLE_ADMIN ."', '". PRIVILEGE_CREATE_NEW_USER ."' ),
								( '". ROLE_ADMIN ."', '". PRIVILEGE_DELETE_A_USER ."' ),
								( '". ROLE_ADMIN ."', '". PRIVILEGE_DELETE_ADMIN ."' );";

	define( 'QUERY_INSERT_DEFAULT_ROLES', $sql );
	
	//****************************************************************
	// user_privileges
	//****************************************************************
	
	$sql = "CREATE TABLE IF NOT EXISTS `". DB_USER_PRIVILEGES_TABLE ."` (
			`". CLM_USER_PRIVILEGES_ID ."` int(11) NOT NULL AUTO_INCREMENT,
					`". CLM_USER_PRIVILEGES_USER_ID ."` varchar(50) NOT NULL,
							`". CLM_USER_PRIVILEGES_PRIVILEGE_NAME ."` varchar(100) NOT NULL,
									PRIMARY KEY (`". CLM_USER_PRIVILEGES_ID ."`),
											FOREIGN KEY (`". CLM_USER_PRIVILEGES_USER_ID ."`) REFERENCES `". DB_USERS_TABLE ."`(`". CLM_USERS_USER_ID ."`),
													FOREIGN KEY (`". CLM_USER_PRIVILEGES_PRIVILEGE_NAME ."`) REFERENCES `". DB_PRIVILEGES_TABLE ."`(`". CLM_PRIVILEGES_PRIVILEGE_NAME ."`)
															) ENGINE=InnoDB DEFAULT CHARSET=utf16;";
	
	define( 'QUERY_CREATE_USER_PRIVILEGES_TABLE', $sql );
	
	//****************************************************************
	//****************************************************************
	*/
	
?>