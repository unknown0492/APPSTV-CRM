<?php
	/* define( "PLUGIN_NAME", "website_configuration" );						// The name of the plugin, without any keywords like "config", "plugin", etc
	define( "PLUGIN_TYPE", "page" );							// The type of the plugin, if it is a standalone plugin, then type = "root". If the plugin is listed under adminpanel, then type = "page"
	define( "PLUGIN_DESCRIPTION", "To Manage all the Pages" );
	define( "PLUGIN_DATE", "15 Feb, 2016" );
	define( "PLUGIN_AUTHOR", "Sohel Patel" );
	define( "PLUGIN_VERSION", "1.0" ); */
	
	$options = array(
		"PLUGIN_NAME"=> "website_configuration",					// The name of the plugin, without any keywords like "config", "plugin", etc
		"PLUGIN_TYPE"=> "page",								// The type of the plugin, if it is a standalone plugin, then type = "root". If the plugin is listed under adminpanel, then type = "page"
		"PLUGIN_DESCRIPTION"=> "To manage hooks/site configurations",
		"PLUGIN_DATE"=> "15 Feb, 2016",
		"PLUGIN_AUTHOR"=> "Sohel Patel",
		"PLUGIN_VERSION"=> "1.0",
	);
	/* if( PLUGIN_TYPE === "page" ){ 							// Use these configuration parameters for the PLUGIN_TYPE = "page"
		
	} */
	
	$config_privileges = array( array( "privilege_group_name" => "Website Configuration",
										"privilege_group_privileges" => array(  array( "privilege_name" => "site_config_page",
																					  "privilege_description" => "Allows access to the Site Config Page where the portal configuration can be managed",
																					  "functionality_name" => "site_config_page",
																					  "functionality_description" => "Allows access to the Site Config Page where the portal configuration can be managed"
																					 ),
																			    array( "privilege_name" => "update_site_config",
																					  "privilege_description" => "To Update Site Configurations",
																					  "functionality_name" => "update_site_config",
																					  "functionality_description" => "To Update Site Configurations"
																					 ),
																			    array( "privilege_name" => "create_hooks_page",
																						"privilege_description" => "Create Hooks Page",
																						"functionality_name" => "create_hooks_page",
																						"functionality_description" => "Create Hooks Page"
																				),
																				array( "privilege_name" => "view_hooks_page",
																						"privilege_description" => "To view all the hooks created",
																						"functionality_name" => "view_hooks_page",
																						"functionality_description" => "To view all the hooks created"
																				),
																				array( "privilege_name" => "edit_hooks_page",
																						"privilege_description" => "To edit hooks",
																						"functionality_name" => "edit_hooks_page",
																						"functionality_description" => "To edit hooks"
																				),
																				array( "privilege_name" => "delete_hook",
																						"privilege_description" => "To Delete Hook",
																						"functionality_name" => "delete_hook",
																						"functionality_description" => "To Delete Hook"
																				),
																			  )
									  										),
							   											);
	
	
	$config_pages = array( array( "page_group_name" => "Website Configurations",
								  "icon" => "fa fa-smile",
								  "page_group_pages" => array( array( "page_name" => "site_config_page",
								  									  "icon" => "fa fa-smile-o",
								  									  "visible" => "1",
								  									  "page_title" => "Site Configuration",
								  									  "functionality_name" => "site_config_page",
								  									  "title" => "Allows access to the site config page where the portal configuration can be managed",
								  									  "description" => "",
								  									  "tags" => "",
								  									  "image" => "http://flopsite.com/logo.png",
								  									  "content" => ""
								  							  		 ),
								  								array( "page_name" => "create_hooks_page",
								  									  "icon" => "fa fa-smile-o",
								  									  "visible" => "1",
								  									  "page_title" => "Create Hooks Page",
								  									  "functionality_name" => "create_hooks_page",
								  									  "title" => "Create Hooks Page",
								  									  "description" => "",
								  									  "tags" => "",
								  									  "image" => "http://flopsite.com/logos.png",
								  									  "content" => ""
								  							  		 ),
														  		array( "page_name" => "view_hooks_page",
														  				"icon" => "fa fa-smile-o",
														  				"visible" => "1",
														  				"page_title" => "View Hooks Page",
														  				"functionality_name" => "view_hooks_page",
														  				"title" => "To view all the hooks created",
														  				"description" => "",
														  				"tags" => "",
														  				"image" => "http://flopsite.com/logos.png",
														  				"content" => ""
														  		),
														  		array( "page_name" => "edit_hooks_page",
														  				"icon" => "fa fa-smile-o",
														  				"visible" => "0",
														  				"page_title" => "Edit Hooks Page",
														  				"functionality_name" => "edit_hooks_page",
														  				"title" => "To edit Hooks",
														  				"description" => "",
														  				"tags" => "",
														  				"image" => "http://flopsite.com/logos.png",
														  				"content" => ""
														  		),
								  							  )
							 	 							) 
						  								);
	
	$config_js = array( 															// Include all the js file names without the extension
			"site_config_page",
			"create_hooks_page",
			"view_hooks_page",
			"edit_hooks_page"
	);
	
	$config_css = array( 															// Include all the css file names without the extension
			"site_config_page",
			"create_hooks_page",
			"view_hooks_page",
			"edit_hooks_page"
	);
?>