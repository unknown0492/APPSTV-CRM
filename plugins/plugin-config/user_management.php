<?php
	/* define( "PLUGIN_NAME", "user_management" );						// The name of the plugin, without any keywords like "config", "plugin", etc
	define( "PLUGIN_TYPE", "page" );							// The type of the plugin, if it is a standalone plugin, then type = "root". If the plugin is listed under adminpanel, then type = "page"
	define( "PLUGIN_DESCRIPTION", "To Manage all the users" );
	define( "PLUGIN_DATE", "15 Feb, 2016" );
	define( "PLUGIN_AUTHOR", "Sohel Patel" );
	define( "PLUGIN_VERSION", "1.0" );	 */
	
	$options = array(
			"PLUGIN_NAME"=> "user_management",					// The name of the plugin, without any keywords like "config", "plugin", etc
			"PLUGIN_TYPE"=> "page",								// The type of the plugin, if it is a standalone plugin, then type = "root". If the plugin is listed under adminpanel, then type = "page"
			"PLUGIN_DESCRIPTION"=> "To Manage all the Users",
			"PLUGIN_DATE"=> "15 Feb, 2016",
			"PLUGIN_AUTHOR"=> "Sohel Patel",
			"PLUGIN_VERSION"=> "1.0",
	);
	/* if( PLUGIN_TYPE === "page" ){ 							// Use these configuration parameters for the PLUGIN_TYPE = "page"
		
	} */
	
	$config_privileges = array( array( "privilege_group_name" => "User Management",
										"privilege_group_privileges" => array(  array( "privilege_name" => "create_user_page",
																					  "privilege_description" => "Allow to access the Create New User page",
																					  "functionality_name" => "create_user_page",
																					  "functionality_description" => "This is a page used for Creating New Users"
																					 ),
																			    array( "privilege_name" => "view_users_page",
																					  "privilege_description" => "Allow to view all the users in the system and their information",
																					  "functionality_name" => "view_users_page",
																					  "functionality_description" => "This page is used for Viewing All Users"
																					 ),
																			    array( "privilege_name" => "edit_user_page",
																						"privilege_description" => "Allow to access the Edit User Profile Information page",
																						"functionality_name" => "edit_user_page",
																						"functionality_description" => "This page is used for Editing Users"
																				),
																				array( "privilege_name" => "create_user",
																						"privilege_description" => "Allow to Create New User in the system",
																						"functionality_name" => "create_user",
																						"functionality_description" => "Creates a new user account in the system"
																				),
																				array( "privilege_name" => "view_users",
																						"privilege_description" => "Allow to view all user information",
																						"functionality_name" => "view_users",
																						"functionality_description" => "View all users in the system along with their profile information"
																				),
																				array( "privilege_name" => "update_user",
																						"privilege_description" => "Allow to Edit/Update User Profile Information",
																						"functionality_name" => "update_user",
																						"functionality_description" => "Update the profile information for any user"
																				),
																				array( "privilege_name" => "delete_user",
																						"privilege_description" => "Allow to Delete any user account from the system",
																						"functionality_name" => "delete_user",
																						"functionality_description" => "Delete any user account"
																				),
																				array( "privilege_name" => "update_profile",
																						"privilege_description" => "Allow the users to update their own Profile Information",
																						"functionality_name" => "update_profile",
																						"functionality_description" => "Update information on your own profile"
																				),
																				array( "privilege_name" => "user_info",
																						"privilege_description" => "Allows retrieval of User Information",
																						"functionality_name" => "user_info",
																						"functionality_description" => "Allows to retrieve User Information"
																				),
																				array( "privilege_name" => "is_user_id_available",
																						"privilege_description" => "Allows to check if the User ID trying to acquire is available or taken",
																						"functionality_name" => "is_user_id_available",
																						"functionality_description" => "Allows to check if the User ID trying to acquire is available or taken"
																				),
																				array( "privilege_name" => "reset_password",
																						"privilege_description" => "Allows to reset the user account password for the specified user id",
																						"functionality_name" => "reset_password",
																						"functionality_description" => "Allows to reset the user account password for the specified user id 	
																						"
																				),
																			  )
									  										),
							   											);
	
	
	$config_pages = array( array( "page_group_name" => "User Role Management",
								  "icon" => "fa fa-smile",
								  "page_group_pages" => array( array( "page_name" => "create_user_page",
								  									  "icon" => "fa fa-smile-o",
								  									  "visible" => "1",
								  									  "page_title" => "Create New User",
								  									  "functionality_name" => "create_user_page",
								  									  "title" => "This page is used to Create a new Users",
								  									  "description" => "",
								  									  "tags" => "",
								  									  "image" => "http://flopsite.com/logo.png",
								  									  "content" => ""
								  							  		 ),
								  								array( "page_name" => "view_users_page",
								  									  "icon" => "fa fa-smile-o",
								  									  "visible" => "1",
								  									  "page_title" => "View/Edit Users",
								  									  "functionality_name" => "view_users_page",
								  									  "title" => "This page is used to Viewing All Users",
								  									  "description" => "",
								  									  "tags" => "",
								  									  "image" => "http://flopsite.com/logos.png",
								  									  "content" => ""
								  							  		 ),
														  		array( "page_name" => "edit_user_page",
														  				"icon" => "fa fa-smile-o",
														  				"visible" => "0",
														  				"page_title" => "Edit User Information",
														  				"functionality_name" => "edit_user_page",
														  				"title" => "This page is used to Editing Users",
														  				"description" => "",
														  				"tags" => "",
														  				"image" => "http://flopsite.com/logos.png",
														  				"content" => ""
														  		),
								  							  )
							 	 							) 
						  								);
	
	$config_js = array( 															// Include all the js file names without the extension
			"create_user_page",
			"view_users_page",
			"edit_user_page"
	);
	
	$config_css = array( 															// Include all the css file names without the extension
			"create_user_page",
			"view_users_page",
			"edit_user_page"
	);
?>