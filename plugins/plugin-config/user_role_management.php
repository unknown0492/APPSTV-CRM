<?php
	/* define( "PLUGIN_NAME", "user_role_management" );						// The name of the plugin, without any keywords like "config", "plugin", etc
	define( "PLUGIN_TYPE", "page" );							// The type of the plugin, if it is a standalone plugin, then type = "root". If the plugin is listed under adminpanel, then type = "page"
	define( "PLUGIN_DESCRIPTION", "To manage all the User Roles" );
	define( "PLUGIN_DATE", "15 Feb, 2016" );
	define( "PLUGIN_AUTHOR", "Sohel Patel" );
	define( "PLUGIN_VERSION", "1.0" );	 */
	
	$options = array(
			"PLUGIN_NAME"=> "user_role_management",					// The name of the plugin, without any keywords like "config", "plugin", etc
			"PLUGIN_TYPE"=> "page",								// The type of the plugin, if it is a standalone plugin, then type = "root". If the plugin is listed under adminpanel, then type = "page"
			"PLUGIN_DESCRIPTION"=> "To Manage all the User Roles",
			"PLUGIN_DATE"=> "15 Feb, 2016",
			"PLUGIN_AUTHOR"=> "Sohel Patel",
			"PLUGIN_VERSION"=> "1.0",
	);
	/* if( PLUGIN_TYPE === "page" ){ 							// Use these configuration parameters for the PLUGIN_TYPE = "page"
		
	} */
	
	$config_privileges = array( array( "privilege_group_name" => "Role Management",
										"privilege_group_privileges" => array(  array( "privilege_name" => "create_role_page",
																					  "privilege_description" => "Provide access to the Create Role Page",
																					  "functionality_name" => "create_role_page",
																					  "functionality_description" => "This page is used to Create a new Role"
																					 ),
																			    array( "privilege_name" => "view_roles_page",
																					  "privilege_description" => "Provide access to the View/Edit Roles Page",
																					  "functionality_name" => "view_roles_page",
																					  "functionality_description" => "This page is used to View/Edit Privileges to Roles"
																					 ),
																			    array( "privilege_name" => "create_role",
																						"privilege_description" => "This privilege allows the user to Create New Role and assign privileges to it",
																						"functionality_name" => "create_role",
																						"functionality_description" => "This is a webservice function name which validates the form data and creates a new role."
																				),
																				array( "privilege_name" => "update_role",
																						"privilege_description" => "Allows the user to update the privileges for a Role",
																						"functionality_name" => "update_role",
																						"functionality_description" => "Allows the user to update the privileges for a Role"
																				),
																				array( "privilege_name" => "delete_role",
																						"privilege_description" => "This function deletes the role specified by the role id",
																						"functionality_name" => "delete_role",
																						"functionality_description" => "This function returns all the privileges assigned to the specified role id"
																				),
																			  )
									  										),
							   											);
	
	
	$config_pages = array( array( "page_group_name" => "User Role Management",
								  "icon" => "fa fa-smile",
								  "page_group_pages" => array( array( "page_name" => "create_role_page",
								  									  "icon" => "fa fa-smile-o",
								  									  "visible" => "1",
								  									  "page_title" => "Create New Role",
								  									  "functionality_name" => "create_role_page",
								  									  "title" => "This page is used to Create a new Role",
								  									  "description" => "",
								  									  "tags" => "",
								  									  "image" => "http://flopsite.com/logo.png",
								  									  "content" => ""
								  							  		 ),
								  								array( "page_name" => "view_roles_page",
								  									  "icon" => "fa fa-smile-o",
								  									  "visible" => "1",
								  									  "page_title" => "View/Edit Roles",
								  									  "functionality_name" => "view_roles_page",
								  									  "title" => "This page is used to View/Edit Privileges to Roles",
								  									  "description" => "",
								  									  "tags" => "",
								  									  "image" => "http://flopsite.com/logos.png",
								  									  "content" => ""
								  							  		 ),
								  							  )
							 	 							) 
						  								);
	
	$config_js = array( 															// Include all the js file names without the extension
			"create_role_page",
			"view_roles_page"
	);
	
	$config_css = array( 															// Include all the css file names without the extension
			"create_role_page",
			"view_roles_page"
	);
	
	
?>