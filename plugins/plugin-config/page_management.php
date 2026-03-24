<?php
	
	$options = array(
		"PLUGIN_NAME"=> "page_management",					// The name of the plugin, without any keywords like "config", "plugin", etc
		"PLUGIN_TYPE"=> "page",								// The type of the plugin, if it is a standalone plugin, then type = "root". If the plugin is listed under adminpanel, then type = "page"
		"PLUGIN_DESCRIPTION"=> "To Manage all the Pages",
		"PLUGIN_DATE"=> "10 March, 2016",
		"PLUGIN_AUTHOR"=> "Sohail Shaikh",
		"PLUGIN_VERSION"=> "1.4",
	);
	
	$config_queries = array( 
		"CREATE TABLE IF NOT EXISTS `pages` ( `page_id` int(11) NOT NULL AUTO_INCREMENT, `page_name` varchar(100) DEFAULT NULL, `page_sequence` int(11) DEFAULT '1', `icon` varchar(100) DEFAULT NULL,   `visible` int(2) DEFAULT NULL,   `page_title` varchar(100) DEFAULT NULL,   `functionality_id` int(11) DEFAULT NULL,   `title` varchar(100) NOT NULL,   `description` varchar(1000) NOT NULL,   `tags` varchar(100) NOT NULL,   `image` varchar(100) NOT NULL,   `content` varchar(1000) NOT NULL,   `plugin_name` varchar(100) NOT NULL,   PRIMARY KEY (`page_id`),   KEY `functionality_id` (`functionality_id`),   KEY `plugin_name` (`plugin_name`));",
		"CREATE TABLE IF NOT EXISTS `pages_groups` ( `group_id` int(11) NOT NULL,  `page_id` int(11) NOT NULL,  `page_sequence` int(11) NOT NULL DEFAULT '1',  PRIMARY KEY (`page_id`,`group_id`),  KEY `group_id` (`group_id`));",
		"CREATE TABLE IF NOT EXISTS `page_group` ( `page_group_id` int(11) NOT NULL AUTO_INCREMENT,  `page_group_name` varchar(100) NOT NULL,  `page_group_sequence` int(11) NOT NULL,  `icon` varchar(100) NOT NULL,  PRIMARY KEY (`page_group_id`));",
		"ALTER TABLE `pages` ADD CONSTRAINT `pages_ibfk_1` FOREIGN KEY (`functionality_id`) REFERENCES `functionalities` (`functionality_id`) ON DELETE CASCADE ON UPDATE CASCADE;",
		"ALTER TABLE `pages_groups`  ADD CONSTRAINT `pages_groups_ibfk_1` FOREIGN KEY (`page_id`) REFERENCES `pages` (`page_id`) ON DELETE CASCADE ON UPDATE CASCADE,  ADD CONSTRAINT `pages_groups_ibfk_2` FOREIGN KEY (`group_id`) REFERENCES `page_group` (`page_group_id`) ON DELETE CASCADE ON UPDATE CASCADE;",
		"INSERT into `page_group`( `page_group_id`, `page_group_name`, `page_group_sequence`, `icon` ) VALUES( 0, 'All Other Pages', 0, 'fa fa-files-o' ) ON DUPLICATE KEY UPDATE `page_group_name` = 'All Other Pages', `page_group_sequence` = 0, `icon` = 'fa fa-files-o' WHERE `page_group_id` = 0;",
	);
	
	$config_privileges = array( array( "privilege_group_name" => "Page Management",
										"privilege_group_privileges" => array(  array( "privilege_name" => "create_new_page",
																					  "privilege_description" => "Provides access to the Create New Page",
																					  "functionality_name" => "create_new_page",
																					  "functionality_description" => "Provides access to the Create New Page"
																					 ),
																			    array( "privilege_name" => "view_system_page",
																					  "privilege_description" => "Provide access to the View System Pages section",
																					  "functionality_name" => "view_system_page",
																					  "functionality_description" => "Provide access to the View System Pages section"
																					 ),
																			    array( "privilege_name" => "edit_system_page",
																						"privilege_description" => "Provide access to the Edit System Page section",
																						"functionality_name" => "edit_system_page",
																						"functionality_description" => "Provide access to the Edit System Page section"
																				),
																				array( "privilege_name" => "create_page_group",
																						"privilege_description" => "Allows to create a New Page Group",
																						"functionality_name" => "create_page_group",
																						"functionality_description" => "Allows to create a New Page Group"
																				),
																				array( "privilege_name" => "create_page",
																						"privilege_description" => "Allows to create a New Page inside the Adminpanel tabs",
																						"functionality_name" => "create_page",
																						"functionality_description" => "Allows to create a New Page inside the Adminpanel tabs"
																				),
																				array( "privilege_name" => "update_page",
																						"privilege_description" => "Allows to update the Page Information",
																						"functionality_name" => "update_page",
																						"functionality_description" => "Allows to update the Page Information"
																				),
																				array( "privilege_name" => "delete_page",
																						"privilege_description" => "Allows to Delete an existing page from the Adminpanel sub tabs",
																						"functionality_name" => "delete_page",
																						"functionality_description" => "Allows to Delete an existing page from the Adminpanel sub tabs"
																				),
																				array( "privilege_name" => "delete_page_group",
																						"privilege_description" => "Allows to Delete a Page Group",
																						"functionality_name" => "delete_page_group",
																						"functionality_description" => "Allows to Delete a Page Group"
																				),
																				array( "privilege_name" => "update_page_group",
																						"privilege_description" => "Allows to update the Page Group Information",
																						"functionality_name" => "update_page_group",
																						"functionality_description" => "Allows to update the Page Group Information"
																				),
																			  )
									  										),
							   											);
	
	
	$config_pages = array( array( "page_group_name" => "Page Management",
								  "icon" => "fa fa-file-text-o",
								  "page_group_pages" => array( array( "page_name" => "create_new_page",
								  									  "icon" => "",
								  									  "visible" => "1",
								  									  "page_title" => "Create New Page",
								  									  "functionality_name" => "create_new_page",
								  									  "title" => "Provides access to Create New Page",
								  									  "description" => "",
								  									  "tags" => "",
								  									  "image" => "",
								  									  "content" => ""
								  							  		 ),
								  								array( "page_name" => "view_system_page",
								  									  "icon" => "",
								  									  "visible" => "1",
								  									  "page_title" => "View/Edit Pages",
								  									  "functionality_name" => "view_system_page",
								  									  "title" => "Provides access to View System Pages Section",
								  									  "description" => "",
								  									  "tags" => "",
								  									  "image" => "",
								  									  "content" => ""
								  							  		 ),
														  		array( "page_name" => "edit_system_page",
														  				"icon" => "",
														  				"visible" => "0",
														  				"page_title" => "Edit System Page",
														  				"functionality_name" => "edit_system_page",
														  				"title" => "Provide access to Edit System Page Section",
														  				"description" => "",
														  				"tags" => "",
														  				"image" => "",
														  				"content" => ""
														  		),
								  							  )
							 	 							) 
						  								);
	
/* 	$config_js = array( 															// Include all the js file names without the extension
			"create_new_page",
			"view_system_page",
			"edit_system_page"
	);
	
	$config_css = array( 															// Include all the css file names without the extension
			"create_new_page",
			"view_system_page",
			"edit_system_page"
	); */
?>