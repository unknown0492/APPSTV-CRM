<?php
	/* define( "PLUGIN_NAME", "privilege_management" );						// The name of the plugin, without any keywords like "config", "plugin", etc
	define( "PLUGIN_TYPE", "page" );							// The type of the plugin, if it is a standalone plugin, then type = "root". If the plugin is listed under adminpanel, then type = "page"
	define( "PLUGIN_DESCRIPTION", "To Manage all the Privileges" );
	define( "PLUGIN_DATE", "15 Feb, 2016" );
	define( "PLUGIN_AUTHOR", "Sohel Patel" );
	define( "PLUGIN_VERSION", "1.0" ); */	
	
	$options = array(
			"PLUGIN_NAME"=> "privilege_management",					// The name of the plugin, without any keywords like "config", "plugin", etc
			"PLUGIN_TYPE"=> "page",								// The type of the plugin, if it is a standalone plugin, then type = "root". If the plugin is listed under adminpanel, then type = "page"
			"PLUGIN_DESCRIPTION"=> "To Manage all the Privileges",
			"PLUGIN_DATE"=> "15 Feb, 2016",
			"PLUGIN_AUTHOR"=> "Sohel Patel",
			"PLUGIN_VERSION"=> "1.0",
	);
	/* if( PLUGIN_TYPE === "page" ){ 							// Use these configuration parameters for the PLUGIN_TYPE = "page"
		
	} */
	
	$config_privileges = array( array( "privilege_group_name" => "Privileges Management",
										"privilege_group_privileges" => array(  array( "privilege_name" => "create_privilege_page",
																					  "privilege_description" => "Access to the Create New Privilege page",
																					  "functionality_name" => "create_privilege_page",
																					  "functionality_description" => "This page is used to Create a new Privilege"
																					 ),
																			    array( "privilege_name" => "view_privileges_page",
																					  "privilege_description" => "Access to the View Privileges page",
																					  "functionality_name" => "view_privileges_page",
																					  "functionality_description" => "This page is used to View all Privileges"
																					 ),
																			    array( "privilege_name" => "create_privilege",
																						"privilege_description" => "Allows the user to create a new privilege",
																						"functionality_name" => "create_privilege",
																						"functionality_description" => "Creates a new privilege-functionality pair in their respective tables and maps them to privileges_functionalities table"
																				),
																				array( "privilege_name" => "view_privileges",
																						"privilege_description" => "Allows the user to view all the privileges",
																						"functionality_name" => "view_privileges",
																						"functionality_description" => "Retrieves all the privilege-functionality information and lists them on the page"
																				),
																				array( "privilege_name" => "update_privilege",
																						"privilege_description" => "Allows the user to update privilege information",
																						"functionality_name" => "update_privilege",
																						"functionality_description" => "Updates the information for the privilege/functionality"
																				),
																				array( "privilege_name" => "delete_privilege",
																						"privilege_description" => "Allows the user to delete the privilege",
																						"functionality_name" => "delete_privilege",
																						"functionality_description" => "Delete a privilege-functionality pair"
																				),
																				array( "privilege_name" => "create_privilege_group",
																						"privilege_description" => "Allows the user to create a new privilege group",
																						"functionality_name" => "create_privilege_group",
																						"functionality_description" => "Allows the user to create a new privilege group"
																				),
																				array( "privilege_name" => "edit_privilege_page",
																						"privilege_description" => "Provides indirect access to the Edit Privilege page where the Privilege and Functionality information can be amended",
																						"functionality_name" => "edit_privilege_page",
																						"functionality_description" => "Provides indirect access to the Edit Privilege page where the Privilege and Functionality information can be amended"
																				),
																				array( "privilege_name" => "update_privilege_group",
																						"privilege_description" => "Allows to update the Privilege Group information",
																						"functionality_name" => "update_privilege_group",
																						"functionality_description" => "Allows to update the Privilege Group information"
																				),
																				array( "privilege_name" => "delete_privilege_group",
																						"privilege_description" => "Allows to delete the Privilege Group and the groupless privileges are moved to the Other Privileges",
																						"functionality_name" => "delete_privilege_group",
																						"functionality_description" => "Allows to delete the Privilege Group and the groupless privileges are moved to the Other Privileges"
																				),
																			  )
									  										),
							   											);
	
	
	$config_pages = array( array( "page_group_name" => "Privilege Management",
								  "icon" => "fa fa-smile",
								  "page_group_pages" => array( array( "page_name" => "create_privilege_page",
								  									  "icon" => "fa fa-smile-o",
								  									  "visible" => "1",
								  									  "page_title" => "Create Privilege",
								  									  "functionality_name" => "create_privilege_page",
								  									  "title" => "This Page is used to create a new Privilege",
								  									  "description" => "",
								  									  "tags" => "",
								  									  "image" => "http://flopsite.com/logo.png",
								  									  "content" => ""
								  							  		 ),
								  								array( "page_name" => "view_privileges_page",
								  									  "icon" => "fa fa-smile-o",
								  									  "visible" => "1",
								  									  "page_title" => "View/Edit Privileges",
								  									  "functionality_name" => "view_privileges_page",
								  									  "title" => "This Page is used to View all Privileges",
								  									  "description" => "",
								  									  "tags" => "",
								  									  "image" => "http://flopsite.com/logos.png",
								  									  "content" => ""
								  							  		 ),
														  		array( "page_name" => "edit_privilege_page",
														  				"icon" => "fa fa-smile-o",
														  				"visible" => "0",
														  				"page_title" => "Edit Privileges Data",
														  				"functionality_name" => "edit_privilege_page",
														  				"title" => "Provides indirect access to the Edit Privilege Page",
														  				"description" => "",
														  				"tags" => "",
														  				"image" => "http://flopsite.com/logos.png",
														  				"content" => ""
														  		),
								  							  )
							 	 							) 
						  								);
	
	$config_js = array( 															// Include all the js file names without the extension
			"create_privilege_page",
			"view_privileges_page",
			"edit_privilege_page"
	);
	
	$config_css = array( 															// Include all the css file names without the extension
			"create_privilege_page",
			"view_privileges_page",
			"edit_privilege_page"
	);
?>